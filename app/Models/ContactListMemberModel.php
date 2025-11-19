<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo de associação entre contatos e listas.
 */
class ContactListMemberModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'contact_list_members';

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
        'contact_id',
        'list_id',
        'added_at',
    ];

    /**
     * @var bool
     */
    public $useTimestamps = false;

    /**
     * Obtém as listas relacionadas aos contatos informados.
     *
     * @param array<int> $contactIds Identificadores dos contatos.
     * @return array<int, array<int, array<string, int|string>>> Map com listas por contato.
     */
    public function getListsByContacts(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('contact_list_members.contact_id, contact_lists.id as list_id, contact_lists.name')
            ->join('contact_lists', 'contact_lists.id = contact_list_members.list_id')
            ->whereIn('contact_list_members.contact_id', $contactIds)
            ->get()
            ->getResultArray();

        $grouped = [];

        foreach ($rows as $row) {
            $contactId = (int) $row['contact_id'];
            $grouped[$contactId][] = [
                'id' => (int) $row['list_id'],
                'name' => (string) $row['name'],
            ];
        }

        return $grouped;
    }
}
