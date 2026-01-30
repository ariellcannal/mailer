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

    public function run(array $params)
    {
        // Otimizações de memória e CPU
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        gc_enable();
        
        $db = \Config\Database::connect();
        if (property_exists($db, 'saveQueries')) {
            $db->saveQueries = false;
        }

        $importModel = new ContactImportModel();
        $contactModel = new ContactModel();

        CLI::write('Iniciando processamento de importações...', 'green');

        while (true) {
            // Buscar próxima importação pendente
            $import = $importModel->getNextPending();

            if (!$import) {
                CLI::write('Nenhuma importação pendente encontrada.', 'yellow');
                break;
            }

            CLI::write("Processando importação #{$import['id']}: {$import['filename']}", 'cyan');

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

                // Processar linhas
                foreach ($rows as $index => $row) {
                    if ($index === 0) {
                        continue; // Pular header
                    }

                    $processedRows++;

                    $email = trim((string) ($row[$emailIndex] ?? ''));

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
                        
                        // Atualizar progresso
                        $importModel->updateProgress(
                            $import['id'],
                            $processedRows,
                            $totalRows,
                            $result['imported'],
                            $result['skipped'],
                            count($result['errors'])
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
                    unset($contacts);
                    gc_collect_cycles();
                }

                // Resultado final
                $finalResult = [
                    'imported' => $result['imported'] ?? 0,
                    'skipped' => $result['skipped'] ?? 0,
                    'errors' => $result['errors'] ?? [],
                ];

                // Marcar como concluído
                $importModel->markAsCompleted($import['id'], $finalResult);

                // Remover arquivo temporário
                if (file_exists($import['filepath'])) {
                    @unlink($import['filepath']);
                }

                CLI::write("Importação #{$import['id']} concluída com sucesso!", 'green');
                CLI::write("Importados: {$finalResult['imported']}, Ignorados: {$finalResult['skipped']}, Erros: " . count($finalResult['errors']), 'cyan');

            } catch (\Exception $e) {
                // Marcar como falha
                $importModel->markAsFailed($import['id'], $e->getMessage());
                CLI::error("Erro ao processar importação #{$import['id']}: " . $e->getMessage());
            }
        }

        CLI::write('Processamento finalizado.', 'green');
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
