<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppTemplateModel extends TenantModel
{
    protected $table = 'whatsapp_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'name', 'language', 'category', 'header_type', 'header_content',
        'body_content', 'footer_content', 'buttons', 'meta_template_id', 'status', 'rejection_reason',
    ];
    protected $useTimestamps = true;
}