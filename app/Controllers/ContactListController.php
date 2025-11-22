<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactListModel;
use App\Models\ContactListMemberModel;
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
}
