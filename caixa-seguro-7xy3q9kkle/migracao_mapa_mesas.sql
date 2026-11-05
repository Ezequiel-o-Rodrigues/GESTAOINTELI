-- Migração: Mapa Visual de Mesas
-- Execute uma única vez. Seguro para rodar em produção com dados existentes.

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

-- 2. FK opcional em comandas (não quebra nenhum INSERT legado)
ALTER TABLE `comandas`
  ADD COLUMN IF NOT EXISTS `mesa_id` INT NULL DEFAULT NULL AFTER `mesa_numero`,
  ADD CONSTRAINT `fk_comandas_mesas`
    FOREIGN KEY (`mesa_id`) REFERENCES `mesas`(`id`) ON DELETE SET NULL;

-- 3. Chave de controle global (desabilitado por padrão)
INSERT INTO `configuracoes_sistema` (`chave`, `valor`, `descricao`)
  VALUES ('usar_mapa_mesas', '0', 'Habilita o mapa visual de mesas nos módulos Garçom e Caixa')
  ON DUPLICATE KEY UPDATE `chave` = `chave`;
