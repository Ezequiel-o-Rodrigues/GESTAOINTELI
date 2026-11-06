/* Editor Interativo de Layout do Salão - TASK-ADMIN-04 */
(function () {
    'use strict';

    const SNAP = 20;
    const cfg = window.layoutEditorConfig || {};
    const mapaMesasUrl   = cfg.mapaMesasUrl;
    const salvarMesaUrl  = cfg.salvarMesaUrl;
    const deletarMesaUrl = cfg.deletarMesaUrl;
    const salvarLayoutUrl = cfg.salvarLayoutUrl;
    const csrfToken      = cfg.csrfToken;

    let mesas = [];
    let modoEdicao = true;
    let ambienteAtivo = '';
    let ambientes = [];
    let zoom = 1;
    let panX = 0, panY = 0;
    let isPanning = false, panStartX = 0, panStartY = 0;
    let dragging = null, dragOffX = 0, dragOffY = 0;
    let alteracoesPendentes = false;

    const canvas      = document.getElementById('layoutCanvas');
    const canvasWrap  = document.getElementById('layoutCanvasWrap');
    const tabsEl      = document.getElementById('layoutAmbienteTabs');
    const btnSalvar   = document.getElementById('btnSalvarLayout');
    const btnZoomIn   = document.getElementById('btnZoomIn');
    const btnZoomOut  = document.getElementById('btnZoomOut');
    const btnZoomReset= document.getElementById('btnZoomReset');
    const btnNovaMesa = document.getElementById('btnNovaMesaEditor');
    const statusEl    = document.getElementById('layoutStatus');

    /* ── utilidades ── */
    function snap(v) { return Math.round(v / SNAP) * SNAP; }

    function setStatus(msg, tipo) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'layout-status layout-status--' + (tipo || 'info');
    }

    function marcarAlterado() {
        alteracoesPendentes = true;
        if (btnSalvar) btnSalvar.classList.add('btn-warning');
    }

    /* ── carregar mesas ── */
    async function carregarMesas() {
        setStatus('Carregando…', 'info');
        try {
            const r = await fetch(mapaMesasUrl);
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            mesas = d.mesas.map(m => ({
                id:        parseInt(m.mesa_id),
                numero:    m.numero_mesa,
                ambiente:  m.ambiente,
                x:         parseInt(m.posicao_x),
                y:         parseInt(m.posicao_y),
                formato:   m.formato || 'quadrada',
                largura:   parseInt(m.largura) || 60,
                altura:    parseInt(m.altura)  || 60,
                status:    m.status_mesa,
                ocupada:   m.status_mesa === 'ocupada'
            }));
            ambientes = [...new Set(mesas.map(m => m.ambiente))];
            if (!ambientes.length) ambientes = ['Salão Principal'];
            if (!ambienteAtivo || !ambientes.includes(ambienteAtivo)) {
                ambienteAtivo = ambientes[0];
            }
            renderTabs();
            renderCanvas();
            renderTabela();
            setStatus('', 'info');
        } catch (e) {
            setStatus('Erro ao carregar: ' + e.message, 'error');
        }
    }

    /* ── abas de ambiente ── */
    function renderTabs() {
        if (!tabsEl) return;
        tabsEl.replaceChildren();
        ambientes.forEach(amb => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'layout-tab' + (amb === ambienteAtivo ? ' layout-tab--active' : '');
            btn.textContent = amb;
            btn.addEventListener('click', () => { ambienteAtivo = amb; renderTabs(); renderCanvas(); });
            tabsEl.appendChild(btn);
        });
    }

    /* ── canvas ── */
    function applyTransform() {
        canvas.style.transform = `translate(${panX}px,${panY}px) scale(${zoom})`;
        canvas.style.transformOrigin = '0 0';
    }

    function renderCanvas() {
        canvas.replaceChildren();
        const mesasAmbiente = mesas.filter(m => m.ambiente === ambienteAtivo);
        mesasAmbiente.forEach(mesa => {
            const el = document.createElement('div');
            el.className = 'le-mesa le-mesa--' + mesa.formato + (mesa.ocupada ? ' le-mesa--ocupada' : ' le-mesa--livre');
            el.style.left   = mesa.x + 'px';
            el.style.top    = mesa.y + 'px';
            el.style.width  = mesa.largura + 'px';
            el.style.height = mesa.altura  + 'px';
            el.dataset.id   = mesa.id;
            el.setAttribute('role', 'button');
            el.setAttribute('aria-label', 'Mesa ' + mesa.numero);
            el.innerHTML = `<span class="le-mesa-num">${mesa.numero}</span><span class="le-mesa-status">${mesa.ocupada ? 'Ocupada' : 'Livre'}</span>`;

            if (modoEdicao) {
                el.setAttribute('draggable', 'false');
                el.addEventListener('pointerdown', onMesaPointerDown);
                el.addEventListener('dblclick', () => abrirModalMesa(mesa));
            }

            canvas.appendChild(el);
        });
        applyTransform();
    }

    /* ── drag mesas ── */
    function onMesaPointerDown(e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        e.stopPropagation();
        const el = e.currentTarget;
        const id = parseInt(el.dataset.id);
        const mesa = mesas.find(m => m.id === id);
        if (!mesa || mesa.ocupada) return;

        el.setPointerCapture(e.pointerId);
        const rect = canvas.getBoundingClientRect();
        dragOffX = (e.clientX - rect.left) / zoom - mesa.x;
        dragOffY = (e.clientY - rect.top)  / zoom - mesa.y;
        dragging = { el, mesa, moved: false };

        el.addEventListener('pointermove', onMesaPointerMove);
        el.addEventListener('pointerup',   onMesaPointerUp);
    }

    function onMesaPointerMove(e) {
        if (!dragging) return;
        const rect = canvas.getBoundingClientRect();
        const nx = snap((e.clientX - rect.left) / zoom - dragOffX);
        const ny = snap((e.clientY - rect.top)  / zoom - dragOffY);
        dragging.mesa.x = Math.max(0, nx);
        dragging.mesa.y = Math.max(0, ny);
        dragging.el.style.left = dragging.mesa.x + 'px';
        dragging.el.style.top  = dragging.mesa.y + 'px';
        dragging.moved = true;
    }

    function onMesaPointerUp(e) {
        if (!dragging) return;
        dragging.el.removeEventListener('pointermove', onMesaPointerMove);
        dragging.el.removeEventListener('pointerup',   onMesaPointerUp);
        if (dragging.moved) {
            e._wasDrag = true;
            marcarAlterado();
        }
        dragging = null;
    }

    /* ── pan canvas ── */
    canvasWrap && canvasWrap.addEventListener('pointerdown', e => {
        if (e.target !== canvasWrap && e.target !== canvas) return;
        isPanning = true;
        panStartX = e.clientX - panX;
        panStartY = e.clientY - panY;
        canvasWrap.setPointerCapture(e.pointerId);
    });
    canvasWrap && canvasWrap.addEventListener('pointermove', e => {
        if (!isPanning) return;
        panX = e.clientX - panStartX;
        panY = e.clientY - panStartY;
        applyTransform();
    });
    canvasWrap && canvasWrap.addEventListener('pointerup', () => { isPanning = false; });

    /* ── zoom ── */
    canvasWrap && canvasWrap.addEventListener('wheel', e => {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 0.1 : -0.1;
        zoom = Math.min(2, Math.max(0.3, zoom + delta));
        applyTransform();
    }, { passive: false });

    btnZoomIn    && btnZoomIn.addEventListener('click',    () => { zoom = Math.min(2, zoom + 0.1); applyTransform(); });
    btnZoomOut   && btnZoomOut.addEventListener('click',   () => { zoom = Math.max(0.3, zoom - 0.1); applyTransform(); });
    btnZoomReset && btnZoomReset.addEventListener('click', () => { zoom = 1; panX = 0; panY = 0; applyTransform(); });

    /* ── salvar layout em lote ── */
    btnSalvar && btnSalvar.addEventListener('click', async () => {
        btnSalvar.disabled = true;
        setStatus('Salvando…', 'info');
        try {
            const payload = { mesas: mesas.map(m => ({
                id: m.id, posicao_x: m.x, posicao_y: m.y,
                formato: m.formato, largura: m.largura, altura: m.altura
            }))};
            const r = await fetch(salvarLayoutUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            alteracoesPendentes = false;
            btnSalvar.classList.remove('btn-warning');
            setStatus('Layout salvo!', 'success');
            setTimeout(() => setStatus('', 'info'), 3000);
        } catch (e) {
            setStatus('Erro ao salvar: ' + e.message, 'error');
        } finally {
            btnSalvar.disabled = false;
        }
    });

    /* ── nova mesa ── */
    btnNovaMesa && btnNovaMesa.addEventListener('click', () => abrirModalMesa(null));

    /* ── modal mesa ── */
    window.abrirModalMesa = function(mesa) {
        document.getElementById('mesa_id').value       = mesa ? mesa.id : '';
        document.getElementById('mesa_numero').value   = mesa ? mesa.numero : '';
        document.getElementById('mesa_ambiente').value = mesa ? mesa.ambiente : ambienteAtivo;
        document.getElementById('mesa_formato').value  = mesa ? mesa.formato : 'quadrada';
        document.getElementById('mesa_largura').value  = mesa ? mesa.largura : 60;
        document.getElementById('mesa_altura').value   = mesa ? mesa.altura  : 60;
        document.getElementById('mesa_x').value        = mesa ? mesa.x : 0;
        document.getElementById('mesa_y').value        = mesa ? mesa.y : 0;
        document.getElementById('mesa_erro').hidden    = true;
        const titulo = document.getElementById('mesaModalTitle');
        titulo.textContent = mesa ? 'Editar mesa' : 'Nova mesa';
        new bootstrap.Modal(document.getElementById('mesaModal')).show();
    };

    window.salvarMesa = async function() {
        const erro = document.getElementById('mesa_erro');
        erro.hidden = true;
        const payload = {
            id:        parseInt(document.getElementById('mesa_id').value) || null,
            numero:    document.getElementById('mesa_numero').value.trim(),
            ambiente:  document.getElementById('mesa_ambiente').value.trim() || 'Salão Principal',
            formato:   document.getElementById('mesa_formato').value,
            largura:   parseInt(document.getElementById('mesa_largura').value) || 60,
            altura:    parseInt(document.getElementById('mesa_altura').value)  || 60,
            posicao_x: parseInt(document.getElementById('mesa_x').value) || 0,
            posicao_y: parseInt(document.getElementById('mesa_y').value) || 0,
            ativo:     1
        };
        try {
            const r = await fetch(salvarMesaUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.message || 'Não foi possível salvar.');
            bootstrap.Modal.getInstance(document.getElementById('mesaModal')).hide();
            await carregarMesas();
        } catch (e) {
            erro.textContent = e.message;
            erro.hidden = false;
        }
    };

    window.excluirMesa = async function(id) {
        if (!confirm('Excluir esta mesa? O histórico de comandas será preservado.')) return;
        try {
            const r = await fetch(deletarMesaUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id })
            });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.message || 'Não foi possível excluir.');
            await carregarMesas();
        } catch (e) {
            alert(e.message);
        }
    };

    /* ── tabela lista ── */
    function renderTabela() {
        const tbody = document.getElementById('mesas-tbody');
        if (!tbody) return;
        if (!mesas.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhuma mesa cadastrada.</td></tr>';
            return;
        }
        tbody.replaceChildren();
        mesas.forEach(mesa => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${mesa.numero}</td>
                <td>${mesa.ambiente}</td>
                <td>${mesa.formato}</td>
                <td>${mesa.x}, ${mesa.y}</td>
                <td><span class="badge bg-${mesa.ocupada ? 'danger' : 'success'}">${mesa.status}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" aria-label="Editar mesa ${mesa.numero}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Excluir mesa ${mesa.numero}">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </td>`;
            tr.querySelector('.btn-outline-primary').addEventListener('click', () => abrirModalMesa(mesa));
            tr.querySelector('.btn-outline-danger').addEventListener('click', () => excluirMesa(mesa.id));
            tbody.appendChild(tr);
        });
    }

    /* ── aviso ao sair com alterações ── */
    window.addEventListener('beforeunload', e => {
        if (alteracoesPendentes) { e.preventDefault(); e.returnValue = ''; }
    });

    carregarMesas();
})();
