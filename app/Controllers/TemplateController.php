<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TemplateModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controlador responsável pelo gerenciamento de templates.
 */
class TemplateController extends BaseController
{
    /**
     * Lista os templates cadastrados.
     */
    public function index(): string
    {
        $model = new TemplateModel();
        $templates = $model->orderBy('created_at', 'DESC')->findAll();

        return view('templates/index', [
            'templates' => $templates,
            'activeMenu' => 'templates',
            'pageTitle' => 'Templates',
        ]);
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): string
    {
        return view('templates/create', [
            'activeMenu' => 'templates',
            'pageTitle' => 'Novo Template',
        ]);
    }

    /**
     * Persiste um novo template.
     */
    public function store(): RedirectResponse
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'html_content' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new TemplateModel();
        $model->insert([
            'name' => (string) $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'html_content' => (string) $this->request->getPost('html_content'),
            'thumbnail' => $this->request->getPost('thumbnail'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/templates')->with('success', 'Template criado com sucesso!');
    }

    /**
     * Exibe os detalhes de um template.
     */
    public function view(int $id): RedirectResponse|string
    {
        $model = new TemplateModel();
        $template = $model->find($id);

        if ($template === null) {
            return redirect()->to('/templates')->with('error', 'Template não encontrado.');
        }

        return view('templates/view', [
            'template' => $template,
            'activeMenu' => 'templates',
            'pageTitle' => $template['name'],
        ]);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(int $id): RedirectResponse|string
    {
        $model = new TemplateModel();
        $template = $model->find($id);

        if ($template === null) {
            return redirect()->to('/templates')->with('error', 'Template não encontrado.');
        }

        return view('templates/edit', [
            'template' => $template,
            'activeMenu' => 'templates',
            'pageTitle' => 'Editar Template',
        ]);
    }

    /**
     * Atualiza o template.
     */
    public function update(int $id): RedirectResponse
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'html_content' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new TemplateModel();
        $template = $model->find($id);

        if ($template === null) {
            return redirect()->to('/templates')->with('error', 'Template não encontrado.');
        }

        $model->update($id, [
            'name' => (string) $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'html_content' => (string) $this->request->getPost('html_content'),
            'thumbnail' => $this->request->getPost('thumbnail'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/templates/view/' . $id)->with('success', 'Template atualizado com sucesso!');
    }

    /**
     * Remove um template existente.
     */
    public function delete(int $id): RedirectResponse
    {
        $model = new TemplateModel();
        $template = $model->find($id);

        if ($template === null) {
            return redirect()->to('/templates')->with('error', 'Template não encontrado.');
        }

        $model->delete($id);

        return redirect()->to('/templates')->with('success', 'Template excluído com sucesso!');
    }
}
