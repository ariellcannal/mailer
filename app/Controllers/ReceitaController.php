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
            'activeMenu' => 'receita-index'
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
            'activeMenu' => 'receita-tasks',
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
        
        // Renderizar HTML usando a mesma partial da view principal
        $html = '';
        foreach ($tasks as $task) {
            $html .= view('receita/partials/_task_row', ['task' => $task]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'html' => $html
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
            $contactListName = $this->request->getPost('contact_list_name');
            $includeContabilidade = $this->request->getPost('include_contabilidade') == 1;
            
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
            
            // Lista de contatos (se fornecida)
            if (!empty($contactListName)) {
                $data['contact_list_name'] = $contactListName;
                $data['include_contabilidade'] = $includeContabilidade ? 1 : 0;
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
     * Retorna dados de uma tarefa para duplicação (carrega formulário)
     */
    public function duplicateTask($taskId)
    {
        try {
            $task = $this->taskModel->find((int) $taskId);
            
            if (!$task) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
            
            // Retornar dados da tarefa para preencher formulário
            return $this->response->setJSON([
                'success' => true,
                'task' => [
                    'name' => $task['name'],
                    'cnaes' => $task['cnaes'],
                    'ufs' => $task['ufs'],
                    'situacoes_fiscais' => $task['situacoes_fiscais']
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao carregar tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao carregar tarefa'
            ]);
        }
    }
    
    /**
     * Pausa uma tarefa (somente se em andamento)
     */
    public function pauseTask($taskId)
    {
        try {
            $task = $this->taskModel->find((int) $taskId);
            
            if (!$task) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
            
            if ($task['status'] !== 'em_andamento') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Apenas tarefas em andamento podem ser pausadas'
                ]);
            }
            
            $this->taskModel->update((int) $taskId, [
                'status' => 'agendada'
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarefa pausada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao pausar tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao pausar tarefa'
            ]);
        }
    }
    
    /**
     * Inicia uma tarefa (pausa outras e inicia a selecionada)
     */
    public function startTask($taskId)
    {
        try {
            $task = $this->taskModel->find((int) $taskId);
            
            if (!$task) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
            
            if ($task['status'] === 'concluida' || $task['status'] === 'erro') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefas concluídas ou com erro não podem ser iniciadas'
                ]);
            }
            
            // Pausar TODAS as outras tarefas (garantir apenas 1 em andamento)
            $this->taskModel->where('status', 'em_andamento')
                           ->where('id !=', (int) $taskId)
                           ->set(['status' => 'agendada'])
                           ->update();
            
            // Iniciar a tarefa selecionada
            $this->taskModel->update((int) $taskId, [
                'status' => 'em_andamento'
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarefa iniciada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao iniciar tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao iniciar tarefa'
            ]);
        }
    }
    
    /**
     * Reinicia uma tarefa (reseta status e apaga arquivo de progresso)
     */
    public function restartTask($taskId)
    {
        try {
            $task = $this->taskModel->find((int) $taskId);
            
            if (!$task) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
            
            // Resetar status e progresso
            $this->taskModel->update((int) $taskId, [
                'status' => 'agendada',
                'processed_files' => 0,
                'processed_lines' => 0,
                'imported_lines' => 0,
                'processed_bytes' => 0,
                'current_file' => null,
                'error_message' => null
            ]);
            
            // Apagar arquivo de progresso
            $basePath = FCPATH . '../' . rtrim(env('CNPJ_DOWNLOAD_PATH', 'writable/receita/'), '/') . '/';
            $processFile = $basePath . 'process_' . $taskId;
            if (file_exists($processFile)) {
                unlink($processFile);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Tarefa reiniciada com sucesso'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao reiniciar tarefa: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao reiniciar tarefa'
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
                'activeMenu' => 'receita-empresas',
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
    
    /**
     * Buscar listas de contatos (AJAX)
     */
    public function buscarListasContatos()
    {
        try {
            $term = $this->request->getGet('q');
            $db = \Config\Database::connect();
            $builder = $db->table('contact_lists');
            
            if (!empty($term)) {
                $builder->like('name', $term);
            }
            
            $listas = $builder
                ->select('id, name as text')
                ->orderBy('name', 'ASC')
                ->limit(20)
                ->get()
                ->getResultArray();
            
            return $this->response->setJSON($listas);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao buscar listas: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }
    
    /**
     * Adicionar empresas filtradas à lista de contatos
     */
    public function adicionarEmpresasALista()
    {
        try {
            $lists = $this->request->getPost('lists');
            $filters = $this->request->getPost('filters');
            
            if (empty($lists)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Nenhuma lista selecionada'
                ]);
            }
            
            $db = \Config\Database::connect();
            $contactModel = new \App\Models\ContactModel();
            $contactListModel = new \App\Models\ContactListModel();
            $contactListMemberModel = new \App\Models\ContactListMemberModel();
            
            // Buscar empresas com filtros (sem paginação)
            $builder = $db->table('receita_estabelecimentos');
            
            // Aplicar mesmos filtros da busca
            if (!empty($filters['nome'])) {
                $builder->like('nome_fantasia', $filters['nome']);
            }
            if (!empty($filters['cnpj_basico'])) {
                $builder->where('cnpj_basico', $filters['cnpj_basico']);
            }
            if (!empty($filters['cnae'])) {
                $builder->groupStart();
                foreach ($filters['cnae'] as $cnae) {
                    $builder->orWhere('cnae_fiscal_principal', $cnae);
                    $builder->orLike('cnae_fiscal_secundario', $cnae);
                }
                $builder->groupEnd();
            }
            if (!empty($filters['uf'])) {
                $builder->where('uf', $filters['uf']);
            }
            
            // Buscar apenas empresas com email válido
            $builder->where('correio_eletronico IS NOT NULL');
            $builder->where('correio_eletronico !=', '');
            
            $empresas = $builder->get()->getResultArray();
            
            if (empty($empresas)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Nenhuma empresa encontrada com os filtros selecionados'
                ]);
            }
            
            $totalAdicionados = 0;
            $listasProcessadas = [];
            
            // Processar cada lista
            foreach ($lists as $listId) {
                // Se começa com 'new:', é uma nova lista
                if (strpos($listId, 'new:') === 0) {
                    $listName = substr($listId, 4);
                    $newListId = $contactListModel->insert([
                        'name' => $listName,
                        'description' => 'Lista criada automaticamente via importação da Receita Federal',
                        'contact_count' => 0
                    ]);
                    $listId = $newListId;
                    $listasProcessadas[] = $listName;
                } else {
                    $lista = $contactListModel->find($listId);
                    if ($lista) {
                        $listasProcessadas[] = $lista['name'];
                    }
                }
                
                // Adicionar empresas à lista
                foreach ($empresas as $empresa) {
                    $email = $empresa['correio_eletronico'];
                    $nome = $empresa['nome_fantasia'] ?: '';
                    
                    // Criar ou atualizar contato
                    $contato = $contactModel->where('email', $email)->first();
                    
                    if ($contato) {
                        // Atualizar nome se estiver vazio
                        if (empty($contato['name']) && !empty($nome)) {
                            $contactModel->update($contato['id'], ['name' => $nome]);
                        }
                        $contactId = $contato['id'];
                    } else {
                        // Criar novo contato
                        $contactId = $contactModel->insert([
                            'name' => $nome,
                            'email' => $email
                        ]);
                    }
                    
                    // Verificar se já está na lista
                    $exists = $contactListMemberModel
                        ->where('contact_list_id', $listId)
                        ->where('contact_id', $contactId)
                        ->first();
                    
                    if (!$exists) {
                        $contactListMemberModel->insert([
                            'contact_list_id' => $listId,
                            'contact_id' => $contactId
                        ]);
                        $totalAdicionados++;
                    }
                }
                
                // Atualizar contador da lista
                $count = $contactListMemberModel
                    ->where('contact_list_id', $listId)
                    ->countAllResults();
                $contactListModel->update($listId, ['contact_count' => $count]);
            }
            
            $message = sprintf(
                '%d contato(s) adicionado(s) à lista(s): %s',
                $totalAdicionados,
                implode(', ', $listasProcessadas)
            );
            
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'total_adicionados' => $totalAdicionados
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Erro ao adicionar empresas à lista: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao adicionar empresas: ' . $e->getMessage()
            ]);
        }
    }
}
