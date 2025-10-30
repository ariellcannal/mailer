<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AWS\SESService;
use App\Models\SystemSettingModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador para configurações do sistema.
 */
class SettingsController extends BaseController
{
    /**
     * Exibe as configurações gerais.
     */
    public function index(): string
    {
        $model = new SystemSettingModel();
        $settings = [];

        foreach ($model->findAll() as $item) {
            $settings[$item['setting_key']] = $item['setting_value'];
        }

        return view('settings/index', [
            'settings' => $settings,
            'activeMenu' => 'settings',
            'pageTitle' => 'Configurações',
        ]);
    }

    /**
     * Atualiza as configurações enviadas.
     */
    public function update(): ResponseInterface
    {
        $model = new SystemSettingModel();
        $settings = $this->request->getPost('settings') ?? [];

        foreach ($settings as $key => $value) {
            $model->upsertSetting($key, $value !== '' ? (string) $value : null);
        }

        return redirect()->to('/settings')->with('success', 'Configurações atualizadas com sucesso!');
    }

    /**
     * Consulta limites atuais do AWS SES.
     */
    public function sesLimits(): ResponseInterface
    {
        try {
            $service = new SESService();
            $quota = $service->getSendQuota();

            return $this->response->setJSON($quota);
        } catch (\Throwable $exception) {
            log_message('error', 'Erro ao consultar limites SES: ' . $exception->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não foi possível obter os limites no momento.',
            ])->setStatusCode(500);
        }
    }
}
