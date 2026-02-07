<?php

namespace App\Commands;

use App\Models\ContactImportModel;
use App\Models\ContactModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Comando para processar fila de importações de contatos
 * 
 * Uso: php spark contacts:import-process
 */
class ProcessContactImports extends BaseCommand
{
    protected $group = 'Contacts';
    protected $name = 'contacts:import-process';
    protected $description = 'Processa fila de importações de contatos pendentes';
    
    private $lockFile;
    private $maxIterations = 5; // Máximo de importações por execução

    public function run(array $params)
    {
        // ========================================
        // OTIMIZAÇÕES DE RECURSOS PARA t3a.small
        // ========================================
        
        // 1. Reduzir prioridade do processo
        if (function_exists('proc_nice')) {
            proc_nice(10);
        }
        
        // 2. Limites de recursos
        set_time_limit(60);
        ini_set('memory_limit', '256M'); // Aumentado para suportar planilhas grandes
        gc_enable();
        
        // 3. Desativar logs de queries
        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }
        
        // 4. Lockfile para evitar execuções concorrentes
        $this->lockFile = WRITEPATH . 'contact_import_process.lock';
        
        if ($this->isLocked()) {
            CLI::write('Processo já em execução. Saindo...', 'yellow');
            return;
        }
        
        $this->createLock();

        try {
            $importModel = new ContactImportModel();
            $contactModel = new ContactModel();

            CLI::write('Iniciando processamento de importações...', 'green');
            
            $iterations = 0;

            while ($iterations < $this->maxIterations) {
                // Buscar próxima importação pendente
                $import = $importModel->getNextPending();

                if (!$import) {
                    CLI::write('Nenhuma importação pendente encontrada.', 'yellow');
                    break;
                }

                CLI::write("Processando importação #{$import['id']}: {$import['filename']}", 'cyan');
                
                // Log de memória inicial
                $memStart = memory_get_usage(true);

                try {
                    // Marcar como processando
                    $importModel->markAsProcessing($import['id']);

                    // Carregar arquivo
                    if (!file_exists($import['filepath'])) {
                        throw new \RuntimeException('Arquivo não encontrado: ' . $import['filepath']);
                    }

                    $spreadsheet = IOFactory::load($import['filepath']);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    if (empty($rows)) {
                        throw new \RuntimeException('Arquivo vazio');
                    }

                    $headers = array_map('trim', $rows[0]);
                    $emailIndex = (int) $import['email_column'];
                    $nameIndex = $import['name_column'] !== null ? (int) $import['name_column'] : null;
                    $listIds = !empty($import['list_ids']) ? json_decode($import['list_ids'], true) : [];

                    $contacts = [];
                    $skippedReasons = [];
                    $processedRows = 0;
                    $totalRows = count($rows) - 1;
                    
                    // Acumuladores de resultados
                    $totalImported = 0;
                    $totalSkipped = 0;
                    $totalErrors = [];

                    // Processar linhas
                    foreach ($rows as $index => $row) {
                        if ($index === 0) {
                            continue; // Pular header
                        }

                        $processedRows++;

                        $email = strtolower(trim((string) ($row[$emailIndex] ?? '')));

                        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $skippedReasons[] = "Linha {$index}: email inválido";
                            unset($row);
                            continue;
                        }

                        $name = $nameIndex !== null ? trim((string) ($row[$nameIndex] ?? '')) : null;
                        $formattedName = $this->formatNameUcFirst($name);

                        $contacts[] = [
                            'email' => $email,
                            'name' => $formattedName,
                        ];

                        unset($row);

                        // Processar em lotes de 500
                        if (count($contacts) >= 500) {
                            $result = $contactModel->importContactsBatch($contacts, $listIds);
                            
                            // Acumular resultados
                            $totalImported += $result['imported'];
                            $totalSkipped += $result['skipped'];
                            $totalErrors = array_merge($totalErrors, $result['errors']);
                            
                            // Atualizar progresso
                            $importModel->updateProgress(
                                $import['id'],
                                $processedRows,
                                $totalRows,
                                $totalImported,
                                $totalSkipped,
                                count($totalErrors)
                            );

                            CLI::write("Progresso: {$processedRows}/{$totalRows} linhas processadas", 'yellow');

                            unset($contacts);
                            $contacts = [];
                            gc_collect_cycles();
                        }
                    }

                    // Processar lote restante
                    if (!empty($contacts)) {
                        $result = $contactModel->importContactsBatch($contacts, $listIds);
                        
                        // Acumular resultados do último lote
                        $totalImported += $result['imported'];
                        $totalSkipped += $result['skipped'];
                        $totalErrors = array_merge($totalErrors, $result['errors']);
                        
                        unset($contacts);
                        gc_collect_cycles();
                    }

                    // Resultado final acumulado
                    $finalResult = [
                        'imported' => $totalImported,
                        'skipped' => $totalSkipped,
                        'errors' => $totalErrors,
                    ];

                    // Marcar como concluído
                    $importModel->markAsCompleted($import['id'], $finalResult);

                    // Remover arquivo temporário
                    if (file_exists($import['filepath'])) {
                        @unlink($import['filepath']);
                    }
                    
                    // Liberar memória do spreadsheet
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet, $sheet, $rows, $headers);
                    gc_collect_cycles();

                    CLI::write("Importação #{$import['id']} concluída com sucesso!", 'green');
                    CLI::write("Importados: {$finalResult['imported']}, Ignorados: {$finalResult['skipped']}, Erros: " . count($finalResult['errors']), 'cyan');
                    
                    // Log de memória
                    $memEnd = memory_get_usage(true);
                    CLI::write('Memória usada: ' . $this->formatBytes($memEnd - $memStart), 'cyan');

                } catch (\Exception $e) {
                    // Marcar como falha
                    $importModel->markAsFailed($import['id'], $e->getMessage());
                    CLI::error("Erro ao processar importação #{$import['id']}: " . $e->getMessage());
                }
                
                $iterations++;
            }
            
            if ($iterations >= $this->maxIterations) {
                CLI::write("Limite de {$this->maxIterations} importações atingido. Próxima execução processará as restantes.", 'yellow');
            }

        } catch (\Exception $e) {
            CLI::error('Erro crítico: ' . $e->getMessage());
            log_message('error', 'ProcessContactImports error: ' . $e->getMessage());
        } finally {
            $this->removeLock();
            gc_collect_cycles();
        }

        CLI::write('Processamento finalizado.', 'green');
    }

    /**
     * Verifica se há um lock ativo
     */
    private function isLocked(): bool
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }
        
        $lockData = json_decode(file_get_contents($this->lockFile), true);
        $pid = $lockData['pid'] ?? 0;
        
        // Verificar se o PID ainda existe
        if (function_exists('posix_getpgid')) {
            if (posix_getpgid($pid) === false) {
                CLI::write("Detectado lock órfão (PID $pid inexistente). Removendo...", 'cyan');
                unlink($this->lockFile);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Cria lockfile
     */
    private function createLock(): void
    {
        $lockData = [
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
            'command' => 'contacts:import-process'
        ];
        file_put_contents($this->lockFile, json_encode($lockData));
    }
    
    /**
     * Remove lockfile
     */
    private function removeLock(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    /**
     * Formata bytes para leitura humana
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Formata o nome para que a primeira letra seja maiúscula
     * 
     * @param string|null $name
     * @return string|null
     */
    protected function formatNameUcFirst(?string $name): ?string
    {
        $trimmed = trim((string) $name);

        if ($trimmed === '') {
            return null;
        }

        $firstChar = mb_strtoupper(mb_substr($trimmed, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_substr($trimmed, 1, null, 'UTF-8');

        return $firstChar . $rest;
    }
}
