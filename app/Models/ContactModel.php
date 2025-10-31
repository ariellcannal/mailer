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

        // Filtro por email
        if (!empty($filters['email'])) {
            $this->like('email', $filters['email']);
        }

        // Filtro por nome
        if (!empty($filters['name'])) {
            $this->like('name', $filters['name']);
        }

        // Filtro por qualidade
        if (!empty($filters['quality_score'])) {
            $this->where('quality_score', $filters['quality_score']);
        }

        // Filtro por status
        if (isset($filters['is_active'])) {
            $this->where('is_active', $filters['is_active']);
        }

        // Filtro por opted_out
        if (isset($filters['opted_out'])) {
            $this->where('opted_out', $filters['opted_out']);
        }

        // Filtro por bounced
        if (isset($filters['bounced'])) {
            $this->where('bounced', $filters['bounced']);
        }

        return $this->paginate($perPage);
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
    public function importContacts(array $contacts): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($contacts as $contact) {
            try {
                // Verifica se email já existe
                $existing = $this->where('email', $contact['email'])->first();
                
                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Insere contato
                $this->insert([
                    'email' => $contact['email'],
                    'name' => $contact['name'] ?? null,
                    'quality_score' => 3, // Score padrão
                    'is_active' => 1,
                ]);

                $imported++;
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
