<?php
/**
 * Script para corrigir TODOS os caminhos incorretos no sistema
 * Executa uma revisão completa e correção de todos os arquivos
 */

echo "🔧 CORREÇÃO FINAL DE TODOS OS CAMINHOS\n";
echo "=====================================\n\n";

$base_dir = __DIR__;
$corrections_made = 0;
$files_checked = 0;

// Lista de arquivos para verificar e corrigir
$files_to_check = [
    // JavaScript files
    'js/path-config.js',
    'js/main.js',
    'modules/admin/admin.js',
    'modules/caixa/caixa.js',
    'modules/estoque/estoque.js',
    'modules/estoque/js/estoque-manager.js',
    'modules/relatorios/relatorios.js',
    
    // PHP files
    'config/paths.php',
    'includes/header.php',
    'includes/footer.php',
    'modules/caixa/index.php',
    'modules/estoque/index.php',
    'modules/relatorios/index.php',
    'modules/admin/index.php',
    'index.php',
    'login.php',
    
    // API files
    'api/adicionar_item.php',
    'api/comanda_aberta.php',
    'api/criar_tabela.php',
    'api/finalizar_comanda.php',
    'api/garcons.php',
    'api/gerar_comprovante.php',
    'api/gerar_relatorio.php',
    'api/imprimir_comprovante.php',
    'api/itens_comanda.php',
    'api/nova_comanda.php',
    'api/produto_info.php',
    'api/produtos_categoria.php',
    'api/registrar_entrada.php',
    'api/relatorio_alertas_perda.php',
    'api/relatorio_analise_estoque.php',
    'api/relatorio_produtos_vendidos.php',
    'api/relatorio_top_categorias.php',
    'api/relatorio_vendas_7dias.php',
    'api/relatorio_vendas_mensais.php',
    'api/relatorio_vendas_periodo.php',
    'api/remover_item.php',
    'api/salvar_produto.php',
    'api/teste_conexao.php',
    'api/toggle_produto.php',
    'api/verificar_estoque.php',
    'api/verificar_tabelas.php'
];

// Padrões de caminhos incorretos para corrigir
$incorrect_patterns = [
    // JavaScript patterns
    '/gestaointeli-jnr\/api\/' => '/api/',
    '/gestaointeli-jnr\/modules\/' => '/modules/',
    '/gestaointeli-jnr\/js\/' => '/js/',
    '/gestaointeli-jnr\/css\/' => '/css/',
    '/gestaointeli-jnr\/' => '/',
    'baseUrl = \'/gestaointeli-jnr\'' => 'baseUrl = \'\'',
    'baseUrl: \'/gestaointeli-jnr\'' => 'baseUrl: \'\'',
    'this.baseUrl = \'/gestaointeli-jnr\'' => 'this.baseUrl = \'\'',
    
    // API URLs in JavaScript
    '\'../../api/' => '\'/api/',
    '"../../api/' => '"/api/',
    'fetch(\'../../api/' => 'fetch(\'/api/',
    'fetch("../../api/' => 'fetch("/api/',
    
    // Relative paths that should be absolute
    'api/' => '/api/',
    
    // PHP patterns
    'require_once \'../../' => 'require_once __DIR__ . \'/../../',
    'include_once \'../../' => 'include_once __DIR__ . \'/../../',
    
    // URL patterns in PHP
    'href="../../' => 'href="/',
    'src="../../' => 'src="/',
    'action="../../' => 'action="/',
];

function correctFile($filepath, $patterns) {
    global $corrections_made, $files_checked;
    
    if (!file_exists($filepath)) {
        echo "⚠️  Arquivo não encontrado: $filepath\n";
        return false;
    }
    
    $files_checked++;
    $content = file_get_contents($filepath);
    $original_content = $content;
    $file_corrections = 0;
    
    // Apply corrections
    foreach ($patterns as $pattern => $replacement) {
        if (strpos($pattern, '/') === 0 && substr($pattern, -1) === '/') {
            // Regex pattern
            $new_content = preg_replace($pattern, $replacement, $content);
            if ($new_content !== $content) {
                $matches = preg_match_all($pattern, $content);
                $file_corrections += $matches;
                $content = $new_content;
            }
        } else {
            // String replacement
            $count = 0;
            $content = str_replace($pattern, $replacement, $content, $count);
            $file_corrections += $count;
        }
    }
    
    // Save if changes were made
    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        echo "✅ Corrigido: $filepath ($file_corrections correções)\n";
        $corrections_made += $file_corrections;
        return true;
    } else {
        echo "✓  OK: $filepath\n";
        return false;
    }
}

// Specific corrections for different file types
function correctJavaScriptFile($filepath) {
    $js_patterns = [
        // Base URL corrections
        '/baseUrl\s*=\s*[\'"]\/gestaointeli-jnr[\'"]/i' => 'baseUrl = \'\'',
        '/this\.baseUrl\s*=\s*[\'"]\/gestaointeli-jnr[\'"]/i' => 'this.baseUrl = \'\'',
        
        // API URL corrections
        '/[\'"]\.\.\/\.\.\/api\//g' => '\'/api/',
        '/fetch\s*\(\s*[\'"]\.\.\/\.\.\/api\//g' => 'fetch(\'/api/',
        
        // Module path corrections
        '/[\'"]\.\.\/\.\.\/modules\//g' => '\'/modules/',
        
        // CSS/JS path corrections
        '/[\'"]\.\.\/\.\.\/css\//g' => '\'/css/',
        '/[\'"]\.\.\/\.\.\/js\//g' => '\'/js/',
        
        // General gestaointeli-jnr removal
        '/\/gestaointeli-jnr\//g' => '/',
    ];
    
    return correctFile($filepath, $js_patterns);
}

function correctPHPFile($filepath) {
    $php_patterns = [
        // Include/require corrections
        '/require_once\s+[\'"]\.\.\/\.\.\//g' => 'require_once __DIR__ . \'/../../',
        '/include_once\s+[\'"]\.\.\/\.\.\//g' => 'include_once __DIR__ . \'/../../',
        
        // URL corrections in PHP
        '/href=[\'"]\.\.\/\.\.\//g' => 'href="/',
        '/src=[\'"]\.\.\/\.\.\//g' => 'src="/',
        '/action=[\'"]\.\.\/\.\.\//g' => 'action="/',
        
        // PathConfig usage corrections
        '/PathConfig::url\([\'"]\/gestaointeli-jnr\/[\'"]/' => 'PathConfig::url(\'\')',
        '/PathConfig::api\([\'"]\/gestaointeli-jnr\/api\/[\'"]/' => 'PathConfig::api(\'\')',
    ];
    
    return correctFile($filepath, $php_patterns);
}

echo "🔍 Verificando e corrigindo arquivos...\n\n";

// Process each file
foreach ($files_to_check as $file) {
    $filepath = $base_dir . '/' . $file;
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    
    switch ($extension) {
        case 'js':
            correctJavaScriptFile($filepath);
            break;
        case 'php':
            correctPHPFile($filepath);
            break;
        default:
            correctFile($filepath, $incorrect_patterns);
            break;
    }
}

// Additional specific corrections
echo "\n🎯 Aplicando correções específicas...\n\n";

// Fix path-config.js specifically
$path_config_file = $base_dir . '/js/path-config.js';
if (file_exists($path_config_file)) {
    $content = file_get_contents($path_config_file);
    $new_content = str_replace(
        "if (path.includes('/gestaointeli-jnr/')) {\n            return '/gestaointeli-jnr';\n        }",
        "// Removed gestaointeli-jnr path detection",
        $content
    );
    
    if ($new_content !== $content) {
        file_put_contents($path_config_file, $new_content);
        echo "✅ Correção específica aplicada: path-config.js\n";
        $corrections_made++;
    }
}

// Fix estoque-manager.js specifically
$estoque_manager_file = $base_dir . '/modules/estoque/js/estoque-manager.js';
if (file_exists($estoque_manager_file)) {
    $content = file_get_contents($estoque_manager_file);
    $new_content = str_replace(
        "this.baseUrl = '';",
        "this.baseUrl = '';",
        $content
    );
    $new_content = str_replace(
        "this.apiUrl = '/api';",
        "this.apiUrl = '/api';",
        $new_content
    );
    
    if ($new_content !== $content) {
        file_put_contents($estoque_manager_file, $new_content);
        echo "✅ Correção específica aplicada: estoque-manager.js\n";
        $corrections_made++;
    }
}

// Create a verification script
$verification_script = $base_dir . '/verify_paths.php';
$verification_content = '<?php
/**
 * Script de verificação de caminhos
 * Executa após as correções para validar se tudo está correto
 */

echo "🔍 VERIFICAÇÃO DE CAMINHOS\n";
echo "========================\n\n";

$issues_found = 0;

// Check for remaining incorrect patterns
$files_to_verify = glob(__DIR__ . "/{*.php,*.js,api/*.php,modules/*/*.php,modules/*/*.js,modules/*/js/*.js}", GLOB_BRACE);

$problematic_patterns = [
    "/gestaointeli-jnr/",
    "../../api/",
    "../../modules/",
    "../../css/",
    "../../js/"
];

foreach ($files_to_verify as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        $relative_path = str_replace(__DIR__ . "/", "", $file);
        
        foreach ($problematic_patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                echo "⚠️  Possível problema em: $relative_path (contém: $pattern)\n";
                $issues_found++;
            }
        }
    }
}

if ($issues_found === 0) {
    echo "✅ Nenhum problema de caminho encontrado!\n";
    echo "🎉 Todos os caminhos estão corretos para o subdomínio.\n";
} else {
    echo "\n❌ Encontrados $issues_found possíveis problemas.\n";
    echo "💡 Revise os arquivos listados acima.\n";
}

echo "\n📋 RESUMO DA CONFIGURAÇÃO:\n";
echo "- Base URL: / (raiz do subdomínio)\n";
echo "- API URLs: /api/\n";
echo "- Módulos: /modules/\n";
echo "- Assets: /css/, /js/\n";
?>';

file_put_contents($verification_script, $verification_content);

echo "\n📊 RESUMO FINAL:\n";
echo "================\n";
echo "Arquivos verificados: $files_checked\n";
echo "Correções aplicadas: $corrections_made\n";
echo "Script de verificação criado: verify_paths.php\n\n";

if ($corrections_made > 0) {
    echo "✅ Correções aplicadas com sucesso!\n";
    echo "🚀 Execute verify_paths.php para validar as correções.\n";
} else {
    echo "✅ Nenhuma correção necessária - todos os caminhos já estão corretos!\n";
}

echo "\n🎯 PRÓXIMOS PASSOS:\n";
echo "1. Execute: php verify_paths.php\n";
echo "2. Teste o sistema no navegador\n";
echo "3. Verifique se as APIs funcionam corretamente\n";
echo "4. Teste todos os módulos (Caixa, Estoque, Relatórios)\n\n";

echo "🔧 CONFIGURAÇÃO FINAL PARA SUBDOMÍNIO:\n";
echo "- Todos os caminhos agora apontam para a raiz (/)\n";
echo "- APIs acessíveis em /api/\n";
echo "- Módulos acessíveis em /modules/\n";
echo "- Sistema pronto para funcionar no subdomínio\n";
?>