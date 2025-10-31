-- Sistema de Email Marketing - Database Schema
-- CodeIgniter 4 + PHP 8.1
-- MySQL/MariaDB

-- Tabela de usuários (autenticação)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) NULL UNIQUE,
    passkey_credential_id VARCHAR(255) NULL UNIQUE,
    email VARCHAR(320) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    avatar VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de sessões
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    timestamp INT UNSIGNED DEFAULT 0,
    data BLOB NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de contatos
CREATE TABLE IF NOT EXISTS contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(320) NOT NULL UNIQUE,
    name VARCHAR(255) NULL,
    quality_score TINYINT UNSIGNED DEFAULT 3 COMMENT '1-5 score based on engagement',
    total_opens INT UNSIGNED DEFAULT 0,
    total_clicks INT UNSIGNED DEFAULT 0,
    avg_open_time INT UNSIGNED DEFAULT 0 COMMENT 'Average seconds to open',
    last_open_date DATETIME NULL,
    last_click_date DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    opted_out TINYINT(1) DEFAULT 0,
    opted_out_at DATETIME NULL,
    bounced TINYINT(1) DEFAULT 0,
    bounce_type VARCHAR(50) NULL COMMENT 'hard, soft, complaint',
    bounced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_quality_score (quality_score),
    INDEX idx_opted_out (opted_out),
    INDEX idx_bounced (bounced)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de campos personalizados de contatos
CREATE TABLE IF NOT EXISTS contact_custom_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_contact_field (contact_id, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de listas de contatos
CREATE TABLE IF NOT EXISTS contact_lists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_contacts INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento contatos-listas (N:N)
CREATE TABLE IF NOT EXISTS contact_list_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_id INT UNSIGNED NOT NULL,
    list_id INT UNSIGNED NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (list_id) REFERENCES contact_lists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact_list (contact_id, list_id),
    INDEX idx_list_contact (list_id, contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de campanhas
CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    total_messages INT UNSIGNED DEFAULT 0,
    total_sends INT UNSIGNED DEFAULT 0,
    total_opens INT UNSIGNED DEFAULT 0,
    total_clicks INT UNSIGNED DEFAULT 0,
    total_bounces INT UNSIGNED DEFAULT 0,
    total_optouts INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de remetentes
CREATE TABLE IF NOT EXISTS senders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(320) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    ses_verified TINYINT(1) DEFAULT 0,
    ses_verification_token VARCHAR(255) NULL,
    dkim_tokens TEXT NULL,
    dkim_verified TINYINT(1) DEFAULT 0,
    spf_verified TINYINT(1) DEFAULT 0,
    dmarc_verified TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de domínios de links
CREATE TABLE IF NOT EXISTS link_domains (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    is_verified TINYINT(1) DEFAULT 0,
    dns_record VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates
CREATE TABLE IF NOT EXISTS templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    html_content LONGTEXT NOT NULL,
    thumbnail VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mensagens
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    sender_id INT UNSIGNED NOT NULL,
    link_domain_id INT UNSIGNED NULL,
    template_id INT UNSIGNED NULL,
    subject VARCHAR(500) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    reply_to VARCHAR(320) NULL,
    html_content LONGTEXT NOT NULL,
    has_optout_link TINYINT(1) DEFAULT 0,
    optout_link_visible TINYINT(1) DEFAULT 0,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    total_recipients INT UNSIGNED DEFAULT 0,
    total_sent INT UNSIGNED DEFAULT 0,
    total_opens INT UNSIGNED DEFAULT 0,
    total_clicks INT UNSIGNED DEFAULT 0,
    total_bounces INT UNSIGNED DEFAULT 0,
    total_optouts INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (sender_id) REFERENCES senders(id) ON DELETE RESTRICT,
    FOREIGN KEY (link_domain_id) REFERENCES link_domains(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE SET NULL,
    INDEX idx_campaign (campaign_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de regras de reenvio
CREATE TABLE IF NOT EXISTS resend_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    resend_number TINYINT UNSIGNED NOT NULL COMMENT '1, 2, 3...',
    hours_after INT UNSIGNED NOT NULL COMMENT 'Hours after previous send',
    subject_override VARCHAR(500) NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    total_sent INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message_resend (message_id, resend_number),
    INDEX idx_status_scheduled (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de envios individuais
CREATE TABLE IF NOT EXISTS message_sends (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    contact_id INT UNSIGNED NOT NULL,
    resend_number TINYINT UNSIGNED DEFAULT 0 COMMENT '0=original, 1+=resend',
    tracking_hash VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'sent', 'bounced', 'complained') DEFAULT 'pending',
    sent_at DATETIME NULL,
    opened TINYINT(1) DEFAULT 0,
    first_open_at DATETIME NULL,
    total_opens INT UNSIGNED DEFAULT 0,
    last_open_at DATETIME NULL,
    clicked TINYINT(1) DEFAULT 0,
    first_click_at DATETIME NULL,
    total_clicks INT UNSIGNED DEFAULT 0,
    last_click_at DATETIME NULL,
    bounced_at DATETIME NULL,
    bounce_type VARCHAR(50) NULL,
    bounce_reason TEXT NULL,
    complained_at DATETIME NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_message (message_id),
    INDEX idx_contact (contact_id),
    INDEX idx_tracking (tracking_hash),
    INDEX idx_status (status),
    INDEX idx_opened (opened),
    INDEX idx_clicked (clicked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de aberturas (tracking detalhado)
CREATE TABLE IF NOT EXISTS message_opens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    send_id INT UNSIGNED NOT NULL,
    opened_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    FOREIGN KEY (send_id) REFERENCES message_sends(id) ON DELETE CASCADE,
    INDEX idx_send (send_id),
    INDEX idx_opened_at (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cliques (tracking detalhado)
CREATE TABLE IF NOT EXISTS message_clicks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    send_id INT UNSIGNED NOT NULL,
    link_url TEXT NOT NULL,
    link_hash VARCHAR(64) NOT NULL,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    FOREIGN KEY (send_id) REFERENCES message_sends(id) ON DELETE CASCADE,
    INDEX idx_send (send_id),
    INDEX idx_link_hash (link_hash),
    INDEX idx_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de opt-outs
CREATE TABLE IF NOT EXISTS optouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED NULL,
    opted_out_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_contact (contact_id),
    INDEX idx_message (message_id),
    INDEX idx_opted_out_at (opted_out_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lista de supressão global
CREATE TABLE IF NOT EXISTS suppression_list (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(320) NOT NULL UNIQUE,
    reason ENUM('bounce', 'complaint', 'manual', 'optout') NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    INDEX idx_email (email),
    INDEX idx_reason (reason)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de atividades
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão do sistema
INSERT INTO system_settings (setting_key, setting_value) VALUES
('aws_ses_region', 'us-east-1'),
('aws_ses_access_key', ''),
('aws_ses_secret_key', ''),
('daily_send_limit', '50000'),
('hourly_send_limit', '10000'),
('throttle_rate', '14'),
('google_oauth_client_id', ''),
('google_oauth_client_secret', ''),
('site_name', 'Email Marketing System'),
('timezone', 'America/Sao_Paulo')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
