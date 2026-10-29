// 📁 js/path-config.js
class PathConfig {
    static getBasePath() {
        // Detecta o caminho base automaticamente
        const path = window.location.pathname;
        if (path.includes('/modules/')) {
            return path.split('/modules/')[0];
        }
        if (path.includes('/gestaointeli-jnr/')) {
            return '/gestaointeli-jnr';
        }
        return '';
    }

    static url(path = '') {
        const base = this.getBasePath();
        return `${base}/${path.replace(/^\//, '')}`;
    }

    static api(endpoint = '') {
        return this.url(`api/${endpoint}`);
    }

    static modules(module = '') {
        return this.url(`modules/${module}`);
    }

    static assets(path = '') {
        return this.url(`assets/${path}`);
    }
}

// Exemplos de uso:
// PathConfig.api('comanda_aberta.php')
// PathConfig.modules('estoque/script.js')
// PathConfig.assets('css/style.css')