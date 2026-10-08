<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppSendModel extends TenantModel
{
    protected $table = 'whatsapp_sends';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'whatsapp_message_id', 'contact_id', 'destination_phone', 'status',
        'meta_message_id', 'error_code', 'error_message', 'sent_at', 'delivered_at', 'read_at',
    ];
    protected $useTimestamps = true;
}