<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo responsável pelas listas de contatos.
 */
class ContactListModel extends TenantModel
{
    /**
     * @var string
     */
    protected $table = 'contact_lists';

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
        'company_id',
        'name',
        'description',
        'total_contacts',
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
     * Atualiza os contadores de uma lista com base nos membros atuais.
     *
     * @param array<int> $listIds Identificadores das listas que devem ser recalculadas.
     * @return void
     */
    public function refreshCounters(array $listIds, int $companyId = 0): void
    {
        if (empty($listIds)) {
            return;
        }

        foreach ($listIds as $listId) {
            $builder = $this->db->table('contact_list_members')->where('list_id', $listId);
            $tenantId = $companyId > 0 ? $companyId : (int) (session()->get('company_id') ?? 0);
            if ($tenantId > 0) {
                $builder->where('company_id', $tenantId);
            }
            $total = $builder->countAllResults();

            $this->update((int) $listId, ['total_contacts' => $total]);
        }
    }
}
