<?php
namespace App\Models;

use CodeIgniter\Model;

class MessageSendModel extends Model
{

    protected $table = 'message_sends';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'message_id',
        'contact_id',
        'resend_number',
        'tracking_hash',
        'status',
        'sent_at',
        'delivery_at',
        'opened',
        'first_open_at',
        'total_opens',
        'last_open_at',
        'clicked',
        'first_click_at',
        'total_clicks',
        'last_click_at',
        'bounced_at',
        'bounce_type',
        'bounce_reason',
        'complained_at'
    ];
}
