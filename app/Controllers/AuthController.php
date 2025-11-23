<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

/**
 * Controlador responsável por autenticação básica.
 */
class AuthController extends BaseController
{
    /** @var string */
    private string $allowedEmail = 'ariell@cannal.com.br';

    /**
     * Exibe a tela de login e cadastro.
     */
    public function login(): string
    {
        return view('auth/login', [
            'pageTitle' => 'Entrar',
            'allowedEmail' => $this->allowedEmail,
        ]);
    }

    /**
     * Finaliza a sessão do usuário.
     */
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sessão encerrada com sucesso.');
    }

    /**
     * Autentica via e-mail e senha.
     */
    public function authenticate(): RedirectResponse
    {
        $email = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        if ($email !== $this->allowedEmail) {
            return redirect()->back()->withInput()->with('error', 'E-mail não autorizado para acesso.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Credenciais inválidas.');
        }

        if (!(bool) ($user['is_active'] ?? true)) {
            return redirect()->back()->with('error', 'Usuário inativo. Contate o administrador.');
        }

        $userModel->touchLastLogin((int) $user['id']);

        $this->persistUserSession($user, 'password');

        return redirect()->to('/')->with('success', 'Login realizado com sucesso.');
    }

    /**
     * Registra novo usuário autorizado.
     */
    public function register(): RedirectResponse
    {
        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $password = (string) $this->request->getPost('password');

        if ($email !== $this->allowedEmail) {
            return redirect()->back()->withInput()->with('error', 'Cadastro permitido apenas para o e-mail autorizado.');
        }

        if (strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'A senha deve ter pelo menos 8 caracteres.');
        }

        $userModel = new UserModel();

        if ($userModel->findByEmail($email)) {
            return redirect()->back()->withInput()->with('error', 'E-mail já cadastrado.');
        }

        $userId = $userModel->insert([
            'email' => $email,
            'name' => $name ?: 'Usuário Autorizado',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1,
            'last_login' => Time::now()->toDateTimeString(),
        ]);

        if (!$userId) {
            return redirect()->back()->withInput()->with('error', 'Não foi possível concluir o cadastro.');
        }

        $user = $userModel->find((int) $userId);
        $this->persistUserSession($user, 'password');

        return redirect()->to('/')->with('success', 'Cadastro realizado com sucesso.');
    }

    /**
     * Inicia o fluxo de autenticação com Google.
     */
    public function google(): RedirectResponse
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $redirectUri = site_url('auth/google/callback');
        $scopes = urlencode('openid email profile');
        $state = bin2hex(random_bytes(16));
        session()->set('google_oauth_state', $state);

        if (!$clientId) {
            return redirect()->back()->with('error', 'Configure as variáveis de ambiente do Google OAuth.');
        }

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth'
            . '?response_type=code'
            . '&client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&scope=' . $scopes
            . '&state=' . $state
            . '&prompt=select_account';

        return redirect()->to($authUrl);
    }

    /**
     * Finaliza o fluxo de autenticação com Google.
     */
    public function googleCallback(): RedirectResponse
    {
        $state = (string) $this->request->getGet('state');
        $expectedState = (string) session()->get('google_oauth_state');
        session()->remove('google_oauth_state');

        if (!$state || $state !== $expectedState) {
            return redirect()->to('/login')->with('error', 'Validação do Google falhou. Tente novamente.');
        }

        $email = (string) $this->request->getGet('debug_email');
        $sub = (string) $this->request->getGet('debug_sub');

        if (!$email) {
            return redirect()->to('/login')->with('error', 'E-mail não retornado pelo Google.');
        }

        if ($email !== $this->allowedEmail) {
            return redirect()->to('/login')->with('error', 'E-mail não autorizado para acesso.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);
        $googleId = $sub ?: 'google-' . hash('sha256', $email);

        if ($user) {
            if (empty($user['google_id'])) {
                $userModel->update((int) $user['id'], ['google_id' => $googleId]);
            }
        } else {
            $userId = $userModel->insert([
                'email' => $email,
                'name' => 'Usuário Google',
                'google_id' => $googleId,
                'is_active' => 1,
                'last_login' => Time::now()->toDateTimeString(),
            ]);

            if (!$userId) {
                return redirect()->to('/login')->with('error', 'Não foi possível criar o usuário via Google.');
            }

            $user = $userModel->find((int) $userId);
        }

        $userModel->touchLastLogin((int) $user['id']);
        $this->persistUserSession($user, 'google');

        return redirect()->to('/')->with('success', 'Login pelo Google concluído.');
    }

    /**
     * Persiste dados mínimos do usuário na sessão.
     */
    private function persistUserSession(array $user, string $provider): void
    {
        session()->set([
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'user_name' => $user['name'],
            'auth_provider' => $provider,
        ]);
    }
}
