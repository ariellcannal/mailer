<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactListModel;
use App\Models\ContactListMemberModel;
use App\Models\ContactModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador para gerenciamento de listas de contatos.
 */
class ContactListController extends BaseController
{
    /**
     * Exibe as listas cadastradas.
     *
     * @return string
     */
    public function index(): string
    {
        $model = new ContactListModel();

        return view('contact_lists/index', [
            'lists' => $model->orderBy('name', 'ASC')->findAll(),
            'activeMenu' => 'contact_lists',
            'pageTitle' => 'Listas de Contatos',
        ]);
    }

    /**
     * Exibe o formulário de criação de listas.
     *
     * @return string
     */
    public function create(): string
    {
        return view('contact_lists/entry', [
            'list' => null,
            'activeMenu' => 'contact_lists',
            'pageTitle' => 'Nova Lista',
        ]);
    }

    /**
     * Exibe o formulário de edição de uma lista.
     *
     * @param int $id Identificador da lista.
     * @return string|RedirectResponse
     */
    public function edit(int $id)
    {
        $model = new ContactListModel();
        $list = $model->find($id);

        if ($list === null) {
            return redirect()->to('/contact-lists')->with('contact_lists_error', 'Lista não encontrada.');
        }

        return view('contact_lists/entry', [
            'list' => $list,
            'activeMenu' => 'contact_lists',
            'pageTitle' => 'Editar Lista',
        ]);
    }

    /**
     * Cria uma nova lista.
     *
     * @return RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $name = (string) $this->request->getPost('name');
        $description = (string) $this->request->getPost('description');

        if ($name === '') {
            return redirect()->back()->with('contact_lists_error', 'O nome da lista é obrigatório.');
        }

        $model = new ContactListModel();
        $model->insert([
            'name' => $name,
            'description' => $description !== '' ? $description : null,
        ]);

        return redirect()->to('/contact-lists')->with('contact_lists_success', 'Lista criada com sucesso.');
    }

    /**
     * Atualiza dados da lista selecionada.
     *
     * @param int $id Identificador da lista.
     * @return RedirectResponse
     */
    public function update(int $id): RedirectResponse
    {
        $name = (string) $this->request->getPost('name');
        $description = (string) $this->request->getPost('description');

        if ($name === '') {
            return redirect()->back()->with('contact_lists_error', 'O nome da lista é obrigatório.');
        }

        $model = new ContactListModel();
        $model->update($id, [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
        ]);

        return redirect()->to('/contact-lists')->with('contact_lists_success', 'Lista atualizada com sucesso.');
    }

    /**
     * Remove uma lista existente.
     *
     * @param int $id Identificador da lista.
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $model = new ContactListModel();
        $model->delete($id);

        return redirect()->to('/contact-lists')->with('contact_lists_success', 'Lista removida.');
    }

    /**
     * Retorna os contatos únicos presentes nas listas selecionadas.
     *
     * @return ResponseInterface
     */
    public function buscarContatos(): ResponseInterface
    {
        $listaIds = $this->request->getPost('listas');

        if (empty($listaIds) || !is_array($listaIds)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Nenhuma lista selecionada.',
            ]);
        }

        $listaIds = array_values(array_unique(array_map('intval', $listaIds)));

        if (empty($listaIds)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Nenhuma lista válida informada.',
            ]);
        }

        $membroModel = new ContactListMemberModel();
        $registros = $membroModel->select('contact_id, list_id')
            ->whereIn('list_id', $listaIds)
            ->findAll();

        $contatosUnicos = [];
        $quantidadePorLista = [];

        foreach ($registros as $registro) {
            $contatoId = (int) $registro['contact_id'];
            $listaId = (int) $registro['list_id'];

            $contatosUnicos[$contatoId] = true;
            $quantidadePorLista[$listaId] = ($quantidadePorLista[$listaId] ?? 0) + 1;
        }

        $listaModel = new ContactListModel();
        $listas = [];

        foreach ($listaModel->whereIn('id', $listaIds)->findAll() as $lista) {
            $id = (int) $lista['id'];
            $listas[] = [
                'id' => $id,
                'name' => (string) $lista['name'],
                'contacts' => $quantidadePorLista[$id] ?? 0,
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'contact_ids' => array_keys($contatosUnicos),
            'total_contacts' => count($contatosUnicos),
            'lists' => $listas,
        ]);
    }

    /**
     * Exibe uma lista e seus contatos.
     *
     * @param int $id Identificador da lista.
     * @return string|RedirectResponse
     */
    public function view(int $id)
    {
        $listModel = new ContactListModel();
        $contactModel = new ContactModel();

        $list = $listModel->find($id);

        if (!$list) {
            return redirect()->to('/contact-lists')->with('contact_lists_error', 'Lista não encontrada.');
        }

        $filters = [
            'email' => (string) $this->request->getGet('email'),
            'name' => (string) $this->request->getGet('name'),
        ];

        $contacts = $contactModel->getContactsForList($id, $filters, 20);

        return view('contact_lists/view', [
            'list' => $list,
            'contacts' => $contacts,
            'filters' => $filters,
            'pager' => $contactModel->pager,
            'activeMenu' => 'contact_lists',
            'pageTitle' => 'Contatos da Lista',
        ]);
    }

    /**
     * Remove um contato específico da lista.
     *
     * @param int $listId    Identificador da lista.
     * @param int $contactId Identificador do contato.
     * @return RedirectResponse
     */
    public function detachContact(int $listId, int $contactId): RedirectResponse
    {
        $memberModel = new ContactListMemberModel();
        $listModel = new ContactListModel();

        $memberModel
            ->where('list_id', $listId)
            ->where('contact_id', $contactId)
            ->delete();

        $listModel->refreshCounters([$listId]);

        return redirect()->back()->with('contact_lists_success', 'Contato removido da lista.');
    }


    /**
     * Atualiza o total_contacts de uma ou todas as listas.
     *
     * @param int|null $id Identificador da lista (null = todas).
     * @return ResponseInterface
     */
    public function refreshCounts(?int $id = null): ResponseInterface
    {
        $listModel = new ContactListModel();
        
        if ($id !== null) {
            // Atualizar apenas uma lista
            $list = $listModel->find($id);
            
            if (!$list) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Lista não encontrada.'
                ]);
            }
            
            $listModel->refreshCounters([$id]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Contador atualizado com sucesso.'
            ]);
        }
        
        // Atualizar todas as listas
        $allLists = $listModel->findAll();
        $listIds = array_column($allLists, 'id');
        
        if (!empty($listIds)) {
            $listModel->refreshCounters($listIds);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Contadores atualizados com sucesso.',
            'total_updated' => count($listIds)
        ]);
    }
}