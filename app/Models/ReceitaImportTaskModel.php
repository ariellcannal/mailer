<?php
declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model para gerenciar tarefas de importação da Receita Federal
 */
class ReceitaImportTaskModel extends Model
{
    protected $table = 'receita_import_tasks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'name',
        'cnaes',
        'ufs',
        'situacoes_fiscais',
        'status',
        'total_files',
        'processed_files',
        'current_file',
        'total_lines',
        'processed_lines',
        'imported_lines',
        'error_message',
        'started_at',
        'completed_at',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $deletedField = null;

    // Validation
    protected $validationRules = [
        'status' => 'required|in_list[agendada,em_andamento,concluida,erro]',
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Busca próxima tarefa agendada ou em andamento para processar
     * Prioriza tarefas em_andamento (que foram interrompidas) antes das agendadas
     * 
     * @return array|null
     */
    public function getNextScheduledTask(): ?array
    {
        // Primeiro tenta buscar tarefa em_andamento (interrompida)
        $task = $this->where('status', 'em_andamento')
            ->orderBy('started_at', 'ASC')
            ->first();
        
        // Se não houver em_andamento, busca próxima agendada
        if (!$task) {
            $task = $this->where('status', 'agendada')
                ->orderBy('created_at', 'ASC')
                ->first();
        }
        
        return $task;
    }

    /**
     * Marca tarefa como em andamento
     * 
     * @param int $taskId
     * @return bool
     */
    public function markAsInProgress(int $taskId): bool
    {
        return $this->update($taskId, [
            'status' => 'em_andamento',
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marca tarefa como concluída
     * 
     * @param int $taskId
     * @return bool
     */
    public function markAsCompleted(int $taskId): bool
    {
        return $this->update($taskId, [
            'status' => 'concluida',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marca tarefa como erro
     * 
     * @param int $taskId
     * @param string $errorMessage
     * @return bool
     */
    public function markAsError(int $taskId, string $errorMessage): bool
    {
        return $this->update($taskId, [
            'status' => 'erro',
            'error_message' => $errorMessage,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Atualiza progresso da tarefa
     * 
     * @param int $taskId
     * @param array $progress
     * @return bool
     */
    public function updateProgress(int $taskId, array $progress): bool
    {
        $data = [];
        
        if (isset($progress['processed_files'])) {
            $data['processed_files'] = $progress['processed_files'];
        }
        
        if (isset($progress['current_file'])) {
            $data['current_file'] = $progress['current_file'];
        }
        
        if (isset($progress['processed_lines'])) {
            $data['processed_lines'] = $progress['processed_lines'];
        }
        
        if (isset($progress['imported_lines'])) {
            $data['imported_lines'] = $progress['imported_lines'];
        }
        
        if (isset($progress['total_lines'])) {
            $data['total_lines'] = $progress['total_lines'];
        }
        
        if (isset($progress['total_files'])) {
            $data['total_files'] = $progress['total_files'];
        }
        
        return empty($data) ? true : $this->update($taskId, $data);
    }

    /**
     * Duplica uma tarefa (copia filtros e nome)
     * 
     * @param int $taskId
     * @return int|false ID da nova tarefa ou false
     */
    public function duplicateTask(int $taskId)
    {
        $task = $this->find($taskId);
        
        if (!$task) {
            return false;
        }
        
        $newTask = [
            'name' => ($task['name'] ?? 'Importação') . ' - Cópia ' . date('d/m/Y H:i'),
            'cnaes' => $task['cnaes'],
            'ufs' => $task['ufs'],
            'situacoes_fiscais' => $task['situacoes_fiscais'] ?? '02,03',
            'status' => 'agendada',
        ];
        
        return $this->insert($newTask);
    }

    /**
     * Calcula percentual de progresso
     * 
     * @param array $task
     * @return float
     */
    public function calculateProgress(array $task): float
    {
        $totalLines = (int) ($task['total_lines'] ?? 0);
        $processedLines = (int) ($task['processed_lines'] ?? 0);
        
        if ($totalLines === 0) {
            return 0.0;
        }
        
        return round(($processedLines / $totalLines) * 100, 2);
    }
}
