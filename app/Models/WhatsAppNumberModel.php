<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppNumberModel extends TenantModel
{
    protected $table = 'whatsapp_numbers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'waba_id', 'phone_number_id', 'display_phone_number',
        'verified_name', 'quality_rating', 'status', 'is_active', 'synced_at',
    ];
    protected $useTimestamps = true;
}