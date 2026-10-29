-- Associa imagens legadas somente quando o produto pode ser identificado com segurança.
-- Execute após enviar a pasta public/images/menu para o servidor.
UPDATE produtos
SET imagem = CASE nome
    WHEN 'espetos variados' THEN 'espetos/espetos_18.jpg'
    WHEN 'Frango com Bacon' THEN 'espetos/espetos_12.jpg'
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
      'espetos variados'
  );

SELECT id, nome, imagem
FROM produtos
WHERE imagem IS NOT NULL AND TRIM(imagem) <> ''
ORDER BY id;
