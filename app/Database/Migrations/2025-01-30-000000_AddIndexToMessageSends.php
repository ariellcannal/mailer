<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adiciona índice composto para otimizar verificação de duplicação
 * em message_sends (message_id, contact_id, resend_number)
 */
class AddIndexToMessageSends extends Migration
{
    /**
     * Adiciona índice composto para otimizar queries de duplicação
     */
    public function up(): void
    {
        // Verificar se índice já existe antes de criar
        $indexName = 'idx_message_contact_resend';
        
        // Criar índice composto
        $this->db->query("
            CREATE INDEX IF NOT EXISTS {$indexName} 
            ON message_sends (message_id, contact_id, resend_number)
        ");
        
        log_message('info', 'Índice composto criado em message_sends para otimização de duplicação');
    }

    /**
     * Remove o índice composto
     */
    public function down(): void
    {
        $indexName = 'idx_message_contact_resend';
        
        // Remover índice se existir
        $this->db->query("DROP INDEX IF EXISTS {$indexName} ON message_sends");
        
        log_message('info', 'Índice composto removido de message_sends');
    }
}
