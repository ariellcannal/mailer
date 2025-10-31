<?php

namespace App\Libraries\DNS;

/**
 * DNS Validator
 * 
 * Valida registros DNS (SPF, DKIM, DMARC, MX)
 * 
 * @package App\Libraries\DNS
 * @author  Mailer System
 * @version 1.0.0
 */
class DNSValidator
{
    /**
     * Valida registro SPF de um domínio
     * 
     * @param string $domain Domínio a validar
     * 
     * @return array Resultado da validação
     */
    public function validateSPF(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_TXT);
        
        if (!$records) {
            return [
                'valid' => false,
                'message' => 'No TXT records found',
                'record' => null,
            ];
        }

        foreach ($records as $record) {
            if (isset($record['txt']) && strpos($record['txt'], 'v=spf1') === 0) {
                $hasSES = strpos($record['txt'], 'include:amazonses.com') !== false;
                
                return [
                    'valid' => true,
                    'hasSES' => $hasSES,
                    'record' => $record['txt'],
                    'message' => $hasSES ? 'SPF configured with Amazon SES' : 'SPF found but no Amazon SES',
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'No SPF record found',
            'record' => null,
            'suggestion' => 'v=spf1 include:amazonses.com ~all',
        ];
    }

    /**
     * Valida registros DKIM de um domínio
     * 
     * @param string $domain Domínio a validar
     * @param array  $selectors Seletores DKIM a verificar
     * 
     * @return array Resultado da validação
     */
    public function validateDKIM(string $domain, array $selectors = []): array
    {
        $results = [];
        
        // Se não forneceu seletores, tenta os padrões da AWS
        if (empty($selectors)) {
            $selectors = $this->generateAWSDKIMSelectors($domain);
        }

        foreach ($selectors as $selector) {
            $dkimDomain = $selector . '._domainkey.' . $domain;
            $records = @dns_get_record($dkimDomain, DNS_CNAME);
            
            if ($records && isset($records[0]['target'])) {
                $results[$selector] = [
                    'valid' => true,
                    'target' => $records[0]['target'],
                ];
            } else {
                $results[$selector] = [
                    'valid' => false,
                    'target' => null,
                ];
            }
        }

        $allValid = !empty($results) && count(array_filter($results, fn($r) => $r['valid'])) === count($results);

        return [
            'valid' => $allValid,
            'selectors' => $results,
            'message' => $allValid ? 'All DKIM records configured' : 'DKIM records missing or incomplete',
        ];
    }

    /**
     * Valida registro DMARC de um domínio
     * 
     * @param string $domain Domínio a validar
     * 
     * @return array Resultado da validação
     */
    public function validateDMARC(string $domain): array
    {
        $dmarcDomain = '_dmarc.' . $domain;
        $records = @dns_get_record($dmarcDomain, DNS_TXT);
        
        if (!$records) {
            return [
                'valid' => false,
                'message' => 'No DMARC record found',
                'record' => null,
                'suggestion' => 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' . $domain,
            ];
        }

        foreach ($records as $record) {
            if (isset($record['txt']) && strpos($record['txt'], 'v=DMARC1') === 0) {
                // Parse DMARC record
                $policy = $this->parseDMARCPolicy($record['txt']);
                
                return [
                    'valid' => true,
                    'record' => $record['txt'],
                    'policy' => $policy,
                    'message' => 'DMARC configured with policy: ' . $policy['p'],
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'DMARC record found but invalid format',
            'record' => null,
        ];
    }

    /**
     * Valida registros MX de um domínio
     * 
     * @param string $domain Domínio a validar
     * 
     * @return array Resultado da validação
     */
    public function validateMX(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_MX);
        
        if (!$records || empty($records)) {
            return [
                'valid' => false,
                'message' => 'No MX records found',
                'records' => [],
            ];
        }

        $mxRecords = array_map(function($record) {
            return [
                'priority' => $record['pri'],
                'target' => $record['target'],
            ];
        }, $records);

        // Ordena por prioridade
        usort($mxRecords, fn($a, $b) => $a['priority'] - $b['priority']);

        return [
            'valid' => true,
            'message' => count($mxRecords) . ' MX record(s) found',
            'records' => $mxRecords,
        ];
    }

    /**
     * Valida todos os registros DNS de um domínio
     * 
     * @param string $domain Domínio a validar
     * @param array  $dkimSelectors Seletores DKIM (opcional)
     * 
     * @return array Resultado completo da validação
     */
    public function validateAll(string $domain, array $dkimSelectors = []): array
    {
        return [
            'domain' => $domain,
            'spf' => $this->validateSPF($domain),
            'dkim' => $this->validateDKIM($domain, $dkimSelectors),
            'dmarc' => $this->validateDMARC($domain),
            'mx' => $this->validateMX($domain),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Gera seletores DKIM padrão da AWS para um domínio
     * 
     * @param string $domain Domínio
     * 
     * @return array Lista de seletores
     */
    private function generateAWSDKIMSelectors(string $domain): array
    {
        // AWS SES gera 3 seletores DKIM
        // Formato: {hash}._domainkey.{domain}
        // Como não temos o hash real, retornamos array vazio
        // O usuário deve fornecer os seletores da AWS
        return [];
    }

    /**
     * Faz parse de uma política DMARC
     * 
     * @param string $record Registro DMARC
     * 
     * @return array Política parseada
     */
    private function parseDMARCPolicy(string $record): array
    {
        $policy = [];
        $parts = explode(';', $record);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $policy[trim($key)] = trim($value);
            }
        }

        return $policy;
    }

    /**
     * Gera instruções de configuração DNS para um domínio
     * 
     * @param string $domain Domínio
     * @param array  $dkimTokens Tokens DKIM da AWS (opcional)
     * @param string|null $verificationToken Token TXT da verificação de domínio
     *
     * @return array Instruções de configuração
     */
    public function generateDNSInstructions(string $domain, array $dkimTokens = [], ?string $verificationToken = null): array
    {
        $instructions = [
            'domain_verification' => [
                'type' => 'TXT',
                'name' => '_amazonses.' . $domain,
                'value' => $verificationToken ?? 'Token indisponível. Reexecute a verificação.',
                'ttl' => 300,
                'description' => 'Registro TXT necessário para validar o domínio no Amazon SES',
            ],
            'spf' => [
                'type' => 'TXT',
                'name' => '@',
                'value' => 'v=spf1 include:amazonses.com ~all',
                'ttl' => 3600,
                'description' => 'SPF record to authorize Amazon SES',
            ],
            'dmarc' => [
                'type' => 'TXT',
                'name' => '_dmarc',
                'value' => 'v=DMARC1; p=quarantine; rua=mailto:dmarc@' . $domain,
                'ttl' => 3600,
                'description' => 'DMARC policy for email authentication',
            ],
            'mx' => [
                'type' => 'MX',
                'name' => '@',
                'value' => '10 inbound-smtp.' . $this->getAWSRegion() . '.amazonaws.com',
                'ttl' => 3600,
                'description' => 'MX record for receiving emails (optional)',
            ],
        ];

        // Adiciona registros DKIM se fornecidos
        if (!empty($dkimTokens)) {
            $instructions['dkim'] = [];
            foreach ($dkimTokens as $index => $token) {
                $instructions['dkim'][] = [
                    'type' => 'CNAME',
                    'name' => $token . '._domainkey',
                    'value' => $token . '.dkim.amazonses.com',
                    'ttl' => 3600,
                    'description' => 'DKIM record ' . ($index + 1) . ' for email signing',
                ];
            }
        }

        return $instructions;
    }

    /**
     * Obtém região AWS configurada
     * 
     * @return string Região AWS
     */
    private function getAWSRegion(): string
    {
        return getenv('aws.ses.region') ?: 'us-east-1';
    }
}
