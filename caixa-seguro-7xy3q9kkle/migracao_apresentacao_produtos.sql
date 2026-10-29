-- Migração aditiva para apresentação pública de produtos.
-- Faça backup do banco antes de executar. Não altera nome, preço, categoria,
-- estoque, vendas ou opções existentes.
ALTER TABLE `produtos`
    ADD COLUMN `nome_publico` varchar(100) DEFAULT NULL,
    ADD COLUMN `descricao_publica` text DEFAULT NULL;

-- Reversão (somente se os campos novos ainda não tiverem dados necessários):
-- ALTER TABLE `produtos`
--     DROP COLUMN `descricao_publica`,
--     DROP COLUMN `nome_publico`;
