<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Config\Services;
use Google_Client;
use Google_Service_Oauth2;

/**
 * Controlador responsável por autenticação básica.
 */
class AuthController extends BaseController
{

    /**
     * Exibe a tela de login.
     *
     * @return string
     */
    public function login(): string
    {
        return view('auth/login', [
            'pageTitle' => 'Entrar',
            'redirectTarget' => $this->getRedirectTarget(),
        ]);
    }

    /**
     * Finaliza a sessão do usuário.
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sessão encerrada com sucesso.');
    }

    /**
     * Autentica via e-mail e senha.
     *
     * @return RedirectResponse
     */
    public function authenticate(): RedirectResponse
    {
        $email = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

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

        return redirect()->to($this->getRedirectTarget() ?: '/')->with('success', 'Login realizado com sucesso.');
    }

    /**
     * Registra novo usuário autorizado.
     *
     * @return RedirectResponse
     */
    public function register(): RedirectResponse
    {
        $email = (string) $this->request->getPost('email');
        $name = (string) $this->request->getPost('name');
        $password = (string) $this->request->getPost('password');

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

        return redirect()->to($this->getRedirectTarget() ?: '/')->with('success', 'Cadastro realizado com sucesso.');
    }

    /**
     * Exibe a tela de cadastro autorizado.
     *
     * @return string
     */
    public function registerForm(): string
    {
        return view('auth/register', [
            'pageTitle' => 'Criar acesso',
            'redirectTarget' => $this->getRedirectTarget(),
        ]);
    }

    /**
     * Dispara código de redefinição de senha.
     *
     * @return RedirectResponse
     */
    public function forgotPassword(): RedirectResponse
    {
        $email = (string) $this->request->getPost('forgot_email');

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Usuário não encontrado para redefinição.');
        }

        if (!(bool) ($user['is_active'] ?? true)) {
            return redirect()->back()->with('error', 'Usuário inativo. Contate o administrador.');
        }

        $resetCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Time::now()->addMinutes(30)->toDateTimeString();

        $userModel->update((int) $user['id'], [
            'user_code' => $resetCode,
            'user_code_expires_at' => $expiresAt,
        ]);

        if (!$this->sendUserCodeEmail($email, $resetCode, 'Código para redefinição de senha', 'senha')) {
            return redirect()->back()->with('error', 'Não foi possível enviar o código. Verifique as configurações de e-mail.');
        }

        return redirect()->back()->with('success', 'Código enviado para o e-mail autorizado. Verifique sua caixa de entrada.');
    }

    /**
     * Confirma o código e redefine a senha.
     *
     * @return RedirectResponse
     */
    public function resetPassword(): RedirectResponse
    {
        $email = (string) $this->request->getPost('reset_email');
        $code = (string) $this->request->getPost('user_code');
        $newPassword = (string) $this->request->getPost('reset_new_password');
        $confirmPassword = (string) $this->request->getPost('reset_new_password_confirm');

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->withInput()->with('error', 'A confirmação de senha não confere.');
        }

        if (strlen($newPassword) < 8) {
            return redirect()->back()->withInput()->with('error', 'A nova senha deve ter pelo menos 8 caracteres.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || empty($user['user_code']) || empty($user['user_code_expires_at'])) {
            return redirect()->back()->with('error', 'Código de validação inválido ou expirado.');
        }

        if ($code !== (string) $user['user_code']) {
            return redirect()->back()->with('error', 'Código de validação inválido.');
        }

        if (Time::now()->isAfter(Time::parse($user['user_code_expires_at']))) {
            return redirect()->back()->with('error', 'Código expirado. Solicite um novo envio.');
        }

        $userModel->update((int) $user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'user_code' => null,
            'user_code_expires_at' => null,
        ]);

        $redirect = $this->getRedirectTarget();
        $loginUrl = $redirect ? '/login?redirect=' . rawurlencode($redirect) : '/login';

        return redirect()->to($loginUrl)->with('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
    }

    /**
     * Inicia o fluxo de autenticação com Google.
     *
     * @return RedirectResponse
     */
    public function google(): RedirectResponse
    {
        $client = $this->createGoogleClient();

        if (!$client) {
            return redirect()->back()->with('error', 'Configure as variáveis de ambiente do Google OAuth.');
        }

        $state = bin2hex(random_bytes(16));
        $session = session();
        $session->set('google_oauth_state', $state);

        if (!$session->has('login_redirect')) {
            $session->set('login_redirect', $this->getRedirectTarget());
        }

        $client->setState($state);

        return redirect()->to($client->createAuthUrl());
    }

    /**
     * Finaliza o fluxo de autenticação com Google.
     *
     * @return RedirectResponse
     */
    public function googleCallback(): RedirectResponse
    {
        $session = session();
        $state = (string) $this->request->getGet('state');
        $expectedState = (string) $session->get('google_oauth_state');
        $session->remove('google_oauth_state');

        if (!$state || $state !== $expectedState) {
            return redirect()->to('/login')->with('error', 'Validação do Google falhou. Tente novamente.');
        }

        $authorizationCode = (string) $this->request->getGet('code');

        if (!$authorizationCode) {
            return redirect()->to('/login')->with('error', 'Código de autorização ausente.');
        }

        $client = $this->createGoogleClient();

        if (!$client) {
            return redirect()->to('/login')->with('error', 'Configure as variáveis de ambiente do Google OAuth.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($authorizationCode);

        if (isset($token['error'])) {
            return redirect()->to('/login')->with('error', 'Não foi possível validar o login com o Google.');
        }

        $client->setAccessToken($token);

        $oauth2 = new Google_Service_Oauth2($client);
        $googleUser = $oauth2->userinfo->get();

        $email = (string) $googleUser->getEmail();
        $googleId = (string) $googleUser->getId();
        $name = (string) ($googleUser->getName() ?: 'Usuário Google');
        $avatar = (string) $googleUser->getPicture();

        if (!$email) {
            return redirect()->to('/login')->with('error', 'E-mail não retornado pelo Google.');
        }
        $userModel = new UserModel();
        $linkUserId = (int) $session->get('google_link_user_id');

        if ($linkUserId > 0) {
            $session->remove('google_link_user_id');
            $user = $userModel->find($linkUserId);

            if (!$user) {
                $session->remove('login_redirect');

                return redirect()->to('/login')->with('error', 'Usuário autenticado não encontrado para vincular.');
            }

            if (!(bool) ($user['is_active'] ?? true)) {
                $session->remove('login_redirect');

                return redirect()->to('/profile')->with('error', 'Usuário inativo. Contate o administrador.');
            }

            if (strcasecmp($email, (string) $user['email']) !== 0) {
                $session->remove('login_redirect');

                return redirect()->to('/profile')->with('error', 'O e-mail retornado pelo Google é diferente do cadastrado. Use o mesmo e-mail para vincular.');
            }

            $updates = ['google_id' => $googleId];

            if ($avatar && (!isset($user['avatar']) || $user['avatar'] !== $avatar)) {
                $updates['avatar'] = $avatar;
            }

            if ($name && (empty($user['name']) || $user['name'] !== $name)) {
                $updates['name'] = $name;
            }

            $userModel->update($linkUserId, $updates);
            $user = $userModel->find($linkUserId);
            $userModel->touchLastLogin($linkUserId);
            $this->persistUserSession($user, 'google');

            $redirectTarget = (string) $session->get('login_redirect');
            $session->remove('login_redirect');

            return redirect()->to($redirectTarget ?: '/profile')->with('success', 'Conta Google vinculada com sucesso.');
        }

        $user = $userModel->findByEmail($email);

        if ($user) {
            if (!(bool) ($user['is_active'] ?? true)) {
                return redirect()->to('/login')->with('error', 'Usuário inativo. Contate o administrador.');
            }

            $updates = [];

            if (empty($user['google_id'])) {
                $updates['google_id'] = $googleId;
            }

            if ($avatar && (!isset($user['avatar']) || $user['avatar'] !== $avatar)) {
                $updates['avatar'] = $avatar;
            }

            if ($name && empty($user['name'])) {
                $updates['name'] = $name;
            }

            if ($updates) {
                $userModel->update((int) $user['id'], $updates);
                $user = $userModel->find((int) $user['id']);
            }
        } else {
            $userId = $userModel->insert([
                'email' => $email,
                'name' => $name,
                'avatar' => $avatar,
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

        $redirectTarget = (string) session()->get('login_redirect');
        session()->remove('login_redirect');

        return redirect()->to($redirectTarget ?: '/')->with('success', 'Login pelo Google concluído.');
    }

    /**
     * Persiste dados mínimos do usuário na sessão.
     *
     * @param array<string, string|int|null> $user
     * @param string                         $provider
     */
    private function persistUserSession(array $user, string $provider): void
    {
        session()->regenerate(true);
        session()->set([
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'user_name' => $user['name'],
            'user_avatar' => $user['avatar'] ?? null,
            'auth_provider' => $provider,
        ]);
    }

    /**
     * Obtém destino de redirecionamento seguro.
     *
     * @return string
     */
    private function getRedirectTarget(): string
    {
        $redirect = (string) ($this->request->getPost('redirect') ?? $this->request->getGet('redirect') ?? '');

        if ($redirect === '') {
            return '';
        }

        $parsed = parse_url($redirect);

        if ($parsed === false) {
            return '';
        }

        if (!isset($parsed['host'])) {
            return $redirect;
        }

        $currentHost = parse_url(site_url(), PHP_URL_HOST);

        if ($parsed['host'] === $currentHost) {
            return $redirect;
        }

        return '';
    }

    /**
     * Envia um código de validação para o destino informado.
     *
     * @param string $email Destinatário da mensagem.
     * @param string $code  Código de validação.
     * @param string $subject Assunto do envio.
     * @param string $context Contexto exibido no corpo do e-mail.
     * @return bool
     */
    private function sendUserCodeEmail(string $email, string $code, string $subject, string $context): bool
    {
        $emailService = Services::email();

        $emailService->setTo($email);
        $emailService->setSubject($subject);
        $emailService->setMessage("Olá,\n\nSeu código de verificação para {$context} é: {$code}. Ele expira em 30 minutos.\n\nSe você não solicitou, ignore esta mensagem.");

        return $emailService->send();
    }

    /**
     * Cria o cliente Google OAuth 2.0 com dados do ambiente.
     *
     * @return Google_Client|null
     */
    private function createGoogleClient(): ?Google_Client
    {
        if (!class_exists(Google_Client::class)) {
            return null;
        }

        $clientId = (string) (env('google.clientId') ?: env('GOOGLE_CLIENT_ID'));
        $clientSecret = (string) (env('google.clientSecret') ?: env('GOOGLE_CLIENT_SECRET'));

        if (!$clientId || !$clientSecret) {
            return null;
        }

        $redirectUri = (string) (env('GOOGLE_REDIRECT_URI') ?: env('google.redirectUri') ?: site_url('auth/google/callback'));

        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setPrompt('select_account');
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }
}
