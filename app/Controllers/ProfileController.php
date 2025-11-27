<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controlador responsável pelo perfil do usuário.
 */
class ProfileController extends BaseController
{
    /**
     * Exibe informações básicas de perfil.
     */
    public function index(): string
    {
        return view('profile/index', [
            'activeMenu' => 'profile',
            'pageTitle' => 'Perfil',
            'user' => $this->currentUser,
        ]);
    }

    /**
     * Atualiza a senha do usuário autenticado.
     *
     * @return RedirectResponse
     */
    public function changePassword(): RedirectResponse
    {
        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_new_password');
        $userId = (int) session()->get('user_id');

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'A confirmação de senha não confere.');
        }

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'A nova senha deve ter pelo menos 8 caracteres.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || empty($user['password_hash']) || !password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Senha atual incorreta.');
        }

        $userModel->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->back()->with('success', 'Senha atualizada com sucesso.');
    }

    /**
     * Remove o vínculo com o Google OAuth.
     *
     * @return RedirectResponse
     */
    public function unlinkGoogle(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || empty($user['google_id'])) {
            return redirect()->back()->with('error', 'Nenhum vínculo Google encontrado.');
        }

        $userModel->update($userId, ['google_id' => null]);
        session()->set('auth_provider', 'password');

        return redirect()->back()->with('success', 'Conta Google desvinculada com sucesso.');
    }
}
