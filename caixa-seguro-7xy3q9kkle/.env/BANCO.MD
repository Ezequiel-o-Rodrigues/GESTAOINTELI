# Documentação Técnica: Banco de Dados `sis_caixa`

> Fonte: diagrama de entidade-relacionamento (DER) e exportação do esquema (PDF gerado em 03/11/2026). As chaves estrangeiras e regras `ON DELETE` abaixo refletem as constraints declaradas no esquema exportado.

## Sumário

1. [Objetivo do Sistema](#1-objetivo-do-sistema)
2. [Arquitetura Modular](#2-arquitetura-modular)
   - [Módulo 1: Vendas, Atendimento e Comandas](#módulo-1-vendas-atendimento-e-comandas)
   - [Módulo 2: Catálogo e Estrutura de Produtos](#módulo-2-catálogo-e-estrutura-de-produtos)
   - [Módulo 3: Controle, Auditoria e Fechamento de Estoque](#módulo-3-controle-auditoria-e-fechamento-de-estoque)
   - [Módulo 4: Administração, Usuários e Segurança](#módulo-4-administração-usuários-e-segurança)
3. [Mapa Real de Relacionamentos](#3-mapa-real-de-relacionamentos-fks-declaradas)
4. [Tabelas Âncora e Pontos de Atenção](#4-tabelas-âncora-e-pontos-de-atenção)

---

## 1. Objetivo do Sistema

Sistema de **PDV (ponto de venda) por comanda com controle de estoque auditável**, voltado a operações de salão como bares, restaurantes e lanchonetes.

- O atendimento é feito por **garçons**.
- A conta do cliente é uma **comanda**, com itens do catálogo e itens avulsos.
- A **taxa de serviço** (gorjeta) é configurável.
- O estoque é reconciliado por **contagem física, snapshots diários e fechamento diário**, com registro de perdas, ajustes e trilha de auditoria.
- Há uma camada de **vitrine pública** (`nome_publico`, `descricao_publica`, `produto_cards`), indicando um cardápio digital voltado ao cliente.

**Modelo de negócio atendido:** venda presencial por comanda, com baixa e reconciliação de estoque, e rastreabilidade de perdas e divergências para reduzir prejuízo operacional.

---

## 2. Arquitetura Modular

### Módulo 1: Vendas, Atendimento e Comandas

**Tabelas:** `comandas`, `itens_comanda`, `itens_livres`, `comprovantes_venda`, `garcons`, `configuracoes`

**Papel:** registrar todo o ciclo de atendimento, da abertura da conta ao pagamento e à emissão do comprovante.

**Jornada do dado:**

1. A **comanda** nasce com `status = 'aberta'`, `valor_total = 0.00` e `garcom_id` (FK para `garcons`).
2. Os consumos entram por dois caminhos:
   - **`itens_comanda`**: referencia `produto_id` e **congela** `preco_unitario` e `subtotal` na linha, preservando o histórico caso o preço do produto mude. É o único caminho conectado ao estoque.
   - **`itens_livres`**: item avulso sem produto cadastrado (`descricao`, `valor`, `quantidade`, `subtotal`), como couvert ou serviços. **Não impacta o estoque.**
3. No fechamento, a regra de **`configuracoes`** (`tipo_taxa`: `fixa`, `percentual` ou `nenhuma`) é aplicada, e a comanda grava `taxa_gorjeta`, `valor_total`, `usuario_fechamento_id` e `data_finalizacao`. O status passa a `fechada` (ou `cancelada`).
4. **`comprovantes_venda`** guarda o texto emitido (`conteudo`), o `tipo` (`cliente` ou `estabelecimento`) e o controle de impressão (`impresso`, `data_impressao`).

**Regras de exclusão:**

- Apagar uma comanda apaga seus itens e itens livres (`CASCADE`), mas é **bloqueado** se houver comprovante (`RESTRICT`).
- `garcons` usa **exclusão lógica** com dois flags: `ativo` (pode atender?) e `removido` (foi excluído?). O `RESTRICT` em `comandas.garcom_id` explica o `removido`: preserva o histórico de vendas.

---

### Módulo 2: Catálogo e Estrutura de Produtos

**Tabelas:** `produtos`, `categorias`, `produto_cards`, `fornecedores`

**Papel:** definir o que é vendido, como é apresentado e de quem é comprado.

**Jornada do dado:**

1. **`produtos`** guarda preço, `estoque_atual`, `estoque_minimo` (base para alertas de reposição) e `ativo`, além da identidade pública (`nome_publico`, `descricao_publica`, `imagem_url`).
2. **`produto_cards`** é a camada de apresentação: `nome_exibicao`, `descricao`, `ordem` (posição no cardápio) e imagem. Se o produto for excluído, o card vai junto (`CASCADE`).
3. **`categorias`** classifica os produtos, mas **sem FK declarada** (ver seção 4).
4. **`fornecedores`** (`nome`, `contato`, `telefone`, `produtos_fornecidos`) é referenciado por `movimentacoes_estoque.fornecedor_id` (`RESTRICT`): não é possível excluir um fornecedor que já teve entradas. O campo `produtos_fornecidos` é texto livre, sem relação formal com `produtos`.

---

### Módulo 3: Controle, Auditoria e Fechamento de Estoque

**Tabelas:** `movimentacoes_estoque`, `inventarios_estoque`, `estoque_snapshots`, `fechamento_diario_estoque`, `perdas_estoque`, `historico_ajustes_estoque`, `log_auditoria_perdas`

**Papel:** garantir que o estoque **teórico** (calculado) coincida com o **real** (contado) e documentar toda divergência.

**Jornada do dado:**

1. **Registro:** `movimentacoes_estoque` (`tipo`: `entrada` ou `saida`, `quantidade`, `valor_unitario`, `fornecedor_id` opcional) é o livro-razão.
2. **Contagem:** `inventarios_estoque` compara `quantidade_fisica` com `quantidade_sistema`, grava a `diferenca` e o `usuario_id` (FK real).
3. **Foto do dia:** `estoque_snapshots` guarda `estoque_real`, `estoque_teorico`, `divergencia`, `entradas_dia` e `saidas_dia`. Segundo o comentário do próprio banco, serve para cálculos precisos de perdas periódicas.
4. **Fechamento:** `fechamento_diario_estoque` consolida o mesmo dia, separando **`vendas_dia`** de **`outras_saidas`**, com `status` (padrão `pendente`).
5. **Perda:** `perdas_estoque` registra `quantidade_perdida`, `valor_perda` e `motivo` (padrão "Diferença de inventário", o que indica que a origem típica é a contagem). Guarda a memória de cálculo (`estoque_inicial_periodo`, `entradas_periodo`, `vendas_periodo`, `estoque_esperado`, `estoque_real`) e o fluxo de ciência pelo gestor (`visualizada`, `data_visualizacao`).
6. **Correção:** `historico_ajustes_estoque` registra `divergencia_detectada`, `ajuste_aplicado`, `motivo`, `observacao` e `ajustado_por`.
7. **Trilha:** `log_auditoria_perdas` registra cada `acao` sobre uma perda, com `usuario_id`, `data_acao` e `detalhes` (`longtext`).

**Fluxo resumido:** movimentação, contagem, comparação, perda ou ajuste, auditoria e fechamento.

**Regras de exclusão ao remover um produto:**

| Tabela filha | Regra |
|---|---|
| `movimentacoes_estoque`, `inventarios_estoque`, `itens_comanda` | `RESTRICT` (bloqueia) |
| `estoque_snapshots`, `fechamento_diario_estoque`, `perdas_estoque`, `historico_ajustes_estoque`, `produto_cards` | `CASCADE` (apaga junto) |

Um produto com vendas ou movimentações não pode ser excluído, o que protege o histórico principal. Já os dados derivados (snapshots, fechamento, perdas, ajustes) são apagados em cascata caso a exclusão seja possível.

---

### Módulo 4: Administração, Usuários e Segurança

**Tabelas:** `usuarios`, `configuracoes_sistema`, `configuracoes`

**Papel:** controlar quem acessa o sistema, com qual permissão, e parametrizar o comportamento global.

- **`usuarios`**: `perfil` (`admin`, `caixa`, `estoque`; padrão `caixa`), `ativo` e `senha varchar(255)`, tamanho compatível com hash. Implementa a separação de responsabilidades: o caixa opera vendas, o estoquista opera inventário e o admin tem controle total. Aparece como carimbo de responsabilidade no fechamento de comandas e nos inventários.
- **`configuracoes_sistema`**: tabela chave-valor (`chave`, `valor`, `descricao`) para parâmetros globais sem alterar o esquema.
- **`configuracoes`**: regra de taxa de serviço (`taxa_gorjeta decimal(5,2)`, `tipo_taxa`). Tem aparência de tabela de linha única.
- **`garcons`** é separado de `usuarios`: o garçom atende, mas não tem login.

---

## 3. Mapa Real de Relacionamentos (FKs declaradas)

| Origem | Destino | Ao excluir o pai |
|---|---|---|
| `comandas.garcom_id` | `garcons` | RESTRICT |
| `comandas.usuario_fechamento_id` | `usuarios` | RESTRICT |
| `comprovantes_venda.comanda_id` | `comandas` | RESTRICT |
| `itens_comanda.comanda_id` | `comandas` | CASCADE |
| `itens_comanda.produto_id` | `produtos` | RESTRICT |
| `itens_livres.comanda_id` | `comandas` | CASCADE |
| `movimentacoes_estoque.produto_id` | `produtos` | RESTRICT |
| `movimentacoes_estoque.fornecedor_id` | `fornecedores` | RESTRICT |
| `inventarios_estoque.produto_id` | `produtos` | RESTRICT |
| `inventarios_estoque.usuario_id` | `usuarios` | RESTRICT |
| `estoque_snapshots.produto_id` | `produtos` | CASCADE |
| `fechamento_diario_estoque.produto_id` | `produtos` | CASCADE |
| `perdas_estoque.produto_id` | `produtos` | CASCADE |
| `historico_ajustes_estoque.produto_id` | `produtos` | CASCADE |
| `log_auditoria_perdas.perda_id` | `perdas_estoque` | CASCADE |
| `produto_cards.produto_id` | `produtos` | CASCADE |

**Colunas que parecem referências, mas não têm FK declarada:**

- `produtos.categoria_id` (esperado: `categorias.id`)
- `historico_ajustes_estoque.ajustado_por` (esperado: `usuarios.id`)
- `log_auditoria_perdas.usuario_id` (esperado: `usuarios.id`)

---

## 4. Tabelas Âncora e Pontos de Atenção

### Tabelas âncora

- **`produtos`**: âncora do estoque, com 9 FKs apontando para ela. É onde a venda (`itens_comanda`) encontra o saldo físico. Todo saldo, perda e divergência refere-se a um `produto_id`.
- **`comandas`**: âncora do atendimento. Conecta `garcons`, `usuarios` (quem fechou), `itens_comanda`, `itens_livres` e `comprovantes_venda`. É a unidade de faturamento: `valor_total` consolida itens, itens livres e taxa.
- **`usuarios`**: âncora da responsabilização. Formalmente só cobre fechamento de comanda e inventário, embora logicamente deva cobrir também ajustes e auditoria.

### Relacionamentos críticos entre atendimento e estoque

| Relacionamento | Regra de negócio |
|---|---|
| `comandas` → `itens_comanda` → `produtos` | A venda de um item deve gerar saída de estoque. É a ponte entre atendimento e estoque |
| `itens_comanda.preco_unitario` vs `produtos.preco` | Preço congelado na venda, preservando o histórico financeiro |
| `movimentacoes_estoque` → `fornecedores` | Rastreia a origem de cada entrada |
| `perdas_estoque` → `log_auditoria_perdas` | Toda perda possui uma trilha de ações |
| `estoque_snapshots` / `fechamento_diario_estoque` | Comparam `vendas_dia` (originadas das comandas) com o saldo físico, revelando furos |

### Inconsistências e pontos de atenção (por prioridade)

1. **`produtos.categoria_id` sem FK.** Permite categoria inexistente e exclusão de categoria com produtos ligados. As tabelas `produtos` e `produto_cards` foram criadas em 30/10/2026, enquanto as demais datam de 20/09/2026. É possível que a tabela tenha sido recriada e perdido a FK, mas isso é hipótese: vale conferir o histórico de migrações.
2. **`ajustado_por` e `log_auditoria_perdas.usuario_id` sem FK.** A trilha de auditoria perde garantia de integridade justamente onde mais importa. Ambos são anuláveis, então também pode haver ações sem responsável.
3. **Sem ligação entre venda e estoque no nível do esquema.** `itens_comanda` não referencia `movimentacoes_estoque`, e `movimentacoes_estoque` não tem `comanda_id`. A baixa por venda depende apenas da lógica da aplicação. Um `comanda_id` opcional na movimentação tornaria a reconciliação rastreável.
4. **Sobreposição de tabelas.** `estoque_snapshots` e `fechamento_diario_estoque` são quase idênticas (real, teórico, divergência, entradas). A diferença útil é `vendas_dia` e `outras_saidas` contra `saidas_dia`. Vale decidir se uma substitui a outra.
5. **Imagens duplicadas.** `produtos` tem `imagem` e `imagem_url`; `produto_cards` também. Convém definir a fonte de verdade.
6. **Tipagem frouxa.** `fechamento_diario_estoque.status` é `varchar(20)`, enquanto `comandas.status` usa `enum`. O export também não lista índices únicos, por exemplo `(produto_id, data_snapshot)` e `(produto_id, data_fechamento)`, que evitariam duplicar o fechamento do mesmo dia. O PDF não mostra índices, então vale checar diretamente no banco.
7. **Cascatas assimétricas.** Confirmar se apagar um produto deve realmente eliminar seu histórico de perdas, ajustes, snapshots e fechamentos.
8. **Campos derivados** (`estoque_atual`, `valor_total`, `divergencia`) exigem transações atômicas para não divergir das tabelas de origem.

para acessar o bd na hostinger: Utilize este recurso para conectar-se ao nosso servidor MySQL a partir do seu computador ou de outro servidor. Basta adicionar o seu endereço IP que se conectará ao nosso servidor MySQL e escolher o nome do banco de dados na lista desejada.

O nome do host do nosso servidor MySQL é: srv2292.hstgr.io ou você pode usar este IP como seu nome de host: 2.24.3.23