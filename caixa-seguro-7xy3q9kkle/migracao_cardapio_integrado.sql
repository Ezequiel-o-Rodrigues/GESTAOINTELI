-- Migração aditiva e reexecutável para o cardápio integrado.
-- Antes de executar, faça backup do banco. Esta migração não altera produtos,
-- preços, categorias, estoque, comandas ou vendas existentes.

SET @migration_sql = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'produtos'
          AND COLUMN_NAME = 'nome_publico'
    ) = 0,
    'ALTER TABLE `produtos` ADD COLUMN `nome_publico` varchar(100) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'produtos'
          AND COLUMN_NAME = 'descricao_publica'
    ) = 0,
    'ALTER TABLE `produtos` ADD COLUMN `descricao_publica` text DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'produtos'
          AND COLUMN_NAME = 'imagem_url'
    ) = 0,
    'ALTER TABLE `produtos` ADD COLUMN `imagem_url` varchar(2048) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS `produto_cards` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `produto_id` int(11) NOT NULL,
    `nome_exibicao` varchar(100) NOT NULL,
    `descricao` text DEFAULT NULL,
    `imagem` varchar(255) DEFAULT NULL,
    `imagem_url` varchar(2048) DEFAULT NULL,
    `ordem` int(10) unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_produto_cards_produto_ordem` (`produto_id`, `ordem`, `id`),
    CONSTRAINT `fk_produto_cards_produto`
        FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @migration_sql = IF(
    (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'produto_cards'
          AND COLUMN_NAME = 'imagem_url'
    ) = 0,
    'ALTER TABLE `produto_cards` ADD COLUMN `imagem_url` varchar(2048) DEFAULT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- Verificação após execução:
-- SHOW COLUMNS FROM `produtos` LIKE 'nome_publico';
-- SHOW COLUMNS FROM `produtos` LIKE 'descricao_publica';
-- SHOW COLUMNS FROM `produtos` LIKE 'imagem_url';
-- SHOW TABLES LIKE 'produto_cards';
-- SHOW COLUMNS FROM `produto_cards` LIKE 'imagem_url';

-- Reversão opcional, somente se puder descartar todas as apresentações:
-- DROP TABLE `produto_cards`;
-- ALTER TABLE `produtos`
--     DROP COLUMN `descricao_publica`,
--     DROP COLUMN `imagem_url`,
--     DROP COLUMN `nome_publico`;
