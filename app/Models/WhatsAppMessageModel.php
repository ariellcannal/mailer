<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppMessageModel extends TenantModel
{
    protected $table = 'whatsapp_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'campaign_id', 'template_id', 'whatsapp_number_id', 'name', 'list_ids', 'variable_mapping',
        'scheduled_at', 'status', 'total_recipients', 'total_sent', 'total_delivered', 'total_read', 'total_failed',
    ];
    protected $useTimestamps = true;
}