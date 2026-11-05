# Regras de negócio do GestãoInteli

Este documento resume as regras mais relevantes observadas no código da aplicação de caixa, atendimento, estoque, administração e cardápio. As referências apontam para os arquivos e linhas que implementam cada comportamento.

> **Escopo e fonte da verdade:** o texto descreve o comportamento do código-fonte atual, não necessariamente uma política formal aprovada. Há arquivos de migração e cópias de banco com valores iniciais diferentes; quando houver divergência, confirme a configuração do banco em produção e as migrações aplicadas.

## 1. Perfis e permissões

1. **Cada perfil tem acesso a módulos específicos.** Administradores podem acessar administração, caixa, estoque, relatórios e ajuda; caixa acessa caixa, relatórios e ajuda; estoque acessa estoque, relatórios e ajuda; garçons acessam atendimento e ajuda. Páginas de módulo verificam a sessão e o perfil antes de exibir conteúdo.  
   Referência: [includes/permissions.php](./includes/permissions.php#L5-L35).

2. **As operações da API também têm uma lista de perfis permitidos.** Por exemplo, finalizar comandas é permitido a administração e caixa; registrar entradas de estoque é permitido a administração e estoque; garçons podem consultar e operar o atendimento destinado ao seu perfil. Regras de tela e de API são verificadas separadamente.  
   Referência: [includes/api-auth.php](./includes/api-auth.php#L4-L60).

3. **A administração de usuários restringe os perfis disponíveis.** Os perfis aceitos são `admin`, `caixa`, `estoque` e `garcom`; novas senhas e alterações de senha precisam ter pelo menos oito caracteres e são armazenadas com hash. O administrador não pode retirar o próprio acesso nem desativar/remover o último administrador ativo.  
   Referência: [modules/admin/index.php](./modules/admin/index.php#L11-L122).

## 2. Produtos, categorias e cardápio

4. **Somente produtos ativos podem ser vendidos ou lançados em uma comanda.** A listagem do caixa e a do garçom filtram `produtos.ativo = 1`; a API que insere itens também rejeita produtos inativos. A ativação/desativação altera esse mesmo campo do produto.  
   Referências: [modules/caixa/index.php](./modules/caixa/index.php#L18-L29), [modules/garcom/index.php](./modules/garcom/index.php#L14-L24), [api/adicionar_item.php](./api/adicionar_item.php#L66-L74), [api/toggle_produto.php](./api/toggle_produto.php#L15-L45).

5. **Produto comercializado precisa ter nome, categoria, preço e dados de estoque válidos.** O cadastro limita o nome a 100 caracteres, exige preço não negativo com até duas casas decimais, categoria válida e estoque mínimo/inicial não negativos. Os campos públicos do cardápio e as imagens têm validações próprias quando enviados.  
   Referência: [api/salvar_produto.php](./api/salvar_produto.php#L75-L143).

6. **Categorias são compartilhadas entre os módulos.** Alterar uma categoria requer confirmação de backup; excluí-la só é permitido quando não há produtos associados. Assim, mudanças de categoria repercutem no estoque, caixa e relatórios que usam essa classificação.  
   Referência: [modules/cardapio/salvar_cardapio.php](./modules/cardapio/salvar_cardapio.php#L25-L87).

7. **O cardápio público é montado com produtos ativos e suas categorias.** Nome público, descrição, imagens e cards de apresentação são mantidos junto ao cadastro do produto, mas o preço usado pelo caixa é o preço do produto no sistema.  
   Referências: [modules/cardapio/index.php](./modules/cardapio/index.php#L1-L37), [api/salvar_produto.php](./api/salvar_produto.php#L100-L168), [modules/caixa/index.php](./modules/caixa/index.php#L609-L639).

## 3. Comandas e atendimento

8. **Uma comanda é criada em estado aberto e só pode ser operada enquanto permanecer aberta.** O caixa pode iniciar uma comanda sem vínculo de garçom; o fluxo do garçom exige informar nome do cliente ou número de mesa (ao menos um), limita o nome a 120 caracteres e aceita mesa numérica de até 20 caracteres. A lista compartilhada exibe comandas abertas.  
   Referências: [api/nova_comanda.php](./api/nova_comanda.php#L8-L28), [api/garcom_comandas.php](./api/garcom_comandas.php#L11-L23), [api/garcom_comandas.php](./api/garcom_comandas.php#L39-L70).

9. **O atendimento dos garçons é cooperativo.** Um garçom autenticado pode visualizar e atender qualquer comanda aberta, não somente a que abriu; a API confirma que a comanda está aberta, mas não exige que ela pertença ao usuário que está fazendo a operação. O fechamento fica reservado ao caixa e à administração.  
   Referências: [modules/garcom/index.php](./modules/garcom/index.php#L64-L90), [includes/garcom-comanda.php](./includes/garcom-comanda.php#L4-L14), [includes/api-auth.php](./includes/api-auth.php#L9-L18).

10. **O vínculo de identificação da comanda não define quem recebe comissão.** O texto da interface do caixa informa que cada item é atribuído ao garçom autenticado que o lança. A atribuição é gravada no item; portanto, uma comanda identificada com determinado garçom pode conter itens lançados por outros garçons.  
    Referências: [modules/caixa/index.php](./modules/caixa/index.php#L669-L681), [api/garcom_itens.php](./api/garcom_itens.php#L88-L125), [includes/comissao-comanda.php](./includes/comissao-comanda.php#L15-L55).

11. **A quantidade de cada item deve ser um inteiro positivo, e o produto precisa estar ativo.** O sistema grava no item o preço unitário vigente no lançamento e calcula o subtotal como preço unitário multiplicado pela quantidade. O total da comanda aberta é recalculado pela soma dos subtotais dos itens não cancelados e dos itens livres.  
    Referências: [api/adicionar_item.php](./api/adicionar_item.php#L20-L36), [includes/comissao-comanda.php](./includes/comissao-comanda.php#L28-L72).

12. **Itens podem ser cancelados sem apagar o histórico.** O cancelamento marca data e usuário responsável; somente itens ainda não cancelados daquela comanda podem ser cancelados. Ao reduzir a quantidade, o sistema cancela o registro original e cria outro com a quantidade restante; ao aumentar, cria um registro para o incremento.  
    Referências: [includes/comissao-comanda.php](./includes/comissao-comanda.php#L75-L84), [includes/comissao-comanda.php](./includes/comissao-comanda.php#L87-L158), [api/garcom_remover_item.php](./api/garcom_remover_item.php#L17-L40).

13. **O estoque é reservado logicamente pelas comandas abertas.** Ao adicionar ou aumentar um item, o sistema soma a quantidade daquele produto em todas as comandas abertas e não canceladas. A nova quantidade é recusada se ultrapassar o estoque atual.  
    Referências: [api/adicionar_item.php](./api/adicionar_item.php#L66-L87), [api/garcom_itens.php](./api/garcom_itens.php#L92-L110), [includes/comissao-comanda.php](./includes/comissao-comanda.php#L113-L132).

## 4. Comissões, taxa de serviço e fechamento

14. **A comissão incide sobre os itens lançados por garçons.** A taxa é configurada em percentual, limitada administrativamente entre 0% e 100%, e gravada como fração decimal. O valor calculado por item é o subtotal multiplicado pela taxa, arredondado para duas casas decimais. Itens lançados por usuários que não são garçons recebem comissão zero.  
    Referências: [modules/admin/index.php](./modules/admin/index.php#L125-L153), [includes/comissao-comanda.php](./includes/comissao-comanda.php#L2-L55), [api/adicionar_item.php](./api/adicionar_item.php#L59-L70).

15. **Repassar comissão ao cliente é uma configuração opcional.** Quando habilitada, a taxa de serviço da comanda fechada corresponde à soma das comissões dos itens ativos; quando desabilitada, a taxa cobrada do cliente é zero. O valor da venda (`valor_total`) não inclui essa taxa; o total a pagar é `valor_total + taxa_gorjeta`.  
    Referências: [modules/admin/index.php](./modules/admin/index.php#L125-L153), [api/finalizar_comanda.php](./api/finalizar_comanda.php#L45-L72), [api/finalizar_comanda.php](./api/finalizar_comanda.php#L130-L138).

16. **O fechamento só acontece uma vez e é permitido a caixa/administração.** A API bloqueia a comanda durante a operação, exige que exista e esteja aberta, registra o usuário que fechou e grava as datas de venda e finalização. O total inclui itens de catálogo não cancelados e itens livres. Se outra operação já fechou a comanda, o fechamento é recusado.  
    Referências: [includes/api-auth.php](./includes/api-auth.php#L9-L18), [api/finalizar_comanda.php](./api/finalizar_comanda.php#L33-L72).

17. **Ao fechar, itens de catálogo são baixados do estoque e a movimentação de saída é registrada.** Essas alterações, o fechamento e a criação do comprovante acontecem na mesma transação. Itens livres entram no total calculado, mas não fazem parte da baixa de estoque de produtos de catálogo.  
    Referência: [api/finalizar_comanda.php](./api/finalizar_comanda.php#L74-L128).

18. **O comprovante é gerado e armazenado no fechamento.** Ele inclui dados de comanda, cliente/mesa quando disponíveis, identificação do garçom, itens de catálogo não cancelados, subtotal, serviço quando aplicável e total a pagar. A interface do caixa oferece impressão ou opção de não imprimir.  
    Referências: [api/finalizar_comanda.php](./api/finalizar_comanda.php#L92-L128), [api/finalizar_comanda.php](./api/finalizar_comanda.php#L157-L220), [modules/caixa/index.php](./modules/caixa/index.php#L690-L718).

## 5. Estoque e perdas

19. **Entradas aumentam o saldo e registram movimentação.** O endpoint de entrada cria uma movimentação do tipo `entrada` e soma a quantidade ao estoque atual. O inventário físico, por sua vez, substitui o saldo do sistema pela quantidade contada, usando a rotina de inventário.  
    Referências: [api/registrar_entrada.php](./api/registrar_entrada.php#L14-L29), [modules/estoque/index.php](./modules/estoque/index.php#L20-L35), [u903648047_sis_restaurant.sql](./u903648047_sis_restaurant.sql#L768-L810).

20. **Produtos ativos geram alerta quando estão zerados ou no mínimo de estoque.** Saldo igual a zero é apresentado como crítico; saldo maior que zero e menor ou igual ao mínimo gera aviso.  
    Referência: [modules/estoque/index.php](./modules/estoque/index.php#L70-L80).

21. **A detecção automática de perdas compara entradas, vendas fechadas e saldo atual.** A diferença positiva é tratada como quantidade perdida e valorizada pelo preço atual do produto. Antes de registrar, a rotina evita duplicar uma perda idêntica ainda não visualizada.  
    Referência: [api/detectar_perdas.php](./api/detectar_perdas.php#L13-L75).

## 6. Relatórios

22. **Os relatórios de vendas consideram apenas comandas fechadas no intervalo informado.** O agrupamento pode ser diário, semanal ou mensal; são apresentados quantidade de comandas, valor total, gorjetas/taxa de serviço e ticket médio. O ticket médio é calculado sobre `valor_total`, portanto não inclui a taxa de serviço.  
    Referência: [api/relatorio_vendas_periodo.php](./api/relatorio_vendas_periodo.php#L10-L75).

## 7. Pontos de atenção para validação operacional

- **O saldo não é novamente validado contra valores negativos durante o fechamento.** A API de fechamento registra a saída e subtrai a quantidade, com comentário explícito de que não bloqueia estoque negativo. A proteção principal é a reserva verificada quando itens são adicionados/aumentados; confirme se isso atende à operação e aos demais caminhos de alteração de estoque.  
  Referência: [api/finalizar_comanda.php](./api/finalizar_comanda.php#L74-L88).
- **O conteúdo do comprovante não acompanha a composição completa do total quando existem itens livres.** O total da comanda inclui `itens_livres`, mas a consulta de itens usada para gerar o comprovante busca somente `itens_comanda`.  
  Referência: [api/finalizar_comanda.php](./api/finalizar_comanda.php#L45-L46), [api/finalizar_comanda.php](./api/finalizar_comanda.php#L92-L124).
- **O valor inicial da comissão não é uniforme entre os artefatos versionados.** Quando não encontra configuração existente, a tela administrativa assume 3% e uma migração também insere `0.03`, enquanto um dump SQL contém `0.1`. O valor efetivo deve ser conferido na tabela `configuracoes_sistema` do ambiente em uso.  
  Referências: [modules/admin/index.php](./modules/admin/index.php#L214-L235), [migracao_comissao_por_item.sql](./migracao_comissao_por_item.sql#L78-L86), [sistema_restaurante (12).sql](./sistema_restaurante%20%2812%29.sql#L528-L542).
- **A fórmula de perdas depende da qualidade do histórico de entradas.** A detecção usa entradas registradas menos vendas concluídas menos saldo atual; valide se estoque inicial e ajustes/inventários estão representados nesse histórico antes de tratar a diferença como perda confirmada.  
  Referência: [api/detectar_perdas.php](./api/detectar_perdas.php#L13-L43).
