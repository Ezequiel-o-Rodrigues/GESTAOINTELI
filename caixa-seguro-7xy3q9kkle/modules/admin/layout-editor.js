/* Editor Interativo de Layout do Salão - TASK-ADMIN-04 */
(function () {
    'use strict';

    const SNAP = 20;
    const cfg = window.layoutEditorConfig || {};
    const mapaMesasUrl    = cfg.mapaMesasUrl;
    const salvarMesaUrl   = cfg.salvarMesaUrl;
    const deletarMesaUrl  = cfg.deletarMesaUrl;
    const salvarLayoutUrl = cfg.salvarLayoutUrl;
    const csrfToken       = cfg.csrfToken;

    let mesas        = [];
    let decoracoes   = [];   // {id, ambiente, tipo, x1,y1,x2,y2, texto, cor}
    let ambienteAtivo = '';
    let ambientes    = [];
    let zoom = 1, panX = 0, panY = 0;
    let isPanning = false, panStartX = 0, panStartY = 0;
    let dragging  = null, dragOffX = 0, dragOffY = 0;
    let alteracoesPendentes = false;

    // ferramenta ativa: 'select' | 'linha' | 'texto'
    let ferramenta = 'select';
    let drawStart  = null;   // {x,y} ao iniciar desenho de linha
    let svgPreview = null;   // linha temporária durante drag

    const canvas      = document.getElementById('layoutCanvas');
    const svgLayer    = document.getElementById('layoutSvg');
    const canvasWrap  = document.getElementById('layoutCanvasWrap');
    const tabsEl      = document.getElementById('layoutAmbienteTabs');
    const btnSalvar   = document.getElementById('btnSalvarLayout');
    const btnZoomIn   = document.getElementById('btnZoomIn');
    const btnZoomOut  = document.getElementById('btnZoomOut');
    const btnZoomReset= document.getElementById('btnZoomReset');
    const btnNovaMesa = document.getElementById('btnNovaMesaEditor');
    const statusEl    = document.getElementById('layoutStatus');
    const toolBtns    = document.querySelectorAll('[data-tool]');

    /* ── utils ── */
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

    function clientToCanvas(cx, cy) {
        const rect = canvasWrap.getBoundingClientRect();
        return {
            x: snap((cx - rect.left - panX) / zoom),
            y: snap((cy - rect.top  - panY) / zoom)
        };
    }

    /* ── ferramentas ── */
    toolBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            ferramenta = btn.dataset.tool;
            toolBtns.forEach(b => b.classList.toggle('le-tool--active', b === btn));
            canvasWrap.style.cursor = ferramenta === 'select' ? 'grab' : 'crosshair';
        });
    });

    /* ── carregar ── */
    async function carregarMesas() {
        setStatus('Carregando…', 'info');
        try {
            const r = await fetch(mapaMesasUrl);
            const d = await r.json();
            if (!d.success) throw new Error(d.message);

            mesas = d.mesas.map(m => ({
                id:      parseInt(m.mesa_id),
                numero:  m.numero_mesa,
                ambiente:m.ambiente,
                x:       parseInt(m.posicao_x),
                y:       parseInt(m.posicao_y),
                formato: m.formato || 'quadrada',
                largura: parseInt(m.largura) || 60,
                altura:  parseInt(m.altura)  || 60,
                status:  m.status_mesa,
                ocupada: m.status_mesa === 'ocupada'
            }));

            decoracoes = (d.decoracoes || []).map(d => ({
                id:      parseInt(d.id),
                ambiente:d.ambiente,
                tipo:    d.tipo,
                x1: parseInt(d.x1), y1: parseInt(d.y1),
                x2: parseInt(d.x2), y2: parseInt(d.y2),
                texto:   d.texto || '',
                cor:     d.cor   || '#94a3b8'
            }));

            ambientes = [...new Set(mesas.map(m => m.ambiente))];
            if (!ambientes.length) ambientes = ['Salão Principal'];
            if (!ambienteAtivo || !ambientes.includes(ambienteAtivo)) ambienteAtivo = ambientes[0];

            renderTabs();
            renderCanvas();
            renderTabela();
            setStatus('', 'info');
        } catch (e) {
            setStatus('Erro ao carregar: ' + e.message, 'error');
        }
    }

    /* ── abas ── */
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

    /* ── transform ── */
    function applyTransform() {
        const t = `translate(${panX}px,${panY}px) scale(${zoom})`;
        canvas.style.transform   = t;
        svgLayer.style.transform = t;
        canvas.style.transformOrigin   = '0 0';
        svgLayer.style.transformOrigin = '0 0';
    }

    /* ── render canvas (mesas) ── */
    function renderCanvas() {
        canvas.replaceChildren();
        mesas.filter(m => m.ambiente === ambienteAtivo).forEach(mesa => {
            const el = document.createElement('div');
            el.className = 'le-mesa le-mesa--' + mesa.formato + (mesa.ocupada ? ' le-mesa--ocupada' : ' le-mesa--livre');
            el.style.cssText = `left:${mesa.x}px;top:${mesa.y}px;width:${mesa.largura}px;height:${mesa.altura}px`;
            el.dataset.id = mesa.id;
            el.setAttribute('role', 'button');
            el.setAttribute('aria-label', 'Mesa ' + mesa.numero);
            el.innerHTML = `<span class="le-mesa-num">${mesa.numero}</span><span class="le-mesa-status">${mesa.ocupada ? 'Ocupada' : 'Livre'}</span>`;
            el.addEventListener('pointerdown', onMesaPointerDown);
            el.addEventListener('dblclick', () => { if (ferramenta === 'select') abrirModalMesa(mesa); });
            canvas.appendChild(el);
        });
        renderDecoracoes();
        applyTransform();
    }

    /* ── render decorações (SVG) ── */
    function renderDecoracoes() {
        while (svgLayer.firstChild) svgLayer.removeChild(svgLayer.firstChild);

        decoracoes.filter(d => d.ambiente === ambienteAtivo).forEach(dec => {
            if (dec.tipo === 'linha') {
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', dec.x1); line.setAttribute('y1', dec.y1);
                line.setAttribute('x2', dec.x2); line.setAttribute('y2', dec.y2);
                line.setAttribute('stroke', dec.cor);
                line.setAttribute('stroke-width', '3');
                line.setAttribute('stroke-linecap', 'round');
                line.dataset.decId = dec.id;
                line.classList.add('le-dec-linha');
                line.addEventListener('dblclick', () => removerDecoracao(dec.id));
                svgLayer.appendChild(line);
            } else if (dec.tipo === 'texto') {
                const txt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                txt.setAttribute('x', dec.x1); txt.setAttribute('y', dec.y1);
                txt.setAttribute('fill', dec.cor);
                txt.setAttribute('font-size', '14');
                txt.setAttribute('font-family', 'Segoe UI, sans-serif');
                txt.setAttribute('font-weight', '600');
                txt.textContent = dec.texto;
                txt.dataset.decId = dec.id;
                txt.classList.add('le-dec-texto');
                txt.addEventListener('dblclick', () => removerDecoracao(dec.id));
                svgLayer.appendChild(txt);
            }
        });
    }

    function removerDecoracao(id) {
        if (!confirm('Remover este elemento do mapa?')) return;
        decoracoes = decoracoes.filter(d => d.id !== id);
        marcarAlterado();
        renderDecoracoes();
    }

    /* ── drag mesas ── */
    function onMesaPointerDown(e) {
        if (ferramenta !== 'select') return;
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        e.stopPropagation();
        const el  = e.currentTarget;
        const id  = parseInt(el.dataset.id);
        const mesa = mesas.find(m => m.id === id);
        if (!mesa || mesa.ocupada) return;

        el.setPointerCapture(e.pointerId);
        const rect = canvasWrap.getBoundingClientRect();
        dragOffX = (e.clientX - rect.left - panX) / zoom - mesa.x;
        dragOffY = (e.clientY - rect.top  - panY) / zoom - mesa.y;
        dragging = { el, mesa, moved: false };

        el.addEventListener('pointermove', onMesaPointerMove);
        el.addEventListener('pointerup',   onMesaPointerUp);
    }

    function onMesaPointerMove(e) {
        if (!dragging) return;
        const rect = canvasWrap.getBoundingClientRect();
        dragging.mesa.x = Math.max(0, snap((e.clientX - rect.left - panX) / zoom - dragOffX));
        dragging.mesa.y = Math.max(0, snap((e.clientY - rect.top  - panY) / zoom - dragOffY));
        dragging.el.style.left = dragging.mesa.x + 'px';
        dragging.el.style.top  = dragging.mesa.y + 'px';
        dragging.moved = true;
    }

    function onMesaPointerUp() {
        if (!dragging) return;
        dragging.el.removeEventListener('pointermove', onMesaPointerMove);
        dragging.el.removeEventListener('pointerup',   onMesaPointerUp);
        if (dragging.moved) marcarAlterado();
        dragging = null;
    }

    /* ── pan canvas ── */
    canvasWrap.addEventListener('pointerdown', e => {
        if (ferramenta === 'linha' || ferramenta === 'texto') {
            onDrawStart(e); return;
        }
        if (e.target !== canvasWrap && e.target !== canvas && e.target !== svgLayer) return;
        isPanning = true;
        panStartX = e.clientX - panX;
        panStartY = e.clientY - panY;
        canvasWrap.setPointerCapture(e.pointerId);
    });

    canvasWrap.addEventListener('pointermove', e => {
        if (ferramenta === 'linha' && drawStart) { onDrawMove(e); return; }
        if (!isPanning) return;
        panX = e.clientX - panStartX;
        panY = e.clientY - panStartY;
        applyTransform();
    });

    canvasWrap.addEventListener('pointerup', e => {
        if (ferramenta === 'linha' && drawStart) { onDrawEnd(e); return; }
        isPanning = false;
    });

    /* ── desenho de linha ── */
    function onDrawStart(e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        canvasWrap.setPointerCapture(e.pointerId);

        if (ferramenta === 'texto') {
            const p = clientToCanvas(e.clientX, e.clientY);
            const txt = prompt('Digite o texto para o mapa:');
            if (!txt || !txt.trim()) return;
            const cor = document.getElementById('leCorPicker')?.value || '#475569';
            decoracoes.push({ id: Date.now(), ambiente: ambienteAtivo, tipo: 'texto', x1: p.x, y1: p.y, x2: p.x, y2: p.y, texto: txt.trim(), cor });
            marcarAlterado();
            renderDecoracoes();
            return;
        }

        drawStart = clientToCanvas(e.clientX, e.clientY);
        // linha de preview
        svgPreview = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        svgPreview.setAttribute('x1', drawStart.x); svgPreview.setAttribute('y1', drawStart.y);
        svgPreview.setAttribute('x2', drawStart.x); svgPreview.setAttribute('y2', drawStart.y);
        svgPreview.setAttribute('stroke', document.getElementById('leCorPicker')?.value || '#94a3b8');
        svgPreview.setAttribute('stroke-width', '3');
        svgPreview.setAttribute('stroke-linecap', 'round');
        svgPreview.setAttribute('stroke-dasharray', '6 3');
        svgLayer.appendChild(svgPreview);
    }

    function onDrawMove(e) {
        if (!svgPreview || !drawStart) return;
        const p = clientToCanvas(e.clientX, e.clientY);
        svgPreview.setAttribute('x2', p.x);
        svgPreview.setAttribute('y2', p.y);
    }

    function onDrawEnd(e) {
        if (!drawStart) return;
        const p = clientToCanvas(e.clientX, e.clientY);
        if (svgPreview) svgLayer.removeChild(svgPreview);
        svgPreview = null;

        const dx = Math.abs(p.x - drawStart.x), dy = Math.abs(p.y - drawStart.y);
        if (dx > 5 || dy > 5) {
            const cor = document.getElementById('leCorPicker')?.value || '#94a3b8';
            decoracoes.push({ id: Date.now(), ambiente: ambienteAtivo, tipo: 'linha', x1: drawStart.x, y1: drawStart.y, x2: p.x, y2: p.y, texto: '', cor });
            marcarAlterado();
            renderDecoracoes();
        }
        drawStart = null;
    }

    /* ── zoom ── */
    canvasWrap.addEventListener('wheel', e => {
        e.preventDefault();
        zoom = Math.min(2, Math.max(0.3, zoom + (e.deltaY < 0 ? 0.1 : -0.1)));
        applyTransform();
    }, { passive: false });

    btnZoomIn    && btnZoomIn.addEventListener('click',    () => { zoom = Math.min(2, zoom + 0.1); applyTransform(); });
    btnZoomOut   && btnZoomOut.addEventListener('click',   () => { zoom = Math.max(0.3, zoom - 0.1); applyTransform(); });
    btnZoomReset && btnZoomReset.addEventListener('click', () => { zoom = 1; panX = 0; panY = 0; applyTransform(); });

    /* ── salvar layout ── */
    btnSalvar && btnSalvar.addEventListener('click', async () => {
        btnSalvar.disabled = true;
        setStatus('Salvando…', 'info');
        try {
            const payload = {
                mesas: mesas.map(m => ({ id: m.id, posicao_x: m.x, posicao_y: m.y, formato: m.formato, largura: m.largura, altura: m.altura })),
                decoracoes: decoracoes.map(d => ({ ambiente: d.ambiente, tipo: d.tipo, x1: d.x1, y1: d.y1, x2: d.x2, y2: d.y2, texto: d.texto, cor: d.cor }))
            };
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
    window.abrirModalMesa = function (mesa) {
        document.getElementById('mesa_id').value       = mesa ? mesa.id : '';
        document.getElementById('mesa_numero').value   = mesa ? mesa.numero : '';
        document.getElementById('mesa_ambiente').value = mesa ? mesa.ambiente : ambienteAtivo;
        document.getElementById('mesa_formato').value  = mesa ? mesa.formato : 'quadrada';
        document.getElementById('mesa_largura').value  = mesa ? mesa.largura : 60;
        document.getElementById('mesa_altura').value   = mesa ? mesa.altura  : 60;
        document.getElementById('mesa_x').value        = mesa ? mesa.x : 0;
        document.getElementById('mesa_y').value        = mesa ? mesa.y : 0;
        document.getElementById('mesa_erro').hidden    = true;
        document.getElementById('mesaModalTitle').textContent = mesa ? 'Editar mesa' : 'Nova mesa';
        new bootstrap.Modal(document.getElementById('mesaModal')).show();
    };

    window.salvarMesa = async function () {
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
            ativo: 1
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

    window.excluirMesa = async function (id) {
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
        } catch (e) { alert(e.message); }
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
            tr.innerHTML = `<td>${mesa.numero}</td><td>${mesa.ambiente}</td><td>${mesa.formato}</td><td>${mesa.x}, ${mesa.y}</td><td><span class="badge bg-${mesa.ocupada ? 'danger' : 'success'}">${mesa.status}</span></td><td></td>`;
            const td = tr.lastElementChild;
            const btnEdit = document.createElement('button');
            btnEdit.type = 'button'; btnEdit.className = 'btn btn-sm btn-outline-primary me-1';
            btnEdit.innerHTML = '<i class="bi bi-pencil"></i>';
            btnEdit.addEventListener('click', () => abrirModalMesa(mesa));
            const btnDel = document.createElement('button');
            btnDel.type = 'button'; btnDel.className = 'btn btn-sm btn-outline-danger';
            btnDel.innerHTML = '<i class="bi bi-trash"></i>';
            btnDel.addEventListener('click', () => excluirMesa(mesa.id));
            td.append(btnEdit, btnDel);
            tbody.appendChild(tr);
        });
    }

    window.addEventListener('beforeunload', e => {
        if (alteracoesPendentes) { e.preventDefault(); e.returnValue = ''; }
    });

    carregarMesas();
})();
