-- Migração: Mapa Visual de Mesas
-- Pode ser executada novamente sem duplicar colunas, índices ou constraints.

-- 1. Tabela de mesas
CREATE TABLE IF NOT EXISTS `mesas` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `numero`     VARCHAR(20)  NOT NULL,
  `ambiente`   VARCHAR(50)  NOT NULL DEFAULT 'Salão Principal',
  `posicao_x`  INT          NOT NULL DEFAULT 0,
  `posicao_y`  INT          NOT NULL DEFAULT 0,
  `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_mesa_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Garantir as colunas antes de criar o vínculo com a mesa.
SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comandas'
     AND COLUMN_NAME = 'mesa_numero') = 0,
  'ALTER TABLE `comandas` ADD COLUMN `mesa_numero` VARCHAR(20) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comandas'
     AND COLUMN_NAME = 'mesa_id') = 0,
  'ALTER TABLE `comandas` ADD COLUMN `mesa_id` INT NULL DEFAULT NULL AFTER `mesa_numero`',
  'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- 3. Índice usado para determinar a ocupação da mesa.
SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comandas'
     AND INDEX_NAME = 'idx_comandas_mesa_status') = 0,
  'CREATE INDEX `idx_comandas_mesa_status` ON `comandas` (`mesa_id`, `status`)',
  'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- 4. FK opcional em comandas (não quebra nenhum INSERT legado).
SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comandas'
     AND CONSTRAINT_NAME = 'fk_comandas_mesas'
     AND CONSTRAINT_TYPE = 'FOREIGN KEY') = 0,
  'ALTER TABLE `comandas` ADD CONSTRAINT `fk_comandas_mesas` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

-- Associar registros existentes pelo número exato, sem sobrescrever vínculos.
UPDATE `comandas` c
INNER JOIN `mesas` m
  ON CONVERT(m.`numero` USING utf8mb4) COLLATE utf8mb4_unicode_ci
   = CONVERT(c.`mesa_numero` USING utf8mb4) COLLATE utf8mb4_unicode_ci
SET c.`mesa_id` = m.`id`
WHERE c.`mesa_id` IS NULL
  AND c.`mesa_numero` IS NOT NULL;

-- 5. Chave de controle global (desabilitado por padrão).
INSERT INTO `configuracoes_sistema` (`chave`, `valor`, `descricao`)
  VALUES ('usar_mapa_mesas', '0', 'Habilita o mapa visual de mesas nos módulos Garçom e Caixa')
  ON DUPLICATE KEY UPDATE `chave` = `chave`;
