<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Aplica o escopo da empresa autenticada aos models que possuem company_id.
 */
abstract class TenantModel extends Model
{
    private ?int $tenantScopeOverride = null;

    /** @var list<string> */
    protected $beforeInsert = ['assignCompany'];

    /** @var list<string> */
    protected $beforeInsertBatch = ['assignCompanyBatch'];

    public function builder(?string $table = null)
    {
        $builder = parent::builder($table);
        $companyId = $this->currentCompanyId();

        if ($companyId > 0 && ($table === null || $table === '' || $table === $this->table)) {
            $builder->where($this->table . '.company_id', $companyId);
        }

        return $builder;
    }

    public function forCompany(int $companyId): static
    {
        $this->tenantScopeOverride = $companyId > 0 ? $companyId : null;

        return $this;
    }

    /** @param array<string, mixed> $eventData */
    protected function assignCompany(array $eventData): array
    {
        $companyId = $this->currentCompanyId();

        if ($companyId > 0) {
            $eventData['data']['company_id'] = $companyId;
        }

        return $eventData;
    }

    /** @param array<string, mixed> $eventData */
    protected function assignCompanyBatch(array $eventData): array
    {
        $companyId = $this->currentCompanyId();

        if ($companyId > 0) {
            foreach ($eventData['data'] as &$row) {
                $row['company_id'] = $companyId;
            }
            unset($row);
        }

        return $eventData;
    }

    protected function currentCompanyId(): int
    {
        if ($this->tenantScopeOverride !== null) {
            return $this->tenantScopeOverride;
        }

        if (is_cli() || ! function_exists('session')) {
            return 0;
        }

        return (int) (session()->get('company_id') ?? 0);
    }
}