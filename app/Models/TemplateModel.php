<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo responsável pelos templates de email.
 */
class TemplateModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'templates';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array<int, string>
     */
    protected $allowedFields = [
        'name',
        'description',
        'html_content',
        'thumbnail',
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

    /**
     * Define o retorno como array associativo.
     *
     * @var string
     */
    protected $returnType = 'array';
}
