<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ReceitaImportTaskModel;
use App\Libraries\ReceitaAsyncProcessor;

/**
 * Controller para gerenciar importações da Receita Federal
 * com processamento assíncrono via CRON
 */
class ReceitaController extends Controller
{
    private $taskModel;
    
    public function __construct()
    {
        $this->taskModel = new ReceitaImportTaskModel();
    }
    
    /**
     * Página principal de configuração
     */
    public function index()
    {
        return view('receita/index', [
            'pageTitle' => 'Importação Receita Federal',
            'activeMenu' => 'receita'
        ]);
    }
    
    /**
     * Página de listagem de tarefas
     */
    public function tasks()
    {
        $tasks = $this->taskModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        return view('receita/tasks', [
            'pageTitle' => 'Tarefas de Importação',
            'activeMenu' => 'receita',
            'tasks' => $tasks
        ]);
    }
    
    /**
     * Retorna dados das tarefas em JSON (para auto-refresh)
     */
    public function tasksData()
    {
        $tasks = $this->taskModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        return $this->response->setJSON([
            'success' => true,
            'tasks' => $tasks
        ]);
    }
    
    /**
     * Agenda nova tarefa de importação
     */
    public function schedule()
    {
        try {
            $name = $this->request->getPost('task_name');
            $cnaes = $this->request->getPost('cnaes') ?? [];
            $ufs = $this->request->getPost('ufs') ?? [];
            
            // Garantir que arrays vazios sejam null para evitar erro SQL
            $cnaesJson = !empty($cnaes) ? json_encode($cnaes) : null;
            $ufsJson = !empty($ufs) ? json_encode($ufs) : null;
            
            $data = [
                'name' => $name ?: 'Importação ' . date('d/m/Y H:i'),
                'status' => 'agendada',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Adicionar apenas se não for null
            if ($cnaesJson !== null) {
                $data['cnaes'] = $cnaesJson;
            }
            if ($ufsJson !== null) {
                $data['ufs'] = $ufsJson;
            }
            
            $taskId = $this->taskModel->insert($data);
            
            if ($taskId) {
                return $this->response->setJSON([
                    'success' => true,
                    'task_id' => $taskId,
                    'message' => 'Tarefa agendada com sucesso'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao agendar tarefa'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao agendar tarefa: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            // Retornar mensagem de erro detalhada para debug
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao agendar tarefa',
                'error_detail' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }
    
    /**
     * Duplica uma tarefa
     */
    public function duplicateTask($taskId)
    {
        try {
            $newTaskId = $this->taskModel->duplicateTask((int) $taskId);
            
            if ($newTaskId) {
                return $this->response->setJSON([
                    'success' => true,
                    'task_id' => $newTaskId,
                    'message' => 'Tarefa duplicada com sucesso'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tarefa não encontrada'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao duplicar tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao duplicar tarefa'
            ]);
        }
    }
    
    /**
     * Exclui uma tarefa (somente se agendada)
     */
    public function deleteTask($taskId)
    {
        try {
            $task = $this->taskModel->find((int) $taskId);
            
            if (!$task) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
            
            if ($task['status'] !== 'agendada') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Apenas tarefas agendadas podem ser excluídas'
                ]);
            }
            
            $this->taskModel->delete((int) $taskId);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarefa excluída com sucesso'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao excluir tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao excluir tarefa'
            ]);
        }
    }
    

    /**
     * Busca AJAX para o Select2 de CNAEs
     */
    public function buscarCnaes()
    {
        $term = $this->request->getGet('q');
        $db = \Config\Database::connect();
        
        $builder = $db->table('receita_cnaes');
        $builder->select('codigo as id, CONCAT(codigo, " - ", descricao) as text');
        $builder->like('codigo', $term)->orLike('descricao', $term);
        $results = $builder->limit(20)->get()->getResultArray();
        
        return $this->response->setJSON($results);
    }
}
