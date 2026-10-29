// VERSÃO AGGRESSIVA - FECHAMENTO RÁPIDO
window.impressaoService = {
    imprimirComprovante: function(conteudo) {
        console.log('⚡ Versão aggressiva - Fechamento rápido...');
        
        const textoLimpo = conteudo.replace(/\x1B\[[0-9;]*[A-Za-z]/g, '').replace(/\x0A/g, '\n').trim();
        
        // Popup ultra-minimalista
        const popup = window.open('', '_blank', 'width=1,height=1,left=9999,top=9999');
        
        if (popup) {
            popup.document.write(`
<html>
<head><title>.</title></head>
<body onload="window.print();">
<pre style="font-size:12px;">${textoLimpo}</pre>
<script>
    // Tentar fechar de várias formas
    window.onafterprint = () => setTimeout(() => window.close(), 100);
    
    // Fallback 1: fechar após impressão
    setTimeout(() => window.close(), 2000);
    
    // Fallback 2: fechar se ainda aberto
    setTimeout(() => { if(!window.closed) window.close(); }, 3000);
</script>
</body>
</html>`);
            popup.document.close();
        }
        
        return Promise.resolve({
            success: true,
            message: '✅ Imprimindo... Fechamento automático ativado.'
        });
    }
};