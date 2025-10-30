<?php

declare(strict_types=1);

namespace App\Controllers;

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
        ]);
    }
}
