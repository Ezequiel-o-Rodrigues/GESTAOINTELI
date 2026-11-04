-- Migração aditiva para contas de garçom e identificação das comandas.
-- Faça backup do banco antes de executar. Pode ser reexecutada com segurança.

ALTER TABLE `usuarios`
    MODIFY COLUMN `perfil` ENUM('admin', 'caixa', 'estoque', 'garcom') NOT NULL DEFAULT 'caixa';

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'comandas'
       AND COLUMN_NAME = 'cliente_nome') = 0,
    'ALTER TABLE `comandas` ADD COLUMN `cliente_nome` varchar(120) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'comandas'
       AND COLUMN_NAME = 'mesa_numero') = 0,
    'ALTER TABLE `comandas` ADD COLUMN `mesa_numero` varchar(20) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'comandas'
       AND COLUMN_NAME = 'garcom_usuario_id') = 0,
    'ALTER TABLE `comandas` ADD COLUMN `garcom_usuario_id` int(11) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'comandas'
       AND INDEX_NAME = 'idx_comandas_garcom_status') = 0,
    'CREATE INDEX `idx_comandas_garcom_status` ON `comandas` (`garcom_usuario_id`, `status`)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- Verificação após execução:
-- SHOW COLUMNS FROM `usuarios` LIKE 'perfil';
-- SHOW COLUMNS FROM `comandas` LIKE 'cliente_nome';
-- SHOW COLUMNS FROM `comandas` LIKE 'mesa_numero';
-- SHOW COLUMNS FROM `comandas` LIKE 'garcom_usuario_id';
