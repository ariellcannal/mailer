<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Config\Services;

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
     * Atualiza nome e avatar do usuário autenticado.
     *
     * @return RedirectResponse
     */
    public function updateProfile(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $name = trim((string) $this->request->getPost('name'));

        if (mb_strlen($name) < 3) {
            return redirect()->back()->with('error', 'Informe um nome com pelo menos 3 caracteres.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'Usuário não localizado para atualização.');
        }

        $updates = ['name' => $name];
        $avatarData = (string) $this->request->getPost('avatar_cropped');

        if ($avatarData !== '') {
            if (!preg_match('/^data:image\/(png|jpeg);base64,/i', $avatarData)) {
                return redirect()->back()->with('error', 'Formato de imagem inválido para o avatar.');
            }

            $base64 = preg_replace('/^data:image\/(png|jpeg);base64,/i', '', $avatarData);
            $binary = base64_decode($base64, true);

            if ($binary === false) {
                return redirect()->back()->with('error', 'Não foi possível processar a imagem enviada.');
            }

            $uploadDir = FCPATH . 'uploads/avatars';
            $tempDir = WRITEPATH . 'uploads';

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                return redirect()->back()->with('error', 'Não foi possível preparar o diretório de avatars.');
            }

            if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
                return redirect()->back()->with('error', 'Não foi possível preparar o diretório temporário.');
            }

            $tempPath = $tempDir . '/avatar_' . $userId . '_' . time() . '.tmp';
            file_put_contents($tempPath, $binary);

            $fileName = 'uploads/avatars/avatar-' . $userId . '-' . time() . '.jpg';
            $finalPath = FCPATH . $fileName;

            Services::image()
                ->withFile($tempPath)
                ->fit(512, 512, 'center')
                ->save($finalPath, 85);

            @unlink($tempPath);

            if (!empty($user['avatar']) && !str_starts_with((string) $user['avatar'], 'http')) {
                $previousPath = FCPATH . ltrim((string) $user['avatar'], '/');

                if (is_file($previousPath)) {
                    @unlink($previousPath);
                }
            }

            $updates['avatar'] = $fileName;
        }

        $userModel->update($userId, $updates);
        session()->set([
            'user_name' => $updates['name'],
            'user_avatar' => $updates['avatar'] ?? ($user['avatar'] ?? null),
        ]);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso.');
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
     * Solicita código para alteração de e-mail.
     *
     * @return RedirectResponse
     */
    public function requestEmailCode(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $newEmail = strtolower(trim((string) $this->request->getPost('new_email')));
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'Usuário não encontrado para alteração de e-mail.');
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Informe um e-mail válido para continuar.');
        }

        if (strcasecmp($newEmail, (string) $user['email']) === 0) {
            return redirect()->back()->with('error', 'O novo e-mail precisa ser diferente do atual.');
        }

        $existing = $userModel->findByEmail($newEmail);

        if ($existing && (int) $existing['id'] !== $userId) {
            return redirect()->back()->with('error', 'O e-mail informado já está em uso por outro usuário.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Time::now()->addMinutes(30)->toDateTimeString();

        $userModel->update($userId, [
            'pending_email' => $newEmail,
            'user_code' => $code,
            'user_code_expires_at' => $expiresAt,
        ]);

        if (!$this->sendVerificationEmail($newEmail, $code)) {
            $userModel->update($userId, [
                'pending_email' => null,
                'user_code' => null,
                'user_code_expires_at' => null,
            ]);

            return redirect()->back()->with('error', 'Não foi possível enviar o código para o novo e-mail.');
        }

        return redirect()->back()->with('success', 'Código enviado! Verifique o novo e-mail para confirmar a alteração.');
    }

    /**
     * Confirma a alteração de e-mail com código válido.
     *
     * @return RedirectResponse
     */
    public function confirmEmailChange(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');
        $code = (string) $this->request->getPost('email_code');
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || empty($user['pending_email'])) {
            return redirect()->back()->with('error', 'Nenhuma solicitação de alteração de e-mail foi encontrada.');
        }

        if ($code === '' || $code !== (string) ($user['user_code'] ?? '')) {
            return redirect()->back()->with('error', 'Código de validação inválido.');
        }

        if (empty($user['user_code_expires_at']) || Time::now()->isAfter(Time::parse($user['user_code_expires_at']))) {
            return redirect()->back()->with('error', 'Código expirado. Solicite um novo envio.');
        }

        $existing = $userModel->findByEmail((string) $user['pending_email']);

        if ($existing && (int) $existing['id'] !== $userId) {
            return redirect()->back()->with('error', 'O e-mail informado já está em uso por outro usuário.');
        }

        $updates = [
            'email' => $user['pending_email'],
            'pending_email' => null,
            'user_code' => null,
            'user_code_expires_at' => null,
        ];

        if (!empty($user['google_id'])) {
            $updates['google_id'] = null;
        }

        $userModel->update($userId, $updates);

        session()->set('user_email', $updates['email']);

        return redirect()->back()->with('success', 'E-mail atualizado com sucesso. Faça login novamente pelo Google se desejar mantê-lo vinculado.');
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

    /**
     * Inicia fluxo de vinculação com Google OAuth.
     *
     * @return RedirectResponse
     */
    public function linkGoogle(): RedirectResponse
    {
        $userId = (int) session()->get('user_id');

        if ($userId <= 0) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'Usuário não encontrado para vincular ao Google.');
        }

        if (!empty($user['google_id'])) {
            return redirect()->back()->with('error', 'A conta já está vinculada ao Google.');
        }

        session()->set([
            'google_link_user_id' => $userId,
            'login_redirect' => '/profile',
        ]);

        return redirect()->to('/auth/google');
    }

    /**
     * Envia código de verificação para o novo e-mail.
     *
     * @param string $email Destinatário alvo.
     * @param string $code  Código gerado.
     * @return bool
     */
    private function sendVerificationEmail(string $email, string $code): bool
    {
        $emailService = Services::email();

        $emailService->setTo($email);
        $emailService->setSubject('Código para alterar seu e-mail');
        $emailService->setMessage("Olá,\n\nSeu código para alterar o e-mail é: {$code}. Ele expira em 30 minutos.\n\nSe você não solicitou, ignore esta mensagem.");

        return $emailService->send();
    }
}
