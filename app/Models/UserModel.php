<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model de usuários para autenticação.
 */
class UserModel extends Model
{
    /** @var string */
    protected $table = 'users';

    /** @var string */
    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $allowedFields = [
        'google_id',
        'passkey_credential_id',
        'email',
        'name',
        'avatar',
        'is_active',
        'last_login',
        'password_hash',
        'reset_code',
        'reset_expires_at',
        'created_at',
        'updated_at',
    ];

    /** @var bool */
    protected $useTimestamps = true;

    /**
     * Busca usuário pelo e-mail.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Atualiza a data de último login.
     */
    public function touchLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
}
