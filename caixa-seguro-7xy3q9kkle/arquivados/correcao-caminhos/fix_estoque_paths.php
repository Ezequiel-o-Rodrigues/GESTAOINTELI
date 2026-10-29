<?php
/**
 * Script específico para corrigir caminhos do módulo de estoque
 */

echo "🔧 CORREÇÃO ESPECÍFICA - MÓDULO ESTOQUE\n";
echo "======================================\n\n";

$base_dir = __DIR__;
$corrections = 0;

// Arquivos específicos do estoque para verificar
$estoque_files = [
    'modules/estoque/js/estoque-manager.js',
    'modules/estoque/estoque.js',
    'modules/estoque/index.php'
];

foreach ($estoque_files as $file) {
    $filepath = $base_dir . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "⚠️  Arquivo não encontrado: $file\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $original = $content;
    
    // Padrões específicos para corrigir
    $patterns = [
        // JavaScript patterns
        '/gestaointeli-jnr\/api\/' => '/api/',
        'baseUrl = \'/gestaointeli-jnr\'' => 'baseUrl = \'\'',
        'this.baseUrl = \'/gestaointeli-jnr\'' => 'this.baseUrl = \'\'',
        
        // Qualquer referência restante ao gestaointeli-jnr
        '/gestaointeli-jnr/' => '/',
        
        // Caminhos relativos que devem ser absolutos
        '../../api/' => '/api/',
        '../api/' => '/api/',
        'api/' => '/api/' // Apenas se não for precedido por /
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = str_replace($pattern, $replacement, $content);
    }
    
    // Verificação específica para estoque-manager.js
    if (strpos($file, 'estoque-manager.js') !== false) {
        // Garantir que apiUrl está correto
        $content = preg_replace('/this\.apiUrl\s*=\s*[\'"][^\'\"]*[\'"]/', 'this.apiUrl = \'/api\'', $content);
    }
    
    if ($content !== $original) {
        file_put_contents($filepath, $content);
        echo "✅ Corrigido: $file\n";
        $corrections++;
    } else {
        echo "✓  OK: $file\n";
    }
}

echo "\n📊 RESULTADO:\n";
echo "Correções aplicadas: $corrections\n";

if ($corrections > 0) {
    echo "✅ Correções aplicadas! Teste o módulo de estoque agora.\n";
} else {
    echo "✅ Todos os arquivos já estavam corretos.\n";
}

echo "\n🔍 VERIFICAÇÃO FINAL:\n";

// Verificar se ainda há caminhos problemáticos
$problematic_found = false;
foreach ($estoque_files as $file) {
    $filepath = $base_dir . '/' . $file;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        
        if (strpos($content, '/gestaointeli-jnr/') !== false) {
            echo "⚠️  Ainda contém /gestaointeli-jnr/: $file\n";
            $problematic_found = true;
        }
        
        if (strpos($content, '../../api/') !== false) {
            echo "⚠️  Ainda contém ../../api/: $file\n";
            $problematic_found = true;
        }
    }
}

if (!$problematic_found) {
    echo "✅ Nenhum caminho problemático encontrado!\n";
    echo "🎉 Módulo de estoque deve funcionar corretamente agora.\n";
}

echo "\n💡 DICAS:\n";
echo "1. Limpe o cache do navegador (Ctrl+F5)\n";
echo "2. Teste cadastrar um novo produto\n";
echo "3. Verifique o console do navegador para erros\n";
?>