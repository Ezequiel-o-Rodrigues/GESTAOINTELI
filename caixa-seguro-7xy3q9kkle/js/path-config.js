class PathConfig {
    static getBasePath() {
        const pathname = window.location.pathname;
        const subdirectoryPositions = ['/modules/', '/api/']
            .map((directory) => pathname.indexOf(directory))
            .filter((position) => position !== -1);

        if (subdirectoryPositions.length) {
            return pathname.slice(0, Math.min(...subdirectoryPositions));
        }

        const segments = pathname.split('/').filter(Boolean);
        const lastSegment = segments[segments.length - 1];

        if (lastSegment && lastSegment.includes('.')) {
            segments.pop();
        }

        return segments.length ? `/${segments.join('/')}` : '';
    }

    static url(path = '') {
        const base = this.getBasePath().replace(/\/+$/, '');
        const normalizedPath = String(path).replace(/^\/+/, '');

        return normalizedPath ? `${base}/${normalizedPath}` : `${base}/`;
    }

    static api(endpoint = '') {
        return this.url(`api/${String(endpoint).replace(/^\/+/, '')}`);
    }

    static modules(module = '') {
        return this.url(`modules/${String(module).replace(/^\/+/, '')}`);
    }

    static assets(path = '') {
        return this.url(`assets/${String(path).replace(/^\/+/, '')}`);
    }
}
