<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ReceitaImportTaskModel;
use App\Libraries\ReceitaAsyncProcessor;

/**
 * Controller para gerenciar importações da Receita Federal
 * com processamento assíncrono via CRON
 */
class ReceitaController extends BaseController
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
            $situacoes = $this->request->getPost('situacoes') ?? ['02', '03']; // Padrão: ATIVA e SUSPENSA
            
            // Garantir que arrays vazios sejam null para evitar erro SQL
            $cnaesJson = !empty($cnaes) ? json_encode($cnaes) : null;
            $ufsJson = !empty($ufs) ? json_encode($ufs) : null;
            $situacoesStr = !empty($situacoes) ? implode(',', $situacoes) : '02,03';
            
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
            // Situações fiscais sempre incluir (tem padrão)
            $data['situacoes_fiscais'] = $situacoesStr;
            
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
    
    /**
     * Página de consulta de empresas importadas
     */
    public function empresas()
    {
        return view('receita/empresas', [
            'pageTitle' => 'Consultar Empresas',
            'activeMenu' => 'receita'
        ]);
    }
    
    /**
     * Buscar empresas com filtros e paginação
     */
    public function buscarEmpresas()
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('receita_estabelecimentos');
            
            // Filtros
            $nome = $this->request->getGet('nome');
            $cnpjBasico = $this->request->getGet('cnpj_basico');
            $cnaes = $this->request->getGet('cnae') ?? [];
            $uf = $this->request->getGet('uf');
            
            // Aplicar filtros
            if (!empty($nome)) {
                $builder->like('nome_fantasia', $nome);
            }
            
            if (!empty($cnpjBasico)) {
                $builder->where('cnpj_basico', $cnpjBasico);
            }
            
            if (!empty($cnaes)) {
                $builder->groupStart();
                foreach ($cnaes as $cnae) {
                    $builder->orWhere('cnae_fiscal_principal', $cnae);
                    $builder->orLike('cnae_fiscal_secundario', $cnae);
                }
                $builder->groupEnd();
            }
            
            if (!empty($uf)) {
                $builder->where('uf', $uf);
            }
            
            // Paginação
            $perPage = 20;
            $page = (int) ($this->request->getGet('page') ?? 1);
            $offset = ($page - 1) * $perPage;
            
            // Total de registros
            $total = $builder->countAllResults(false);
            
            // Buscar dados
            $empresas = $builder
                ->select('cnpj_basico, cnpj_ordem, cnpj_dv, nome_fantasia, ddd1, telefone1, ddd2, telefone2, ddd_fax, fax, correio_eletronico')
                ->limit($perPage, $offset)
                ->get()
                ->getResultArray();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $empresas,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao buscar empresas: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao buscar empresas: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Detalhes da empresa com sócios
     */
    public function empresa($cnpjBasico, $cnpjOrdem, $cnpjDv)
    {
        try {
            $db = \Config\Database::connect();
            
            // Buscar estabelecimento
            $estabelecimento = $db->table('receita_estabelecimentos')
                ->where('cnpj_basico', $cnpjBasico)
                ->where('cnpj_ordem', $cnpjOrdem)
                ->where('cnpj_dv', $cnpjDv)
                ->get()
                ->getRowArray();
            
            if (!$estabelecimento) {
                throw new \Exception('Empresa não encontrada');
            }
            
            // Buscar empresa (dados da matriz)
            $empresa = $db->table('receita_empresas')
                ->where('cnpj_basico', $cnpjBasico)
                ->get()
                ->getRowArray();
            
            // Buscar sócios
            $socios = $db->table('receita_socios')
                ->where('cnpj_basico', $cnpjBasico)
                ->get()
                ->getResultArray();
            
            return view('receita/empresa_detalhes', [
                'pageTitle' => 'Detalhes da Empresa',
                'activeMenu' => 'receita',
                'estabelecimento' => $estabelecimento,
                'empresa' => $empresa,
                'socios' => $socios
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao buscar detalhes da empresa: ' . $e->getMessage());
            return redirect()->to(base_url('receita/empresas'))
                ->with('error', 'Empresa não encontrada');
        }
    }
}
