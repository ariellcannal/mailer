<?php
namespace App\Models;
use CodeIgniter\Model;

class MessageModel extends Model {
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['campaign_id','sender_id','link_domain_id','template_id','subject','from_name','reply_to','html_content','has_optout_link','optout_link_visible','status','scheduled_at','sent_at','total_recipients','total_sent','total_opens','total_clicks','total_bounces','total_optouts','progress_data'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    public function increment(int $id, string $field, int $value = 1): bool {
        return $this->set($field, "$field + $value", false)->where('id', $id)->update();
    }
}
