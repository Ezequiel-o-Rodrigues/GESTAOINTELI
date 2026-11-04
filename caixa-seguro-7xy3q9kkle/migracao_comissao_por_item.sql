-- Execute após a migração do módulo Garçom. Faça backup do banco antes.
-- As colunas de autoria permanecem nulas nos registros antigos, cuja autoria por item não é conhecida.

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'garcom_usuario_id') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `garcom_usuario_id` int(11) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'garcom_nome') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `garcom_nome` varchar(100) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'comissao_percentual') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `comissao_percentual` decimal(7,6) NOT NULL DEFAULT 0.000000',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'comissao_valor') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `comissao_valor` decimal(10,2) NOT NULL DEFAULT 0.00',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'cancelado_em') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `cancelado_em` datetime DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND COLUMN_NAME = 'cancelado_por') = 0,
    'ALTER TABLE `itens_comanda` ADD COLUMN `cancelado_por` int(11) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens_comanda'
       AND INDEX_NAME = 'idx_itens_comanda_garcom_data') = 0,
    'CREATE INDEX `idx_itens_comanda_garcom_data` ON `itens_comanda` (`garcom_usuario_id`, `created_at`, `cancelado_em`)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

INSERT INTO `configuracoes_sistema` (`chave`, `valor`, `descricao`)
VALUES ('commission_rate', '0.03', 'Taxa de comissão dos garçons')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

INSERT INTO `configuracoes_sistema` (`chave`, `valor`, `descricao`)
VALUES ('service_fee_enabled', '0', 'Repassar as comissões dos itens ao cliente')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- A taxa padrão de comissão é definida pelo administrador em Administração > Configurações.
