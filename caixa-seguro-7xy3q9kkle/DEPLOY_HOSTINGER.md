# Deploy para Hostinger - Estrutura de Pastas

## Estrutura no Servidor Hostinger:
```
public_html/
├── index.php
├── login.php
├── logout.php
├── .htaccess
├── api/
│   ├── adicionar_item.php
│   ├── comanda_aberta.php
│   ├── finalizar_comanda.php
│   ├── garcons.php
│   ├── gerar_comprovante.php
│   ├── imprimir_comprovante.php
│   ├── itens_comanda.php
│   ├── nova_comanda.php
│   ├── produto_info.php
│   ├── produtos_categoria.php
│   ├── registrar_entrada.php
│   ├── relatorio_alertas_perda.php
│   ├── relatorio_analise_estoque.php
│   ├── relatorio_produtos_vendidos.php
│   ├── relatorio_top_categorias.php
│   ├── relatorio_vendas_7dias.php
│   ├── relatorio_vendas_mensais.php
│   ├── relatorio_vendas_periodo.php
│   ├── remover_item.php
│   ├── salvar_produto.php
│   ├── teste_conexao.php
│   ├── toggle_produto.php
│   └── verificar_estoque.php
├── config/
│   ├── database.php
│   ├── paths.php
│   └── auth.php
├── css/
│   └── style.css
├── includes/
│   ├── auth-check.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── js/
│   ├── main.js
│   └── path-config.js
└── modules/
    ├── admin/
    │   ├── index.php
    │   └── admin.js
    ├── caixa/
    │   ├── index.php
    │   ├── caixa.js
    │   ├── criar_comanda.php
    │   └── impressao-service.js
    ├── estoque/
    │   ├── index.php
    │   └── js/
    │       └── estoque-manager.js
    └── relatorios/
        ├── index.php
        └── relatorios.js
```

## Passos para Deploy:

1. **Fazer upload de todos os arquivos para public_html/**
2. **Configurar banco de dados no config/database.php**
3. **Importar SQL no banco da Hostinger**
4. **Testar conexão**

## Migrações do cardápio integrado

Antes de enviar a versão que consulta o cardápio público, faça um backup completo do banco e dos arquivos de imagem. No banco usado pelo sistema de caixa, aplique nesta ordem:

1. `migracao_cards_cardapio.sql` (somente se a tabela `produto_cards` ainda não existir).
2. `migracao_apresentacao_produtos.sql` (adiciona campos opcionais de nome e descrição pública).
3. Opcionalmente, `migracao_imagens_cardapio.sql`, depois de enviar as imagens legadas ao servidor; ela só preenche imagens vazias para nomes de produtos previstos no script.

As alterações não copiam nem alteram produtos, preços, estoque ou vendas. Categorias são compartilhadas com o estoque e relatórios; renomear uma categoria ou reclassificar produtos também altera o agrupamento histórico por categoria. A exclusão de categorias com produtos é bloqueada até a reclassificação.

Não execute novamente uma migração já aplicada sem verificar o esquema. A reversão dos novos campos está comentada no arquivo SQL e só deve ser usada após confirmar que os dados de apresentação podem ser descartados. Não apague imagens antigas durante a implantação; arquivos novos são salvos em `public/images/menu/products`.