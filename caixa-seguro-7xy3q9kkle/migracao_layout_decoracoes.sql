-- Migração: Decorações do Editor de Layout (linhas e textos)
CREATE TABLE IF NOT EXISTS `layout_decoracoes` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `ambiente`  VARCHAR(50)  NOT NULL DEFAULT 'Salão Principal',
  `tipo`      ENUM('linha','texto') NOT NULL,
  `x1`        INT NOT NULL DEFAULT 0,
  `y1`        INT NOT NULL DEFAULT 0,
  `x2`        INT NOT NULL DEFAULT 100,
  `y2`        INT NOT NULL DEFAULT 0,
  `texto`     VARCHAR(120) NULL,
  `cor`       VARCHAR(20)  NOT NULL DEFAULT '#94a3b8',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
