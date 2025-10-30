<?php
namespace App\Models;
use CodeIgniter\Model;

/**
 * Classe responsável por gerenciar os registros de campanhas no banco de dados.
 */
class CampaignModel extends Model {
    protected $table = 'campaigns';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['name','description','total_messages','total_sends','total_opens','total_clicks','total_bounces','total_optouts','is_active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Incrementa um campo numérico específico da campanha informada.
     *
     * @param int    $id    Identificador da campanha que será atualizada.
     * @param string $field Nome do campo numérico que receberá o incremento.
     * @param int    $value Quantidade a ser adicionada ao campo informado.
     *
     * @return bool
     */
    public function increment(int $id, string $field, int $value = 1): bool {
        return $this->set($field, "$field + $value", false)->where('id', $id)->update();
    }
}
