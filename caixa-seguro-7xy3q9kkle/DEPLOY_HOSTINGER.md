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