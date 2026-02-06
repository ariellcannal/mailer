<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AutoMigrate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Carrega o serviço de migração
        $migrations = Services::migrations();

        try {
            // Executa todas as migrações pendentes
            // Se já estiver atualizado, ele apenas retorna true
            $migrations->latest(); 
        } catch (\Throwable $e) {
            // Logue o erro se necessário para não travar a aplicação
            log_message('error', 'Erro na Migração Automática: ' . $e->getMessage());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer aqui
    }
}