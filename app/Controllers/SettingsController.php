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
        $this->purgeAwsCredentials($model);

        $settings = [];

        foreach ($model->findAll() as $item) {
            $settings[$item['setting_key']] = $item['setting_value'];
        }

        return view('settings/index', [
            'settings' => $settings,
            'activeMenu' => 'settings',
            'pageTitle' => 'Configurações',
            'awsConfig' => [
                'region' => getenv('aws.ses.region') ?: 'us-east-1',
                'hasAccessKey' => (bool) getenv('aws.ses.accessKey'),
                'hasSecretKey' => (bool) getenv('aws.ses.secretKey'),
            ],
        ]);
    }

    /**
     * Atualiza as configurações enviadas.
     */
    public function update(): ResponseInterface
    {
        $model = new SystemSettingModel();
        $this->purgeAwsCredentials($model);

        $settings = $this->request->getPost('settings') ?? [];

        unset($settings['aws_access_key'], $settings['aws_secret_key'], $settings['ses_region']);

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

    /**
     * Remove eventuais credenciais da AWS armazenadas em banco.
     *
     * @param SystemSettingModel $model Modelo utilizado para exclusão das chaves.
     * @return void
     */
    private function purgeAwsCredentials(SystemSettingModel $model): void
    {
        foreach (['aws_access_key', 'aws_secret_key', 'ses_region'] as $key) {
            $model->deleteSetting($key);
        }
    }
}
