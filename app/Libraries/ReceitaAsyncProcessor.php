<?php
declare(strict_types=1);

namespace App\Libraries;

use App\Models\ReceitaImportTaskModel;
use ZipArchive;

/**
 * Processador assíncrono para importação da Receita Federal
 * Gerencia lockfile, controle de tempo e processamento em lote
 */
class ReceitaAsyncProcessor
{
    private $basePath;
    private $lockFile;
    private $processFile;
    private $db;
    private $taskModel;
    private $processStart;
    private $maxExecutionTime = 55; // segundos
    private $currentTask;
    private $stats = [];
    
    /**
     * Construtor - Inicializa processador e registra timestamp
     */
    public function __construct()
    {
        // Registrar timestamp de início
        $this->processStart = time();
        
        $this->basePath = FCPATH . '../' . rtrim(env('CNPJ_DOWNLOAD_PATH', 'writable/receita/'), '/') . '/';
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
        
        $this->lockFile = FCPATH . '../receita.lock';
        $this->db = \Config\Database::connect();
        
        if (property_exists($this->db, 'saveQueries')) {
            $this->db->saveQueries = false;
        }
        
        $this->taskModel = new ReceitaImportTaskModel();
    }
    
    /**
     * Processa próxima tarefa agendada
     * 
     * @return array Resultado do processamento
     */
    /**
     * Processa uma tarefa específica por ID (usado pelo Command)
     * 
     * @param int $taskId ID da tarefa
     * @param int $timeLimit Tempo máximo de execução em segundos
     * @return array
     */
    public function processTaskById(int $taskId, int $timeLimit = 55): array
    {
        // Verificar lockfile
        if (!$this->acquireLock()) {
            return [
                'success' => false,
                'message' => 'Outro processo está em execução'
            ];
        }
        
        try {
            // Buscar tarefa específica
            $this->currentTask = $this->taskModel->find($taskId);
            
            if (!$this->currentTask) {
                $this->releaseLock();
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ];
            }
            
            if ($this->currentTask['status'] !== 'agendada' && $this->currentTask['status'] !== 'em_andamento') {
                $this->releaseLock();
                return [
                    'success' => false,
                    'message' => 'Tarefa não está disponível para processamento'
                ];
            }
            
            log_message('info', "Iniciando processamento da tarefa #{$this->currentTask['id']}");
            
            // Marcar como em andamento
            $this->taskModel->markAsInProgress($taskId);
            
            // Carregar progresso
            $this->processFile = $this->basePath . 'process_' . $this->currentTask['id'];
            $progress = $this->loadProgress();
            
            // Processar
            $result = $this->processTask($progress);
            
            // Verificar se concluiu
            if ($result['completed']) {
                $this->taskModel->markAsCompleted($taskId);
                $this->deleteProgressFile();
                log_message('info', "Tarefa #{$this->currentTask['id']} concluída");
            }
            
            $this->releaseLock();
            
            return [
                'success' => true,
                'task_id' => $this->currentTask['id'],
                'completed' => $result['completed'],
                'total_files' => $result['stats']['total_files'] ?? 0,
                'processed_files' => $result['stats']['processed_files'] ?? 0,
                'processed_lines' => $result['stats']['processed_lines'] ?? 0,
                'imported_lines' => $result['stats']['imported_lines'] ?? 0
            ];
            
        } catch (\Exception $e) {
            log_message('error', "Erro no processamento: " . $e->getMessage());
            
            if ($this->currentTask) {
                $this->taskModel->markAsError(
                    $taskId,
                    $e->getMessage()
                );
            }
            
            $this->releaseLock();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function processNextTask(): array
    {
        // Verificar lockfile
        if (!$this->acquireLock()) {
            return [
                'success' => false,
                'message' => 'Outro processo está em execução'
            ];
        }
        
        try {
            // Buscar próxima tarefa agendada
            $this->currentTask = $this->taskModel->getNextScheduledTask();
            
            if (!$this->currentTask) {
                $this->releaseLock();
                return [
                    'success' => false,
                    'message' => 'Nenhuma tarefa agendada'
                ];
            }
            
            log_message('info', "Iniciando processamento da tarefa #{$this->currentTask['id']}");
            
            // Marcar como em andamento
            $this->taskModel->markAsInProgress((int) $this->currentTask['id']);
            
            // Carregar progresso
            $this->processFile = $this->basePath . 'process_' . $this->currentTask['id'];
            $progress = $this->loadProgress();
            
            // Processar
            $result = $this->processTask($progress);
            
            // Verificar se concluiu
            if ($result['completed']) {
                $this->taskModel->markAsCompleted((int) $this->currentTask['id']);
                $this->deleteProgressFile();
                log_message('info', "Tarefa #{$this->currentTask['id']} concluída");
            }
            
            $this->releaseLock();
            
            return [
                'success' => true,
                'task_id' => $this->currentTask['id'],
                'completed' => $result['completed'],
                'stats' => $result['stats']
            ];
            
        } catch (\Exception $e) {
            log_message('error', "Erro no processamento: " . $e->getMessage());
            
            if ($this->currentTask) {
                $this->taskModel->markAsError(
                    (int) $this->currentTask['id'],
                    $e->getMessage()
                );
            }
            
            $this->releaseLock();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Adquire lockfile para controle de concorrência
     * 
     * @return bool
     */
    private function acquireLock(): bool
    {
        // Verificar se lockfile existe
        if (file_exists($this->lockFile)) {
            $lockData = json_decode(file_get_contents($this->lockFile), true);
            $pid = $lockData['pid'] ?? 0;
            
            // Verificar se processo ainda está rodando
            if ($this->isProcessRunning($pid)) {
                log_message('info', "Processo #{$pid} ainda em execução");
                return false;
            }
            
            // Lockfile órfão - remover
            log_message('warning', "Lockfile órfão detectado (PID: {$pid}), removendo...");
            unlink($this->lockFile);
        }
        
        // Criar novo lockfile
        $lockData = [
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
            'task_id' => $this->currentTask['id'] ?? null
        ];
        
        file_put_contents($this->lockFile, json_encode($lockData));
        log_message('info', "Lockfile criado (PID: " . getmypid() . ")");
        
        return true;
    }
    
    /**
     * Libera lockfile
     */
    private function releaseLock(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
            log_message('info', "Lockfile removido");
        }
    }
    
    /**
     * Verifica se processo está rodando
     * 
     * @param int $pid
     * @return bool
     */
    private function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }
        
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
            return strpos($output, (string) $pid) !== false;
        } else {
            return file_exists("/proc/{$pid}");
        }
    }
    
    /**
     * Carrega progresso do arquivo
     * 
     * @return array
     */
    private function loadProgress(): array
    {
        if (file_exists($this->processFile)) {
            $content = file_get_contents($this->processFile);
            $decoded = json_decode($content, true);
            return $decoded ?? ['ultimo_arquivo' => '', 'ultima_linha' => 0];
        }
        
        return ['ultimo_arquivo' => '', 'ultima_linha' => 0];
    }
    
    /**
     * Salva progresso no arquivo
     * 
     * @param string $arquivo
     * @param int $linha
     */
    private function saveProgress(string $arquivo, int $linha): void
    {
        $progress = [
            'ultimo_arquivo' => $arquivo,
            'ultima_linha' => $linha
        ];
        
        file_put_contents($this->processFile, json_encode($progress));
    }
    
    /**
     * Remove arquivo de progresso
     */
    private function deleteProgressFile(): void
    {
        if (file_exists($this->processFile)) {
            unlink($this->processFile);
            log_message('info', "Arquivo de progresso removido");
        }
    }
    
    /**
     * Verifica se tempo de execução foi excedido
     * 
     * @return bool
     */
    private function isTimeExceeded(): bool
    {
        $elapsed = time() - $this->processStart;
        return $elapsed >= $this->maxExecutionTime;
    }
    
    /**
     * Processa tarefa
     * 
     * @param array $progress
     * @return array
     */
    private function processTask(array $progress): array
    {
        set_time_limit(90);
        ini_set('memory_limit', '128M');
        gc_enable();
        
        $cnaes = json_decode($this->currentTask['cnaes'] ?? '[]', true);
        $ufs = json_decode($this->currentTask['ufs'] ?? '[]', true);
        
        $fila = $this->getFilaArquivos();
        $ordemFila = array_flip($fila);
        
        $completed = false;
        $filesProcessed = 0;
        $linesProcessed = 0;
        $linesImported = 0;
        $bytesProcessed = 0;
        
        // Calcular total de bytes
        $totalBytes = 0;
        foreach ($fila as $zipName) {
            $path = $this->basePath . $zipName;
            if (file_exists($path)) {
                $totalBytes += filesize($path);
            }
        }
        
        // Atualizar total de arquivos e bytes
        $this->taskModel->updateProgress((int) $this->currentTask['id'], [
            'total_files' => count($fila),
            'total_bytes' => $totalBytes
        ]);
        
        foreach ($fila as $zipName) {
            // Verificar tempo
            if ($this->isTimeExceeded()) {
                log_message('info', "Tempo limite atingido, salvando progresso...");
                break;
            }
            
            // Verificar se arquivo já foi processado
            if ($progress['ultimo_arquivo'] && $ordemFila[$zipName] < $ordemFila[$progress['ultimo_arquivo']]) {
                continue;
            }
            
            $path = $this->basePath . $zipName;
            if (!file_exists($path)) {
                continue;
            }
            
            $result = $this->processFile($zipName, $progress, $cnaes, $ufs);
            
            $filesProcessed++;
            $linesProcessed += $result['lines_processed'];
            $linesImported += $result['lines_imported'];
            
            // Adicionar bytes do arquivo processado
            if (file_exists($path)) {
                $bytesProcessed += filesize($path);
            }
            
            // Atualizar progresso no banco
            $this->taskModel->updateProgress((int) $this->currentTask['id'], [
                'processed_files' => $filesProcessed,
                'current_file' => $zipName,
                'processed_lines' => $linesProcessed,
                'imported_lines' => $linesImported,
                'processed_bytes' => $bytesProcessed
            ]);
            
            // Se não completou o arquivo, sair
            if (!$result['completed']) {
                break;
            }
            
            // Resetar progresso para próximo arquivo
            $progress = ['ultimo_arquivo' => '', 'ultima_linha' => 0];
        }
        
        // Verificar se todos os arquivos foram processados
        $completed = ($filesProcessed >= count($fila));
        
        return [
            'completed' => $completed,
            'stats' => [
                'total_files' => count($fila),
                'processed_files' => $filesProcessed,
                'processed_lines' => $linesProcessed,
                'imported_lines' => $linesImported
            ]
        ];
    }
    
    /**
     * Processa um arquivo ZIP
     * 
     * @param string $zipName
     * @param array $progress
     * @param array $cnaes
     * @param array $ufs
     * @return array
     */
    private function processFile(string $zipName, array $progress, array $cnaes, array $ufs): array
    {
        $path = $this->basePath . $zipName;
        $rawName = strtolower(preg_replace('/[0-9]|\.zip/', '', $zipName));
        $tableName = 'receita_' . $rawName;
        
        $linesProcessed = 0;
        $linesImported = 0;
        $completed = false;
        
        $zip = new ZipArchive;
        if ($zip->open($path) === TRUE) {
            $fp = $zip->getStream($zip->getNameIndex(0));
            
            $lineCount = 0;
            $batchData = [];
            $skipTo = ($zipName == $progress['ultimo_arquivo']) ? $progress['ultima_linha'] : 0;
            
            $fields = $this->db->getFieldNames($tableName);
            $this->db->transBegin();
            
            while (($line = fgets($fp)) !== FALSE) {
                // Verificar tempo a cada iteração
                if ($this->isTimeExceeded()) {
                    log_message('info', "Tempo excedido durante processamento de {$zipName}");
                    break;
                }
                
                $lineCount++;
                $linesProcessed++;
                
                if ($lineCount <= $skipTo) {
                    unset($line);
                    continue;
                }
                
                $data = str_getcsv($line, ';', '"');
                unset($line);
                
                // Filtro por CNAE e UF para estabelecimentos
                if ($rawName == 'estabelecimentos') {
                    // Filtro por CNAE
                    if (!empty($cnaes) && !in_array($data[11] ?? '', $cnaes)) {
                        unset($data);
                        continue;
                    }
                    
                    // Filtro por UF (coluna 0 = UF)
                    if (!empty($ufs) && !in_array($data[0] ?? '', $ufs)) {
                        unset($data);
                        continue;
                    }
                    
                    $data[] = $this->isContabilidade($data[27] ?? '') ? 1 : 0;
                }
                
                // Filtro de sócios
                if ($rawName == 'socios') {
                    $exists = $this->db->table('receita_estabelecimentos')
                        ->where('cnpj_basico', $data[0] ?? '')
                        ->countAllResults();
                    if ($exists === 0) {
                        unset($data);
                        continue;
                    }
                }
                
                $row = [];
                foreach ($fields as $idx => $fName) {
                    if (isset($data[$idx])) {
                        $value = $data[$idx];
                        // Converter encoding apenas se for string
                        if ($value !== null && is_string($value)) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                        }
                        $row[$fName] = $value;
                    }
                }
                $batchData[] = $row;
                unset($data);
                
                if (count($batchData) >= 500) {
                    $this->db->table($tableName)->ignore(true)->insertBatch($batchData);
                    $linesImported += count($batchData);
                    
                    unset($batchData);
                    $batchData = [];
                    
                    $this->db->transCommit();
                    $this->saveProgress($zipName, $lineCount);
                    gc_collect_cycles();
                    $this->db->transBegin();
                }
            }
            
            // Inserir restante
            if (!empty($batchData)) {
                $this->db->table($tableName)->ignore(true)->insertBatch($batchData);
                $linesImported += count($batchData);
            }
            
            $this->db->transCommit();
            $this->saveProgress($zipName, $lineCount);
            
            $zip->close();
            fclose($fp);
            
            // Arquivo completamente processado
            $completed = !$this->isTimeExceeded();
        }
        
        return [
            'completed' => $completed,
            'lines_processed' => $linesProcessed,
            'lines_imported' => $linesImported
        ];
    }
    
    /**
     * Verifica se email é de contabilidade
     * 
     * @param string $email
     * @return bool
     */
    private function isContabilidade(string $email): bool
    {
        if (empty($email) || strpos($email, '@') === false) {
            return false;
        }
        
        list($usuario, $dominio) = explode('@', strtolower($email));
        $termos = ['contabil', 'contabilidade', 'contabilista', 'assessoriacontabil'];
        $genericos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'uol.com.br'];
        
        if (in_array($dominio, $genericos)) {
            foreach ($termos as $t) {
                if (strpos($usuario, $t) !== false) {
                    return true;
                }
            }
        } else {
            foreach ($termos as $t) {
                if (strpos($dominio, $t) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Retorna fila de arquivos na ordem correta
     * 
     * @return array
     */
    private function getFilaArquivos(): array
    {
        $auxiliares = ['Cnaes', 'Motivos', 'Municipios', 'Naturezas', 'Paises', 'Qualificacoes'];
        sort($auxiliares);
        
        $fila = [];
        foreach ($auxiliares as $b) {
            $fila[] = "{$b}.zip";
        }
        
        // ORDEM CRÍTICA: Estabelecimentos ANTES de Sócios
        for ($i = 0; $i <= 9; $i++) {
            $fila[] = "Estabelecimentos{$i}.zip";
        }
        for ($i = 0; $i <= 9; $i++) {
            $fila[] = "Socios{$i}.zip";
        }
        
        return $fila;
    }
}
