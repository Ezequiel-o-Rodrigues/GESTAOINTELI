-- Associa imagens legadas somente quando o produto pode ser identificado com segurança.
-- Execute após enviar a pasta public/images/menu para o servidor.
-- Se a tabela produto_cards ainda não existir, execute migracao_cards_cardapio.sql primeiro.
UPDATE produtos
SET imagem = CASE nome
    WHEN 'Frango com Bacon' THEN 'espetos/espetos_12.jpg'
    WHEN 'Contra Filé' THEN 'espetos/espetos_19.jpg'
    WHEN 'Provolone' THEN 'espetos/espetos_21.jpg'
    WHEN 'Coração' THEN 'espetos/espetos_22.jpg'
    WHEN 'Linguiça de porco' THEN 'espetos/espetos_20.jpg'
    WHEN 'Macarrão M' THEN 'porcoes/porcoes_8.jpg'
    WHEN 'Macarrão G' THEN 'porcoes/porcoes_9.jpg'
    WHEN 'Salada M' THEN 'porcoes/porcoes_10.jpg'
    WHEN 'Salada G' THEN 'porcoes/porcoes_11.jpg'
    WHEN 'Feijão Tropeiro M' THEN 'porcoes/porcoes_6.jpg'
    WHEN 'Feijão Tropeiro G' THEN 'porcoes/porcoes_6.jpg'
    WHEN 'Água Sem Gás' THEN 'bebidas/bebidas_24.jpg'
    WHEN 'Coca-Cola KS' THEN 'bebidas/bebidas_62.jpg'
    WHEN 'Coca-Cola 600ml' THEN 'bebidas/bebidas_16.jpeg'
    WHEN 'H2OH' THEN 'bebidas/bebidas_23.jpg'
    WHEN 'Mineiro' THEN 'bebidas/bebidas_38.jpg'
    WHEN 'H2OH 1,5L' THEN 'bebidas/bebidas_40.jpg'
    WHEN 'Coca-Cola 2L' THEN 'bebidas/bebidas_36.jpg'
    WHEN 'Sucos Life 900ml' THEN 'bebidas/bebidas_48.jpg'
    WHEN 'Spaten' THEN 'cervejas/cervejas_5.jpeg'
END
WHERE (imagem IS NULL OR TRIM(imagem) = '')
  AND nome IN (
      'Frango com Bacon',
      'Macarrão M',
      'Macarrão G',
      'Salada M',
      'Salada G',
      'Feijão Tropeiro M',
      'Feijão Tropeiro G',
      'Água Sem Gás',
      'Coca-Cola KS',
      'Coca-Cola 600ml',
      'H2OH',
      'Mineiro',
      'H2OH 1,5L',
      'Coca-Cola 2L',
      'Sucos Life 900ml',
      'Spaten',
      'Contra Filé',
      'Provolone',
      'Coração',
      'Linguiça de porco'
  );

-- Apresenta os sabores de lata como cards do produto agrupado no caixa.
INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, imagem, ordem)
SELECT p.id, 'Guaraná Antarctica', NULL, 'bebidas/bebidasguaranaantarticalata_12.png', 1
FROM produtos p
WHERE p.nome = 'Diversas latas 350ml'
  AND NOT EXISTS (
      SELECT 1 FROM produto_cards pc
      WHERE pc.produto_id = p.id AND pc.nome_exibicao = 'Guaraná Antarctica'
  );

INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, imagem, ordem)
SELECT p.id, 'Pepsi', NULL, 'bebidas/bebidaspepsilata_9.jpg', 2
FROM produtos p
WHERE p.nome = 'Diversas latas 350ml'
  AND NOT EXISTS (
      SELECT 1 FROM produto_cards pc
      WHERE pc.produto_id = p.id AND pc.nome_exibicao = 'Pepsi'
  );

INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, imagem, ordem)
SELECT p.id, 'Soda Limonada', NULL, 'bebidas/bebidassodalata_10.jpg', 3
FROM produtos p
WHERE p.nome = 'Diversas latas 350ml'
  AND NOT EXISTS (
      SELECT 1 FROM produto_cards pc
      WHERE pc.produto_id = p.id AND pc.nome_exibicao = 'Soda Limonada'
  );

INSERT INTO produto_cards (produto_id, nome_exibicao, descricao, imagem, ordem)
SELECT p.id, 'Sukita', NULL, 'bebidas/bebidassukitalata_8.jpg', 4
FROM produtos p
WHERE p.nome = 'Diversas latas 350ml'
  AND NOT EXISTS (
      SELECT 1 FROM produto_cards pc
      WHERE pc.produto_id = p.id AND pc.nome_exibicao = 'Sukita'
  );

SELECT id, nome, imagem
FROM produtos
WHERE imagem IS NOT NULL AND TRIM(imagem) <> ''
ORDER BY id;
