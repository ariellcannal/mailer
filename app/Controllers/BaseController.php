<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use App\Models\UserModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['settings'];

    /**
     * Lista de rotas públicas que não exigem autenticação.
     *
     * @var list<string>
     */
    protected array $publicRoutes = [
        '',
        'login',
        'register',
        'logout',
        'auth/google',
        'auth/google/callback',
        'auth/forgot-password',
        'auth/reset-password',
        'track/open',
        'track/click',
        'webview',
        'optout',
        'imagens',
        'assets',
        'favicon.ico',
    ];

    /**
     * Dados do usuário autenticado.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $currentUser = null;

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->enforceAuthentication();
    }

    /**
     * Garante que a rota exige autenticação ou redireciona para login.
     *
     * @return void
     */
    protected function enforceAuthentication(): void
    {
        if ($this->request instanceof CLIRequest) {
            return;
        }

        $currentPath = trim($this->request->getUri()->getPath(), '/');

        if ($this->isPublicRoute($currentPath)) {
            return;
        }

        $session = session();
        $userId = (int) ($session->get('user_id') ?? 0);

        if ($userId <= 0) {
            $this->redirectToLogin();
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || !(bool) ($user['is_active'] ?? true)) {
            $session->destroy();
            $this->redirectToLogin();
        }

        $this->currentUser = $user;
        $session->set([
            'user_email' => $user['email'],
            'user_name' => $user['name'],
            'auth_provider' => $session->get('auth_provider') ?? 'password',
        ]);
    }

    /**
     * Verifica se a rota é pública com base em prefixos conhecidos.
     *
     * @param string $path Caminho atual da requisição.
     * @return bool
     */
    protected function isPublicRoute(string $path): bool
    {
        foreach ($this->publicRoutes as $public) {
            if ($public === '') {
                if ($path === '') {
                    return true;
                }
                continue;
            }

            if ($path === $public || str_starts_with($path, $public . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redireciona para o login preservando a URL acessada.
     *
     * @return void
     */
    protected function redirectToLogin(): void
    {
        $intendedUrl = current_url(true)->__toString();
        session()->destroy();

        $loginUrl = site_url('login');
        $redirectUrl = $loginUrl . '?redirect=' . rawurlencode($intendedUrl);

        redirect()->to($redirectUrl)->withCookies()->send();
        exit;
    }
}
