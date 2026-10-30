# Briefing — redesign do cardápio público

## Objetivo

Criar uma apresentação visual mais sofisticada para o cardápio público do Espetinho do Júnior, mantendo a leitura rápida, a navegação por categorias e a correspondência fiel com os produtos e preços cadastrados no sistema de caixa.

Este cardápio é uma **vitrine informativa**. Atualmente, ele não recebe pedidos nem calcula carrinho: o cliente consulta os itens e, para pedir, segue para o iFood ou WhatsApp.

## Como funciona hoje

1. A página pública consulta os produtos e categorias do mesmo banco usado pelo sistema de caixa.
2. Somente produtos marcados como **ativos** são exibidos. Produtos inativos não aparecem.
3. As categorias exibidas são as que têm produtos ativos. Cada categoria vira uma seção da página e um link na navegação fixa.
4. As categorias seguem uma ordem predefinida — Espetos, Porções, Jantinhas e Marmitas, Bebidas não alcoólicas, Cervejas e Outros — e as demais vêm depois em ordem alfabética.
5. Cada produto aparece como um card. Se houver subcategorias de apresentação cadastradas — por exemplo, sabores de uma bebida — cada uma também aparece como card, junto do card principal.
6. Nome, descrição e imagem de apresentação podem ser personalizados para o público. Se não houver nome público, usa-se o nome oficial do produto.
7. O preço exibido é sempre o preço oficial do produto no caixa, inclusive nos cards de subcategorias.
8. A página inclui uma chamada para delivery. Ela abre opções externas de pedido pelo iFood e WhatsApp, além do horário informado (18h às 23h). Há também um botão para voltar ao topo e um rodapé com contato e redes sociais.
9. O layout atual se adapta a celular, tablet e desktop; os cards ficam em grade e a navegação por categorias permanece acessível no topo durante a rolagem.

## Regras de negócio que não devem mudar

- **Uma única fonte de verdade:** preço, nome oficial, categoria, status ativo/inativo e vínculo com estoque vêm do cadastro do sistema de caixa. O cardápio não deve manter cópias independentes desses dados.
- **Preço do produto:** todas as subcategorias/cards derivados do mesmo produto mostram o mesmo preço oficial. Não criar preço próprio, adicional ou variação de preço para uma subcategoria sem uma decisão explícita do negócio e alteração correspondente no sistema.
- **Subcategorias são apresentação:** são subdivisões visuais de um produto (como sabores ou apresentações), não criam um novo produto, SKU, estoque, movimentação ou item de venda.
- **Ativação controla exibição:** produtos inativos ficam fora do cardápio público; produtos ativos aparecem na categoria vinculada no cadastro.
- **Categoria é compartilhada:** a categoria vem do cadastro de produtos e também é usada por estoque e relatórios. Não transformar a organização visual em uma alteração de categoria ou reclassificação dos dados.
- **Sem disponibilidade inferida pelo estoque:** a página atual não oculta produtos nem altera seu estado visual com base na quantidade em estoque. Não introduzir esse comportamento no redesign sem aprovação.
- **Pedido é externo:** a vitrine não tem carrinho, checkout ou envio de pedido ao caixa. Os canais atuais de pedido são os links externos do iFood e WhatsApp.
- **Conteúdo comercial existente:** manter os canais, contatos e horários atuais até que a empresa confirme qualquer mudança. Os links de redes sociais no rodapé estão atualmente como placeholders (`#`); não apresentá-los como canais funcionais sem destinos confirmados.

## O que pode evoluir visualmente

Tipografia, cores, identidade visual, espaçamentos, hierarquia, estilo dos cards, proporção e tratamento das imagens, organização responsiva e aparência da navegação podem ser redesenhados. A interação pode ser refinada desde que preserve:

- acesso às seções por categoria;
- identificação clara do produto, descrição (quando houver) e preço;
- distinção visual entre produto principal e suas subcategorias, sem sugerir preços ou estoques diferentes;
- acesso simples às opções de pedido por delivery;
- boa leitura e operação em telas pequenas.

## Imagens e conteúdo

- A apresentação aceita imagem própria ou URL HTTPS direta.
- A prioridade atual é: imagem local válida; depois URL HTTPS válida; por fim, imagem padrão.
- Se uma subcategoria não tiver imagem própria válida, a página tenta usar a imagem do produto principal; se nenhuma estiver disponível, usa a imagem padrão.
- Nome e descrição de apresentação são opcionais. Não substituir silenciosamente o preço, o status ou a categoria oficial por texto decorativo.
- Usar apenas imagens autorizadas para publicação.

## Pontos para confirmar com o negócio

- O topo usa “ESPETINHO DO JÚNIOR”, enquanto o rodapé usa “CHURRASCARIA DO JÚNIOR”. Confirmar a marca/nome que deve prevalecer.
- Confirmar se horário de delivery, telefone, endereço e destinos dos canais continuam corretos antes de destacá-los no novo layout.
- A ordem das categorias atualmente depende de nomes específicos. Se o negócio quiser reorganizá-la, essa decisão deve ser confirmada e tratada como regra explícita, não presumida pelo layout.

## Referências da implementação atual

- Página pública: [`public/cardapio.php`](./public/cardapio.php)
- Gerenciamento da apresentação: [`admin-seguro-7xy3q9bk/gerenciar_cardapio.php`](./admin-seguro-7xy3q9bk/gerenciar_cardapio.php)
- Salvamento e validações: [`caixa-seguro-7xy3q9kkle/api/salvar_produto.php`](./caixa-seguro-7xy3q9kkle/api/salvar_produto.php)
- Regras de resolução e upload de imagens: [`caixa-seguro-7xy3q9kkle/includes/menu-images.php`](./caixa-seguro-7xy3q9kkle/includes/menu-images.php)
- Estrutura de apresentação e subcategorias: [`caixa-seguro-7xy3q9kkle/migracao_cardapio_integrado.sql`](./caixa-seguro-7xy3q9kkle/migracao_cardapio_integrado.sql)
