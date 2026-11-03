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

## Perfis de acesso

- `admin`: acesso completo, incluindo administração e cardápio público.
- `caixa`: frente de caixa e relatórios.
- `estoque`: controle de estoque e relatórios.

As permissões são verificadas nas páginas e nas APIs; ocultar um link na navegação não substitui a validação do servidor.

O login, o painel inicial, a navegação compartilhada e o cabeçalho do caixa usam React 18 via CDN e o arquivo `js/react-app.js`. Bootstrap continua carregado para as telas operacionais existentes, que mantêm os endpoints PHP atuais durante a migração gradual, sem etapa de compilação no deploy.

## Migrações do cardápio integrado

Antes de enviar a versão que consulta o cardápio público, faça um backup completo do banco e dos arquivos de imagem. No banco usado pelo sistema de caixa, execute `migracao_cardapio_integrado.sql` no phpMyAdmin. O script cria a tabela de opções se estiver ausente e adiciona os campos públicos apenas se ainda não existirem, podendo ser reaplicado com segurança.

Opcionalmente, execute `migracao_imagens_cardapio.sql` depois de enviar as imagens legadas ao servidor; ela só preenche imagens vazias para nomes de produtos previstos no script.

As alterações não copiam nem alteram produtos, preços, estoque ou vendas. Categorias são compartilhadas com o estoque e relatórios; renomear uma categoria ou reclassificar produtos também altera o agrupamento histórico por categoria. A exclusão de categorias com produtos é bloqueada até a reclassificação.

Não apague imagens antigas durante a implantação; arquivos novos são salvos em `public/images/menu/products`. A reversão opcional da nova tabela e dos campos está comentada no arquivo SQL e só deve ser usada após confirmar que todos os dados de apresentação podem ser descartados.

Para usar imagens por URL em produtos ou subcategorias, execute novamente a versão atualizada de `migracao_cardapio_integrado.sql` após fazer backup. Ela adiciona os campos de URL sem apagar dados ou imagens existentes. O botão de pesquisa abre o Google Imagens; copie o endereço HTTPS direto de um arquivo autorizado para uso. A exibição prioriza um arquivo local válido, depois a URL HTTPS salva e, por fim, a imagem padrão. A URL é carregada pelo navegador, portanto alguns sites podem bloquear exibição externa; nesses casos, envie o arquivo pelo formulário.