CREATE TABLE `produto_cards` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `produto_id` int(11) NOT NULL,
    `nome_exibicao` varchar(100) NOT NULL,
    `descricao` text DEFAULT NULL,
    `imagem` varchar(255) DEFAULT NULL,
    `ordem` int(10) unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_produto_cards_produto_ordem` (`produto_id`, `ordem`, `id`),
    CONSTRAINT `fk_produto_cards_produto`
        FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
