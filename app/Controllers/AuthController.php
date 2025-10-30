<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Controlador responsável por autenticação básica.
 */
class AuthController extends BaseController
{
    /**
     * Exibe a tela de login.
     */
    public function login(): string
    {
        return view('auth/login', [
            'pageTitle' => 'Entrar',
        ]);
    }

    /**
     * Finaliza a sessão do usuário.
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Sessão encerrada com sucesso.');
    }

    /**
     * Placeholder para OAuth com Google.
     */
    public function google()
    {
        return redirect()->to('/login')->with('error', 'Integração com Google ainda não configurada.');
    }

    /**
     * Callback da integração com Google.
     */
    public function googleCallback()
    {
        return redirect()->to('/login')->with('error', 'Integração com Google ainda não configurada.');
    }
}
