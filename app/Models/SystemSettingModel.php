<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo responsável pelas configurações do sistema.
 */
class SystemSettingModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'system_settings';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array<int, string>
     */
    protected $allowedFields = ['setting_key', 'setting_value'];

    /**
     * @var string
     */
    protected $returnType = 'array';

    /**
     * Recupera uma configuração pela chave.
     *
     * @param string $key Chave da configuração.
     * @return array|null Registro encontrado ou null.
     */
    public function getByKey(string $key): ?array
    {
        return $this->where('setting_key', $key)->first();
    }

    /**
     * Atualiza ou cria uma configuração.
     *
     * @param string      $key   Chave da configuração.
     * @param string|null $value Valor a ser armazenado.
     * @return bool Verdadeiro em caso de sucesso.
     */
    public function upsertSetting(string $key, ?string $value): bool
    {
        $existing = $this->getByKey($key);

        if ($existing === null) {
            return $this->insert([
                'setting_key' => $key,
                'setting_value' => $value,
            ]) !== false;
        }

        return $this->update((int) $existing['id'], ['setting_value' => $value]);
    }

    /**
     * Remove uma configuração pela chave informada.
     *
     * @param string $key Chave que será eliminada.
     * @return bool Verdadeiro quando a exclusão ocorrer sem falhas.
     */
    public function deleteSetting(string $key): bool
    {
        return $this->where('setting_key', $key)->delete() !== false;
    }
}
