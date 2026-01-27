<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use ZipArchive;

class ReceitaController extends Controller
{
    private $basePath;
    private $configFile;
    private $db;
    private $progress;
    private $stats = []; // Armazenará [tabela => quantidade]
    
    public function __construct()
    {
        $this->basePath = FCPATH . '../' . rtrim(env('CNPJ_DOWNLOAD_PATH', 'writable/receita/'), '/') . '/';
        if (!is_dir($this->basePath)) mkdir($this->basePath, 0777, true);
        
        $this->configFile = $this->basePath . 'import.conf';
        $this->db = \Config\Database::connect();
        
        if (property_exists($this->db, 'saveQueries')) {
            $this->db->saveQueries = false;
        }
        
        $this->loadProgress();
    }
    
    public function index()
    {
        return view('receita/index', [
            'pageTitle' => 'Importação Receita Federal',
            'activeMenu' => 'receita'
        ]);
    }
    
    // Busca AJAX para o Select2
    public function buscarCnaes()
    {
        $term = $this->request->getGet('q');
        $db = \Config\Database::connect();
        // Busca na tabela prefixada conforme configurado anteriormente
        $builder = $db->table('receita_cnaes');
        $builder->select('codigo as id, CONCAT(codigo, " - ", descricao) as text');
        $builder->like('codigo', $term)->orLike('descricao', $term);
        $results = $builder->limit(20)->get()->getResultArray();
        
        return $this->response->setJSON($results);
    }
    
    // Método para forçar o encerramento do processo
    public function parar()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            shell_exec('taskkill /F /IM php.exe /T');
        }
        return $this->response->setJSON(['status' => 'Processos encerrados']);
    }
    
    private function echoStatus($message, $newline = true)
    {
        // Adicionado prefixo LOG: consistente para o parser JS
        echo "LOG:" . $message . ($newline ? "<br>" : "") . "\n";
        echo str_repeat(' ', 1024);
        if (ob_get_level() > 0) ob_end_flush();
        flush();
    }
    
    public function importar()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        gc_enable();
        
        $restart = $this->request->getGet('restart') === 'true';
        if ($restart) {
            $this->progress = ['ultimo_arquivo' => '', 'ultima_linha' => 0];
            if (file_exists($this->configFile)) unlink($this->configFile);
        }
        
        $cnaesSelecionados = $this->request->getGet('cnaes');
        if (!empty($cnaesSelecionados)) {
            putenv("CNPJ_CNAES_FILTRO=" . implode(',', $cnaesSelecionados));
        }
        
        if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1');
        ini_set('zlib.output_compression', '0');
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        ob_implicit_flush(true);
        
        $this->echoStatus("<strong>>>> INICIANDO IMPORTAÇÃO</strong>");
        
        $foundUrl = $this->findLatestUrl();
        if (!$foundUrl) return;
        
        $fila = $this->getFilaArquivos();
        
        $this->echoStatus("FASE 1: Downloads");
        foreach ($fila as $zipName) {
            $this->downloadIfNewer($foundUrl . $zipName, $zipName);
        }
        
        $this->echoStatus("<br>FASE 2: Processamento");
        $this->processarArquivos($fila);
        
        $this->exibirResumoFinal();
        
        $this->echoStatus("<br>✅ <strong>CONCLUÍDO!</strong>");
    }
    
    private function exibirResumoFinal()
    {
        $this->echoStatus("\n--- RESUMO DA IMPORTAÇÃO ---");
        // Cabeçalho simples em texto
        $this->echoStatus(str_pad("TABELA", 25) . " | REGISTROS");
        $this->echoStatus(str_repeat("-", 40));
        
        foreach ($this->stats as $tabela => $quantidade) {
            // Remove o prefixo e limpa tags se houver
            $nomeSemPrefixo = strtoupper(str_replace('receita_', '', $tabela));
            $qtdFormatada = number_format($quantidade, 0, ',', '.');
            
            // Formata uma linha de texto alinhada
            $linha = str_pad($nomeSemPrefixo, 25) . " | " . $qtdFormatada;
            $this->echoStatus($linha);
        }
        $this->echoStatus(str_repeat("-", 40));
    }
    
    private function downloadIfNewer($remoteUrl, $zipName)
    {
        $localPath = $this->basePath . $zipName;
        $folderUrl = dirname($remoteUrl) . '/';
        
        $ch = curl_init($folderUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);
        
        $remoteTime = 0;
        $pattern = '/href="' . preg_quote($zipName) . '".*?>(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2})/is';
        if (preg_match($pattern, $html, $matches)) {
            $remoteTime = strtotime($matches[1]);
        }
        
        if ($remoteTime <= 0 || $remoteTime > 2147483647) return;
        
        $localTime = file_exists($localPath) ? filemtime($localPath) : 0;
        
        if ($remoteTime > $localTime) {
            $this->echoStatus("Baixando: <strong>{$zipName}</strong>... ", false);
            $fp = fopen($localPath, 'w+');
            $ch = curl_init($remoteUrl);
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 0
            ]);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            
            clearstatcache(true, $localPath);
            usleep(500000);
            @touch($localPath, $remoteTime);
            $this->echoStatus("<span style='color:green;'>✓ OK</span>");
        } else {
            $this->echoStatus("<span style='color:gray;'>{$zipName} já está na versão mais atual.</span>");
        }
    }
    
    private function processarArquivos($fila)
    {
        $cnaesFiltro = array_filter(explode(',', env('CNPJ_CNAES_FILTRO', '')));
        
        // Cache da ordem da fila para comparação correta de progresso
        $ordemFila = array_flip($fila);
        
        foreach ($fila as $zipName) {
            // CORREÇÃO: Verifica se o arquivo já foi processado seguindo a ordem da fila, não ordem alfabética
            if ($this->progress['ultimo_arquivo'] && $ordemFila[$zipName] < $ordemFila[$this->progress['ultimo_arquivo']]) {
                continue;
            }
            
            $path = $this->basePath . $zipName;
            if (!file_exists($path)) continue;
            
            $rawName = strtolower(preg_replace('/[0-9]|\.zip/', '', $zipName));
            $tableName = 'receita_' . $rawName;
            
            $zip = new ZipArchive;
            if ($zip->open($path) === TRUE) {
                $stat = $zip->statIndex(0);
                $fileSize = $stat['size'];
                $fp = $zip->getStream($zip->getNameIndex(0));
                
                $this->echoStatus("Processando: <strong>{$zipName}</strong>", true);
                $this->renderShellBar(0); // Força exibição de 0% ao iniciar arquivos grandes
                
                $lineCount = 0;
                $processedBytes = 0;
                $lastPercent = -1;
                $batchData = [];
                $skipTo = ($zipName == $this->progress['ultimo_arquivo']) ? $this->progress['ultima_linha'] : 0;
                
                $fields = $this->db->getFieldNames($tableName);
                $this->db->transBegin();
                
                while (($line = fgets($fp)) !== FALSE) {
                    $processedBytes += strlen($line);
                    $lineCount++;
                    
                    if ($lineCount <= $skipTo) {
                        unset($line); continue;
                    }
                    
                    $data = str_getcsv($line, ';', '"');
                    unset($line);
                    
                    if ($rawName == 'estabelecimentos') {
                        if (!empty($cnaesFiltro) && !in_array($data[11] ?? '', $cnaesFiltro)) { unset($data); continue; }
                        $data[] = $this->isContabilidade($data[27] ?? '') ? 1 : 0;
                    }
                    
                    if ($rawName == 'socios') {
                        $exists = $this->db->table('receita_estabelecimentos')->where('cnpj_basico', $data[0] ?? '')->countAllResults();
                        if ($exists === 0) { unset($data); continue; }
                    }
                    
                    $row = [];
                    foreach ($fields as $idx => $fName) {
                        if (isset($data[$idx])) {
                            $row[$fName] = ($data[$idx] !== null) ? mb_convert_encoding($data[$idx], 'UTF-8', 'ISO-8859-1') : null;
                        }
                    }
                    $batchData[] = $row;
                    unset($data);
                    
                    if (count($batchData) >= 500) {
                        $this->db->table($tableName)->ignore(true)->insertBatch($batchData);
                        
                        // Incrementa a estatística
                        if (!isset($this->stats[$tableName])) $this->stats[$tableName] = 0;
                        $this->stats[$tableName] += count($batchData);
                        
                        unset($batchData);
                        $batchData = [];
                        
                        $percent = round(($processedBytes / $fileSize) * 100);
                        if ($percent > $lastPercent) {
                            $this->renderShellBar($percent);
                            $lastPercent = $percent;
                        }
                        
                        $this->db->transCommit();
                        $this->saveProgress($zipName, $lineCount);
                        gc_collect_cycles();
                        $this->db->transBegin();
                    }
                }
                
                $this->db->transCommit();
                $this->saveProgress($zipName, $lineCount);
                $zip->close();
                fclose($fp);
                $this->renderShellBar(100);
                $this->echoStatus("", true);
            }
        }
    }
    
    /**
     * Renderiza a barra estilo [XXXXX.....] %
     */
    private function renderShellBar($percent)
    {
        $totalWidth = 40;
        $done = round(($percent / 100) * $totalWidth);
        $left = $totalWidth - $done;
        $bar = str_repeat("X", $done) . str_repeat(".", $left);
        
        // Prefixo BAR: e sufixo claro para atualização em tempo real
        echo "BAR:[{$bar}] {$percent}% <br/> \n";
        
        echo str_repeat(' ', 1024);
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
    
    private function findLatestUrl()
    {
        $baseUrl = rtrim(env('CNPJ_BASE_URL'), '/') . '/';
        $date = new \DateTime();
        for ($i = 0; $i < 12; $i++) {
            $folder = $date->format('Y-m');
            $url = $baseUrl . $folder . '/';
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'Mozilla/5.0']);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200) return $url;
            $date->modify('-1 month');
        }
        return null;
    }
    
    private function isContabilidade($email)
    {
        if (empty($email) || strpos($email, '@') === false) return false;
        list($usuario, $dominio) = explode('@', strtolower($email));
        $termos = ['contabil', 'contabilidade', 'contabilista', 'assessoriacontabil'];
        $genericos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'uol.com.br'];
        if (in_array($dominio, $genericos)) {
            foreach ($termos as $t) if (strpos($usuario, $t) !== false) return true;
        } else {
            foreach ($termos as $t) if (strpos($dominio, $t) !== false) return true;
        }
        return false;
    }
    
    private function loadProgress()
    {
        if (file_exists($this->configFile)) {
            $content = file_get_contents($this->configFile);
            $decoded = json_decode($content, true);
            $this->progress = ($decoded && isset($decoded['ultimo_arquivo'])) ? $decoded : ['ultimo_arquivo' => '', 'ultima_linha' => 0];
        } else {
            $this->progress = ['ultimo_arquivo' => '', 'ultima_linha' => 0];
        }
    }
    
    private function saveProgress($arq, $lin)
    {
        $this->progress = ['ultimo_arquivo' => $arq, 'ultima_linha' => $lin];
        file_put_contents($this->configFile, json_encode($this->progress));
    }
    
    private function getFilaArquivos()
    {
        $auxiliares = ['Cnaes', 'Motivos', 'Municipios', 'Naturezas', 'Paises', 'Qualificacoes'];
        sort($auxiliares);
        
        $fila = [];
        foreach ($auxiliares as $b) { $fila[] = "{$b}.zip"; }
        // ORDEM CRÍTICA: Estabelecimentos ANTES de Sócios
        for ($i = 0; $i <= 9; $i++) { $fila[] = "Estabelecimentos{$i}.zip"; }
        for ($i = 0; $i <= 9; $i++) { $fila[] = "Socios{$i}.zip"; }
        
        return $fila;
    }
}