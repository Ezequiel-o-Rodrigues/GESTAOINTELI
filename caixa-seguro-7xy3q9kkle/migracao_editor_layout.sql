-- Migração: Editor Interativo de Layout do Salão (TASK-ADMIN-04)
-- Segura para re-execução.

SET @s = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mesas' AND COLUMN_NAME = 'formato') = 0,
  "ALTER TABLE `mesas`
     ADD COLUMN `formato`  ENUM('quadrada','redonda','retangular') NOT NULL DEFAULT 'quadrada' AFTER `ambiente`,
     ADD COLUMN `largura`  INT NOT NULL DEFAULT 60 AFTER `posicao_y`,
     ADD COLUMN `altura`   INT NOT NULL DEFAULT 60 AFTER `largura`",
  'SELECT 1'
);
PREPARE _m FROM @s; EXECUTE _m; DEALLOCATE PREPARE _m;
