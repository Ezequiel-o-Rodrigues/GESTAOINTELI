<?php
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
?>