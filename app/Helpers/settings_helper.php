<?php

declare(strict_types=1);

use App\Models\SystemSettingModel;

if (! function_exists('get_system_setting')) {
    /**
     * Recupera uma configuração do sistema.
     *
     * @param string      $key     Chave da configuração.
     * @param string|null $default Valor padrão quando não encontrado.
     *
     * @return string|null Valor localizado ou padrão.
     */
    function get_system_setting(string $key, ?string $default = null): ?string
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            /** @var SystemSettingModel $model */
            $model = model(SystemSettingModel::class);

            foreach ($model->findAll() as $setting) {
                $cache[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return $cache[$key] ?? $default;
    }
}
