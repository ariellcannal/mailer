<?php
namespace App\Models;
use CodeIgniter\Model;

class SenderModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'senders';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $returnType = 'array';

    /**
     * @var array<int, string>
     */
    protected $allowedFields = [
        'email',
        'name',
        'domain',
        'ses_verified',
        'ses_verification_token',
        'dkim_tokens',
        'dkim_verified',
        'spf_verified',
        'dmarc_verified',
        'is_active',
    ];

    /**
     * @var bool
     */
    protected $useTimestamps = true;

    /**
     * @var string
     */
    protected $createdField = 'created_at';

    /**
     * @var string
     */
    protected $updatedField = 'updated_at';
}
