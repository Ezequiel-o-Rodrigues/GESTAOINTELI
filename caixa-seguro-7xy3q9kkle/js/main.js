// Funções globais do sistema
class SistemaRestaurante {
    constructor() {
        this.comandaAtual = null;
        this.init();
    }

    init() {
        this.configurarEventListeners();
    }

    carregarComandaAberta() {
        // ✅ CORRIGIDO
        fetch(PathConfig.api('comanda_aberta.php'))
            .then(response => {
                if (!response.ok) {
                    throw new Error('API não encontrada');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.comanda) {
                    this.comandaAtual = data.comanda;
                    this.atualizarInterfaceComanda();
                }
            })
            .catch(error => {
                console.log('Nenhuma comanda aberta ou API indisponível:', error.message);
            });
    }

    configurarEventListeners() {
        // Event listeners globais
        document.addEventListener('keydown', this.handleKeyboard.bind(this));
    }

    handleKeyboard(event) {
        // Atalhos de teclado
        if (event.ctrlKey) {
            switch(event.key) {
                case 'n':
                    event.preventDefault();
                    this.novaComanda();
                    break;
                case 'f':
                    event.preventDefault();
                    this.finalizarComanda();
                    break;
            }
        }
    }

    formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    mostrarNotificacao(mensagem, tipo = 'info') {
        console.log(`${tipo.toUpperCase()}: ${mensagem}`);
    }

    // Método placeholder para atualizar interface
    atualizarInterfaceComanda() {
        console.log('Comanda atual:', this.comandaAtual);
        // Implementar conforme necessidade
    }

    novaComanda() {
        console.log('Nova comanda - implementar');
    }

    finalizarComanda() {
        console.log('Finalizar comanda - implementar');
    }
}

// Inicializar sistema quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.sistema = new SistemaRestaurante();
    console.log('Sistema Restaurante inicializado');
});