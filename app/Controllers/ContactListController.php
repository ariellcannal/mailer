<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactListModel;
use CodeIgniter\HTTP\RedirectResponse;

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
}
