<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Contact Import Model
 * 
 * Model para gerenciamento de importações de contatos
 */
class ContactImportModel extends Model
{
    protected $table = 'contact_imports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'filename',
        'filepath',
        'status',
        'total_rows',
        'processed_rows',
        'imported_count',
        'skipped_count',
        'error_count',
        'progress_percent',
        'email_column',
        'name_column',
        'list_ids',
        'error_message',
        'error_details',
        'started_at',
        'completed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Obtém importações com paginação
     * 
     * @param int $perPage
     * @return array
     */
    public function getImports(int $perPage = 20): array
    {
        return $this->orderBy('created_at', 'DESC')
            ->paginate($perPage);
    }
    
    /**
     * Obtém próxima importação pendente para processar
     * 
     * @return array|null
     */
    public function getNextPending(): ?array
    {
        return $this->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->first();
    }
    
    /**
     * Marca importação como processando
     * 
     * @param int $id
     * @return bool
     */
    public function markAsProcessing(int $id): bool
    {
        return $this->update($id, [
            'status' => 'processing',
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Atualiza progresso da importação
     * 
     * @param int $id
     * @param int $processedRows
     * @param int $totalRows
     * @param int $imported
     * @param int $skipped
     * @param int $errors
     * @return bool
     */
    public function updateProgress(int $id, int $processedRows, int $totalRows, int $imported, int $skipped, int $errors): bool
    {
        $progress = $totalRows > 0 ? round(($processedRows / $totalRows) * 100, 2) : 0;
        
        return $this->update($id, [
            'processed_rows' => $processedRows,
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'error_count' => $errors,
            'progress_percent' => $progress,
        ]);
    }
    
    /**
     * Marca importação como concluída
     * 
     * @param int $id
     * @param array $result
     * @return bool
     */
    public function markAsCompleted(int $id, array $result): bool
    {
        return $this->update($id, [
            'status' => 'completed',
            'imported_count' => $result['imported'] ?? 0,
            'skipped_count' => $result['skipped'] ?? 0,
            'error_count' => count($result['errors'] ?? []),
            'progress_percent' => 100.00,
            'error_details' => !empty($result['errors']) ? json_encode($result['errors']) : null,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Marca importação como falha
     * 
     * @param int $id
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(int $id, string $errorMessage): bool
    {
        return $this->update($id, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
