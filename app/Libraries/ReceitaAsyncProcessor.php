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
        $situacoes = !empty($this->currentTask['situacoes_fiscais']) 
            ? explode(',', $this->currentTask['situacoes_fiscais']) 
            : ['02', '03']; // Padrão: ATIVA e SUSPENSA
        
        // Criar lista de contatos se fornecida
        $contactListId = null;
        $includeContabilidade = true;
        if (!empty($this->currentTask['contact_list_name'])) {
            $contactListId = $this->createContactList(
                $this->currentTask['contact_list_name'],
                $this->currentTask['id']
            );
            $includeContabilidade = !empty($this->currentTask['include_contabilidade']);
            
            // Salvar ID da lista na tarefa
            if ($contactListId) {
                $this->taskModel->update($this->currentTask['id'], [
                    'contact_list_id' => $contactListId
                ]);
                log_message('info', "Lista de contatos #{$contactListId} criada para tarefa #{$this->currentTask['id']}");
            }
        }
        
        $fila = $this->getFilaArquivos();
        $ordemFila = array_flip($fila);
        
        $completed = false;
        $filesProcessed = 0;
        $linesProcessed = 0;
        $linesImported = 0;
        $bytesProcessed = 0;
        
        // Calcular total de bytes (tamanho descompactado dos CSVs)
        $totalBytes = 0;
        foreach ($fila as $zipName) {
            $path = $this->basePath . $zipName;
            if (file_exists($path)) {
                $zip = new \ZipArchive();
                if ($zip->open($path) === TRUE) {
                    // Obter tamanho descompactado do primeiro arquivo (CSV)
                    $stat = $zip->statIndex(0);
                    if ($stat !== false) {
                        $totalBytes += $stat['size']; // Tamanho descompactado
                    }
                    $zip->close();
                }
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
            
            $result = $this->processFile($zipName, $progress, $cnaes, $ufs, $situacoes, $contactListId, $includeContabilidade);
            
            $linesProcessed += $result['lines_processed'];
            $linesImported += $result['lines_imported'];
            $bytesProcessed += $result['bytes_processed']; // Acumular bytes das linhas processadas
            
            // Se completou o arquivo, incrementar contador de arquivos
            if ($result['completed']) {
                $filesProcessed++;
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
    private function processFile(string $zipName, array $progress, array $cnaes, array $ufs, array $situacoes = ['02', '03'], ?int $contactListId = null, bool $includeContabilidade = true): array
    {
        $path = $this->basePath . $zipName;
        $rawName = strtolower(preg_replace('/[0-9]|\.zip/', '', $zipName));
        $tableName = 'receita_' . $rawName;
        
        $linesProcessed = 0;
        $linesImported = 0;
        $bytesProcessed = 0;
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
                $bytesProcessed += strlen($line); // Contar bytes da linha processada
                
                if ($lineCount <= $skipTo) {
                    unset($line);
                    continue;
                }
                
                $data = str_getcsv($line, ';', '"');
                unset($line);
                
                // Filtro por CNAE, UF e Situação Fiscal para estabelecimentos
                if ($rawName == 'estabelecimentos') {
                    // Filtro por CNAE (coluna 11 = cnae_fiscal_principal)
                    if (!empty($cnaes) && !in_array($data[11] ?? '', $cnaes)) {
                        unset($data);
                        continue;
                    }
                    
                    // Filtro por UF (coluna 0 = UF)
                    if (!empty($ufs) && !in_array($data[0] ?? '', $ufs)) {
                        unset($data);
                        continue;
                    }
                    
                    // Filtro por Situação Fiscal (coluna 6 = situacao_cadastral, índice 5)
                    // Valores no arquivo: 01=NULA, 02=ATIVA, 03=SUSPENSA, 04=INAPTA, 08=BAIXADA (2 dígitos)
                    // Valores no filtro: 1, 2, 3, 4, 8 (1 dígito) ou 01, 02, 03, 04, 08 (2 dígitos)
                    if (!empty($situacoes)) {
                        $situacaoArquivo = $data[5] ?? '';
                        // Normalizar para 2 dígitos com zero à esquerda
                        $situacoesNormalizadas = array_map(function($s) {
                            return str_pad($s, 2, '0', STR_PAD_LEFT);
                        }, $situacoes);
                        
                        if (!in_array($situacaoArquivo, $situacoesNormalizadas)) {
                            unset($data);
                            continue;
                        }
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
                        // Converter string vazia em NULL para correio_eletronico
                        if ($fName === 'correio_eletronico' && trim($value) === '') {
                            $value = null;
                        }
                        $row[$fName] = $value;
                    }
                }
                $batchData[] = $row;
                unset($data);
                
                if (count($batchData) >= 500) {
                    $batchSize = count($batchData);
                    $this->db->table($tableName)->ignore(true)->insertBatch($batchData);
                    
                    // Contar apenas estabelecimentos
                    if ($rawName == 'estabelecimentos') {
                        $linesImported += $batchSize;
                    }
                    
                    // Criar/atualizar contatos se lista foi fornecida
                    if ($contactListId && $rawName == 'estabelecimentos') {
                        $this->processContactsFromBatch($batchData, $contactListId, $includeContabilidade);
                    }
                    
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
                $batchSize = count($batchData);
                $this->db->table($tableName)->ignore(true)->insertBatch($batchData);
                
                // Contar apenas estabelecimentos
                if ($rawName == 'estabelecimentos') {
                    $linesImported += $batchSize;
                }
                
                // Criar/atualizar contatos se lista foi fornecida
                if ($contactListId && $rawName == 'estabelecimentos') {
                    $this->processContactsFromBatch($batchData, $contactListId, $includeContabilidade);
                }
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
            'lines_imported' => $linesImported,
            'bytes_processed' => $bytesProcessed
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
    
    /**
     * Cria uma lista de contatos
     * 
     * @param string $name Nome da lista
     * @param int $taskId ID da tarefa
     * @return int|null ID da lista criada
     */
    private function createContactList(string $name, int $taskId): ?int
    {
        $listModel = new \App\Models\ContactListModel();
        
        $data = [
            'name' => $name,
            'description' => "Lista criada automaticamente pela importação da Receita Federal (Tarefa #{$taskId})",
            'total_contacts' => 0
        ];
        
        $listId = $listModel->insert($data);
        
        return $listId ? (int) $listId : null;
    }
    
    /**
     * Processa contatos de um batch de estabelecimentos
     * 
     * @param array $batchData Dados do batch
     * @param int $contactListId ID da lista de contatos
     * @param bool $includeContabilidade Se deve incluir contatos de contabilidade
     */
    private function processContactsFromBatch(array $batchData, int $contactListId, bool $includeContabilidade): void
    {
        $contactModel = new \App\Models\ContactModel();
        $memberModel = new \App\Models\ContactListMemberModel();
        
        foreach ($batchData as $row) {
            // Verificar se tem email
            $email = trim($row['correio_eletronico'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            
            // Verificar se é contabilidade
            $isContabilidade = !empty($row['is_contabilidade']);
            if ($isContabilidade && !$includeContabilidade) {
                continue; // Pular contatos de contabilidade se não deve incluir
            }
            
            // Criar/atualizar contato
            $contactData = [
                'email' => $email,
                'name' => trim($row['nome_fantasia'] ?? ''),
                'is_active' => 1
            ];
            
            // Verificar se contato já existe
            $existingContact = $contactModel->where('email', $email)->first();
            
            if ($existingContact) {
                $contactId = $existingContact['id'];
                // Atualizar nome se estiver vazio
                if (empty($existingContact['name']) && !empty($contactData['name'])) {
                    $contactModel->update($contactId, ['name' => $contactData['name']]);
                }
            } else {
                // Criar novo contato
                try {
                    $contactId = $contactModel->insert($contactData, false); // false = não validar (email pode duplicar)
                    if (!$contactId) {
                        continue; // Falhou ao criar, pular
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Erro ao criar contato: ' . $e->getMessage());
                    continue;
                }
            }
            
            // Adicionar à lista (se ainda não estiver)
            $existingMember = $memberModel
                ->where('contact_id', $contactId)
                ->where('list_id', $contactListId)
                ->first();
            
            if (!$existingMember) {
                $memberModel->insert([
                    'contact_id' => $contactId,
                    'list_id' => $contactListId,
                    'added_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Atualizar contador da lista
        $listModel = new \App\Models\ContactListModel();
        $listModel->refreshCounters([$contactListId]);
    }
}
