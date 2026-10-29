# ✅ CHECKLIST DEPLOY HOSTINGER

## 📋 Arquivos Corrigidos para Produção:

### 1. **Configuração de Banco**
- ✅ `config/database_hostinger.php` - Configuração para produção
- ⚠️ **AÇÃO**: Renomear para `database.php` e alterar senha

### 2. **Caminhos Corrigidos**
- ✅ `config/paths.php` - BASE_URL alterado de `/gestaointeli-jnr` para ``
- ✅ `modules/caixa/index.php` - $base_path alterado para `/`
- ✅ `modules/estoque/index.php` - Script path corrigido
- ✅ `modules/relatorios/relatorios.js` - Timestamp adicionado

### 3. **Arquivos de Deploy**
- ✅ `.htaccess` - Configurações de servidor
- ✅ `INSTRUCOES_DEPLOY.txt` - Guia completo
- ✅ `DEPLOY_HOSTINGER.md` - Estrutura de pastas

## 🚀 PASSOS PARA DEPLOY:

### **Passo 1: Upload**
```
Fazer upload de TODOS os arquivos para public_html/
```

### **Passo 2: Configurar Banco**
```bash
1. Renomear: config/database_hostinger.php → config/database.php
2. Editar database.php e colocar SUA SENHA
3. Importar SQL no banco u903648047_sis_restaurant
```

### **Passo 3: Testar**
```
1. Acessar: seusite.com/api/teste_conexao.php
2. Deve mostrar "Conexão bem-sucedida"
3. Acessar: seusite.com
4. Testar login e funcionalidades
```

## ⚠️ IMPORTANTE:
- **Senha**: Altere em `database.php` antes do upload
- **SQL**: Importe o arquivo `sistema_restaurante (6).sql`
- **Teste**: Sempre teste a conexão primeiro

## 📁 Estrutura Final no Servidor:
```
public_html/
├── index.php
├── login.php
├── .htaccess
├── api/
├── config/
├── modules/
└── ...
```