<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Contact Model
 * 
 * Model para gerenciamento de contatos
 * 
 * @package App\Models
 * @author  Mailer System
 * @version 1.0.0
 */
class ContactModel extends Model
{
    protected $table = 'contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'email',
        'name',
        'nickname',
        'quality_score',
        'total_opens',
        'total_clicks',
        'avg_open_time',
        'last_open_date',
        'last_click_date',
        'is_active',
        'opted_out',
        'opted_out_at',
        'bounced',
        'bounce_type',
        'bounced_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'email' => 'required|valid_email|is_unique[contacts.email,id,{id}]',
        'name' => 'permit_empty|max_length[255]',
        'nickname' => 'permit_empty|max_length[255]',
        'quality_score' => 'permit_empty|integer|in_list[1,2,3,4,5]',
    ];

    protected $validationMessages = [
        'email' => [
            'required' => 'Email é obrigatório',
            'valid_email' => 'Email inválido',
            'is_unique' => 'Este email já está cadastrado',
        ],
    ];

    /**
     * Recupera histórico de envios do contato com métricas agregadas.
     *
     * @param int $contactId ID do contato a ser consultado.
     * @return array Lista de envios ordenada por data de envio.
     */
    public function getContactSends(int $contactId): array
    {
        $builder = $this->db->table('message_sends');

        return $builder
            ->select(
                'message_sends.id, message_sends.message_id, message_sends.resend_number, message_sends.sent_at, ' .
                'message_sends.opened, message_sends.total_opens, message_sends.clicked, message_sends.total_clicks, ' .
                'messages.subject, messages.status AS message_status'
            )
            ->join('messages', 'messages.id = message_sends.message_id')
            ->where('message_sends.contact_id', $contactId)
            ->orderBy('message_sends.sent_at', 'DESC')
            ->orderBy('message_sends.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Busca contatos com paginação e filtros
     * 
     * @param array $filters Filtros opcionais
     * @param int   $perPage Itens por página
     * 
     * @return array Contatos paginados
     */
    public function getContacts(array $filters = [], int $perPage = 20): array
    {
        $this->select('*');

        $this->applyFilters($filters);

        return $this->paginate($perPage);
    }

    /**
     * Retorna todos os IDs de contatos aplicando filtros.
     *
     * @param array $filters Filtros opcionais.
     * @return array<int>
     */
    public function getAllContactIds(array $filters = []): array
    {
        $this->select('id');
        $this->applyFilters($filters);

        return $this->findColumn('id') ?? [];
    }

    /**
     * Busca contatos pertencentes a uma lista específica com paginação.
     *
     * @param int   $listId   Identificador da lista.
     * @param array $filters  Filtros de pesquisa.
     * @param int   $perPage  Quantidade por página.
     * @return array
     */
    public function getContactsForList(int $listId, array $filters = [], int $perPage = 20): array
    {
        $this->select('contacts.*')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->where('contact_list_members.list_id', $listId);

        $this->applyFilters($filters);

        return $this->paginate($perPage);
    }

    /**
     * Aplica filtros reutilizáveis nas consultas de contatos.
     *
     * @param array $filters Filtros a serem aplicados.
     * @return void
     */
    protected function applyFilters(array $filters): void
    {
        if (!empty($filters['email'])) {
            $this->like('email', $filters['email']);
        }

        if (!empty($filters['name'])) {
            $this->like('name', $filters['name']);
        }

        if (!empty($filters['quality_score'])) {
            $this->where('quality_score', $filters['quality_score']);
        }

        if (isset($filters['is_active'])) {
            $this->where('is_active', $filters['is_active']);
        }

        if (isset($filters['opted_out'])) {
            $this->where('opted_out', $filters['opted_out']);
        }

        if (isset($filters['bounced'])) {
            $this->where('bounced', $filters['bounced']);
        }
    }

    /**
     * Atualiza score de qualidade de um contato
     * 
     * @param int $contactId ID do contato
     * 
     * @return bool Sucesso da atualização
     */
    public function updateQualityScore(int $contactId): bool
    {
        $contact = $this->find($contactId);
        
        if (!$contact) {
            return false;
        }

        $totalOpens = $contact['total_opens'];
        $totalClicks = $contact['total_clicks'];
        $avgOpenTime = $contact['avg_open_time'];

        // Calcular score baseado em métricas
        $score = 1; // Padrão

        if ($totalOpens == 0) {
            $score = 1;
        } elseif ($totalOpens < 5) {
            $score = 2;
        } elseif ($totalOpens < 15) {
            $score = 3;
        } elseif ($totalOpens < 30) {
            $score = 4;
        } else {
            $score = 5;
        }

        // Bonus por cliques
        if ($totalClicks > 0) {
            $score = min(5, $score + 1);
        }

        // Bonus por velocidade de abertura (< 1 hora)
        if ($avgOpenTime > 0 && $avgOpenTime < 3600) {
            $score = min(5, $score + 1);
        }

        return $this->update($contactId, ['quality_score' => $score]);
    }

    /**
     * Marca contato como opted out
     * 
     * @param int $contactId ID do contato
     * 
     * @return bool Sucesso da operação
     */
    public function optOut(int $contactId): bool
    {
        return $this->update($contactId, [
            'opted_out' => 1,
            'opted_out_at' => date('Y-m-d H:i:s'),
            'is_active' => 0,
        ]);
    }

    /**
     * Marca contato como bounced
     * 
     * @param int    $contactId  ID do contato
     * @param string $bounceType Tipo de bounce
     * 
     * @return bool Sucesso da operação
     */
    public function markAsBounced(int $contactId, string $bounceType = 'hard'): bool
    {
        $data = [
            'bounced' => 1,
            'bounce_type' => $bounceType,
            'bounced_at' => date('Y-m-d H:i:s'),
        ];

        // Hard bounce desativa contato
        if ($bounceType === 'hard') {
            $data['is_active'] = 0;
        }

        return $this->update($contactId, $data);
    }

    /**
     * Importa contatos em massa
     * 
     * @param array $contacts Array de contatos
     * 
     * @return array Resultado da importação
     */
    public function importContacts(array $contacts, array $listIds = []): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $listIds = array_map('intval', array_unique(array_filter($listIds)));

        $listMemberModel = new ContactListMemberModel();
        $listModel = new ContactListModel();

        foreach ($contacts as $contact) {
            try {
                // Verifica se email já existe
                $existing = $this->where('email', $contact['email'])->first();

                if ($existing) {
                    if (!empty($contact['name']) && $contact['name'] !== ($existing['name'] ?? '')) {
                        $this->update((int) $existing['id'], [
                            'name' => $contact['name'],
                            'nickname' => $this->generateNickname($contact['name'], $contact['email']),
                        ]);
                    }

                    if (!empty($listIds)) {
                        $this->syncContactLists((int) $existing['id'], $listIds, $listMemberModel, $listModel);
                    }

                    $skipped++;
                    continue;
                }

                $contactId = $this->insert([
                    'email' => $contact['email'],
                    'name' => $contact['name'] ?? null,
                    'nickname' => $this->generateNickname($contact['name'] ?? null, $contact['email']),
                    'quality_score' => 3, // Score padrão
                    'is_active' => 1,
                ]);

                $imported++;

                if (!empty($listIds)) {
                    $this->syncContactLists((int) $contactId, $listIds, $listMemberModel, $listModel);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'email' => $contact['email'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Gera o apelido do contato com base no primeiro nome ou no usuário do e-mail.
     *
     * @param string|null $name  Nome completo informado.
     * @param string      $email Endereço de e-mail do contato.
     * @return string Apelido capitalizado.
     */
    public function generateNickname(?string $name, string $email): string
    {
        $source = trim((string) $name);

        if ($source === '') {
            $source = strstr($email, '@', true) ?: $email;
        }

        $firstName = explode(' ', $source)[0];

        return mb_convert_case($firstName, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Vincula um conjunto de contatos a listas específicas.
     *
     * @param array<int> $contactIds Identificadores de contatos que devem ser vinculados.
     * @param array<int> $listIds    Identificadores das listas selecionadas.
     * @return void
     */
    public function assignContactsToLists(array $contactIds, array $listIds): void
    {
        $contactIds = array_map('intval', array_unique(array_filter($contactIds)));
        $listIds = array_map('intval', array_unique(array_filter($listIds)));

        if (empty($contactIds) || empty($listIds)) {
            return;
        }

        $memberModel = new ContactListMemberModel();
        $listModel = new ContactListModel();

        foreach ($contactIds as $contactId) {
            $this->syncContactLists($contactId, $listIds, $memberModel, $listModel);
        }

        $listModel->refreshCounters($listIds);
    }

    /**
     * Sincroniza as listas de um contato.
     *
     * @param int $contactId Identificador do contato.
     * @param array<int> $listIds Listas selecionadas.
     * @param ContactListMemberModel|null $memberModel Modelo de membros para reutilização.
     * @param ContactListModel|null $listModel Modelo de listas para atualizar contadores.
     * @return void
     */
    public function syncContactLists(
        int $contactId,
        array $listIds,
        ?ContactListMemberModel $memberModel = null,
        ?ContactListModel $listModel = null
    ): void {
        $listIds = array_map('intval', array_unique(array_filter($listIds)));

        if (empty($listIds)) {
            return;
        }

        $memberModel ??= new ContactListMemberModel();
        $listModel ??= new ContactListModel();

        foreach ($listIds as $listId) {
            $exists = $memberModel
                ->where('contact_id', $contactId)
                ->where('list_id', $listId)
                ->first();

            if ($exists !== null) {
                continue;
            }

            $memberModel->insert([
                'contact_id' => $contactId,
                'list_id' => $listId,
            ], false);
        }

        $listModel->refreshCounters($listIds);
    }

    /**
     * Substitui todas as listas de um contato pelas selecionadas.
     *
     * @param int $contactId Identificador do contato.
     * @param array<int> $listIds Listas desejadas.
     * @return void
     */
    public function replaceContactLists(int $contactId, array $listIds): void
    {
        $memberModel = new ContactListMemberModel();
        $listModel = new ContactListModel();

        $currentListIds = $memberModel
            ->where('contact_id', $contactId)
            ->findColumn('list_id') ?? [];

        $memberModel->where('contact_id', $contactId)->delete();

        if (empty($listIds)) {
            $listModel->refreshCounters($currentListIds);
            return;
        }

        $this->syncContactLists($contactId, $listIds, $memberModel, $listModel);
        $listModel->refreshCounters(array_unique(array_merge($listIds, $currentListIds)));
    }

    /**
     * Obtém contatos de uma lista
     * 
     * @param int $listId ID da lista
     * 
     * @return array Contatos da lista
     */
    public function getContactsByList(int $listId): array
    {
        return $this->db->table('contacts')
            ->select('contacts.*')
            ->join('contact_list_members', 'contact_list_members.contact_id = contacts.id')
            ->where('contact_list_members.list_id', $listId)
            ->where('contacts.is_active', 1)
            ->where('contacts.opted_out', 0)
            ->where('contacts.bounced', 0)
            ->get()
            ->getResultArray();
    }

    /**
     * Obtém estatísticas gerais de contatos
     * 
     * @return array Estatísticas
     */
    public function getStatistics(): array
    {
        $total = $this->countAll();
        $active = $this->where('is_active', 1)->countAllResults(false);
        $optedOut = $this->where('opted_out', 1)->countAllResults(false);
        $bounced = $this->where('bounced', 1)->countAllResults(false);

        // Distribuição por qualidade
        $qualityDist = $this->select('quality_score, COUNT(*) as count')
            ->groupBy('quality_score')
            ->orderBy('quality_score', 'ASC')
            ->findAll();

        return [
            'total' => $total,
            'active' => $active,
            'opted_out' => $optedOut,
            'bounced' => $bounced,
            'quality_distribution' => $qualityDist,
        ];
    }
}
