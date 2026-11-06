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

    let mesas      = [];
    let decoracoes = [];
    let ambienteAtivo = '';
    let ambientes  = [];
    let zoom = 1, panX = 0, panY = 0;
    let isPanning  = false, panStartX = 0, panStartY = 0;
    let dragging   = null;   // mesa drag
    let alteracoesPendentes = false;
    let ferramenta = 'select';

    // estado de seleção/edição de decoração
    let selDec     = null;   // decoração selecionada
    let editOp     = null;   // {type:'move'|'p1'|'p2', startX,startY, origDec}

    // estado de desenho de nova linha
    let drawStart  = null;
    let svgPreview = null;

    /* ── DOM refs ── */
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
    function cor()   { return document.getElementById('leCorPicker')?.value || '#475569'; }

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
        const r = canvasWrap.getBoundingClientRect();
        return { x: snap((cx - r.left - panX) / zoom), y: snap((cy - r.top - panY) / zoom) };
    }

    /* ── ferramentas ── */
    toolBtns.forEach(btn => btn.addEventListener('click', () => {
        ferramenta = btn.dataset.tool;
        toolBtns.forEach(b => b.classList.toggle('le-tool--active', b === btn));
        canvasWrap.style.cursor = ferramenta === 'select' ? 'grab' : 'crosshair';
        if (ferramenta !== 'select') { selDec = null; renderDecoracoes(); }
    }));

    /* ── carregar ── */
    async function carregarMesas() {
        setStatus('Carregando…', 'info');
        try {
            const r = await fetch(mapaMesasUrl);
            const d = await r.json();
            if (!d.success) throw new Error(d.message);

            mesas = d.mesas.map(m => ({
                id: parseInt(m.mesa_id), numero: m.numero_mesa, ambiente: m.ambiente,
                x: parseInt(m.posicao_x), y: parseInt(m.posicao_y),
                formato: m.formato || 'quadrada',
                largura: parseInt(m.largura) || 60, altura: parseInt(m.altura) || 60,
                status: m.status_mesa, ocupada: m.status_mesa === 'ocupada'
            }));

            decoracoes = (d.decoracoes || []).map(d => ({
                id: parseInt(d.id), ambiente: d.ambiente, tipo: d.tipo,
                x1: parseInt(d.x1), y1: parseInt(d.y1),
                x2: parseInt(d.x2), y2: parseInt(d.y2),
                texto: d.texto || '', cor: d.cor || '#94a3b8'
            }));

            ambientes = [...new Set(mesas.map(m => m.ambiente))];
            if (!ambientes.length) ambientes = ['Salão Principal'];
            if (!ambienteAtivo || !ambientes.includes(ambienteAtivo)) ambienteAtivo = ambientes[0];

            renderTabs(); renderCanvas(); renderTabela();
            setStatus('', 'info');
        } catch (e) { setStatus('Erro ao carregar: ' + e.message, 'error'); }
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
            btn.addEventListener('click', () => { ambienteAtivo = amb; selDec = null; renderTabs(); renderCanvas(); });
            tabsEl.appendChild(btn);
        });
    }

    /* ── transform ── */
    function applyTransform() {
        const t = `translate(${panX}px,${panY}px) scale(${zoom})`;
        canvas.style.transform = svgLayer.style.transform = t;
        canvas.style.transformOrigin = svgLayer.style.transformOrigin = '0 0';
    }

    /* ── render mesas ── */
    function renderCanvas() {
        canvas.replaceChildren();
        mesas.filter(m => m.ambiente === ambienteAtivo).forEach(mesa => {
            const el = document.createElement('div');
            el.className = 'le-mesa le-mesa--' + mesa.formato + (mesa.ocupada ? ' le-mesa--ocupada' : ' le-mesa--livre');
            el.style.cssText = `left:${mesa.x}px;top:${mesa.y}px;width:${mesa.largura}px;height:${mesa.altura}px`;
            el.dataset.id = mesa.id;
            el.innerHTML = `<span class="le-mesa-num">${mesa.numero}</span><span class="le-mesa-status">${mesa.ocupada ? 'Ocupada' : 'Livre'}</span>`;
            el.addEventListener('pointerdown', onMesaPointerDown);
            el.addEventListener('dblclick', () => { if (ferramenta === 'select') abrirModalMesa(mesa); });
            canvas.appendChild(el);
        });
        renderDecoracoes();
        applyTransform();
    }

    /* ── render decorações ── */
    function renderDecoracoes() {
        while (svgLayer.firstChild) svgLayer.removeChild(svgLayer.firstChild);

        decoracoes.filter(d => d.ambiente === ambienteAtivo).forEach(dec => {
            const isSel = selDec && selDec.id === dec.id;

            if (dec.tipo === 'linha') {
                // linha principal
                const line = makeSvg('line', {
                    x1: dec.x1, y1: dec.y1, x2: dec.x2, y2: dec.y2,
                    stroke: dec.cor, 'stroke-width': 3, 'stroke-linecap': 'round'
                });
                line.classList.add('le-dec-linha');
                if (isSel) line.setAttribute('stroke-dasharray', '6 3');
                line.addEventListener('pointerdown', e => onDecPointerDown(e, dec, 'move'));
                svgLayer.appendChild(line);

                // área de clique mais larga (invisível)
                const hit = makeSvg('line', {
                    x1: dec.x1, y1: dec.y1, x2: dec.x2, y2: dec.y2,
                    stroke: 'transparent', 'stroke-width': 16
                });
                hit.style.cursor = 'move';
                hit.addEventListener('pointerdown', e => onDecPointerDown(e, dec, 'move'));
                svgLayer.appendChild(hit);

                if (isSel) {
                    // handle ponto 1
                    svgLayer.appendChild(makeHandle(dec.x1, dec.y1, dec, 'p1'));
                    // handle ponto 2
                    svgLayer.appendChild(makeHandle(dec.x2, dec.y2, dec, 'p2'));
                    // botão deletar
                    svgLayer.appendChild(makeDeleteBtn((dec.x1 + dec.x2) / 2, (dec.y1 + dec.y2) / 2 - 18, dec));
                }

            } else if (dec.tipo === 'texto') {
                const txt = makeSvg('text', {
                    x: dec.x1, y: dec.y1, fill: dec.cor,
                    'font-size': 14, 'font-family': 'Segoe UI, sans-serif', 'font-weight': 600
                });
                txt.textContent = dec.texto;
                txt.classList.add('le-dec-texto');
                txt.style.cursor = 'move';
                if (isSel) txt.setAttribute('text-decoration', 'underline');
                txt.addEventListener('pointerdown', e => onDecPointerDown(e, dec, 'move'));
                svgLayer.appendChild(txt);

                if (isSel) {
                    svgLayer.appendChild(makeDeleteBtn(dec.x1 + 4, dec.y1 - 18, dec));
                    // handle editar texto
                    svgLayer.appendChild(makeEditBtn(dec.x1 + 24, dec.y1 - 18, dec));
                }
            }
        });
    }

    function makeSvg(tag, attrs) {
        const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
        return el;
    }

    function makeHandle(cx, cy, dec, point) {
        const c = makeSvg('circle', { cx, cy, r: 7, fill: '#fff', stroke: '#ea580c', 'stroke-width': 2 });
        c.style.cursor = point === 'p1' ? 'nwse-resize' : 'nwse-resize';
        c.addEventListener('pointerdown', e => { e.stopPropagation(); onDecPointerDown(e, dec, point); });
        return c;
    }

    function makeDeleteBtn(cx, cy, dec) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.style.cursor = 'pointer';
        const bg = makeSvg('rect', { x: cx - 10, y: cy - 10, width: 20, height: 20, rx: 4, fill: '#dc2626' });
        const ic = makeSvg('text', { x: cx, y: cy + 5, 'text-anchor': 'middle', fill: '#fff', 'font-size': 13, 'font-family': 'sans-serif' });
        ic.textContent = '✕';
        g.append(bg, ic);
        g.addEventListener('click', e => { e.stopPropagation(); deletarDecoracao(dec.id); });
        return g;
    }

    function makeEditBtn(cx, cy, dec) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.style.cursor = 'pointer';
        const bg = makeSvg('rect', { x: cx - 10, y: cy - 10, width: 20, height: 20, rx: 4, fill: '#ea580c' });
        const ic = makeSvg('text', { x: cx, y: cy + 5, 'text-anchor': 'middle', fill: '#fff', 'font-size': 11, 'font-family': 'sans-serif' });
        ic.textContent = '✎';
        g.append(bg, ic);
        g.addEventListener('click', e => {
            e.stopPropagation();
            const novo = prompt('Editar texto:', dec.texto);
            if (novo !== null && novo.trim()) { dec.texto = novo.trim(); marcarAlterado(); renderDecoracoes(); }
        });
        return g;
    }

    function deletarDecoracao(id) {
        decoracoes = decoracoes.filter(d => d.id !== id);
        if (selDec && selDec.id === id) selDec = null;
        marcarAlterado();
        renderDecoracoes();
    }

    /* ── interação decorações ── */
    function onDecPointerDown(e, dec, type) {
        if (ferramenta !== 'select') return;
        e.stopPropagation();
        selDec = dec;
        const p = clientToCanvas(e.clientX, e.clientY);
        editOp = { type, startX: p.x, startY: p.y, origDec: { ...dec } };
        svgLayer.setPointerCapture(e.pointerId);
        renderDecoracoes();
    }

    /* ── drag mesas ── */
    function onMesaPointerDown(e) {
        if (ferramenta !== 'select') return;
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        e.stopPropagation();
        selDec = null; renderDecoracoes();
        const el = e.currentTarget;
        const mesa = mesas.find(m => m.id === parseInt(el.dataset.id));
        if (!mesa || mesa.ocupada) return;
        el.setPointerCapture(e.pointerId);
        const r = canvasWrap.getBoundingClientRect();
        const offX = (e.clientX - r.left - panX) / zoom - mesa.x;
        const offY = (e.clientY - r.top  - panY) / zoom - mesa.y;
        dragging = { el, mesa, offX, offY, moved: false };
        el.addEventListener('pointermove', onMesaMove);
        el.addEventListener('pointerup',   onMesaUp);
    }

    function onMesaMove(e) {
        if (!dragging) return;
        const r = canvasWrap.getBoundingClientRect();
        dragging.mesa.x = Math.max(0, snap((e.clientX - r.left - panX) / zoom - dragging.offX));
        dragging.mesa.y = Math.max(0, snap((e.clientY - r.top  - panY) / zoom - dragging.offY));
        dragging.el.style.left = dragging.mesa.x + 'px';
        dragging.el.style.top  = dragging.mesa.y + 'px';
        dragging.moved = true;
    }

    function onMesaUp() {
        if (!dragging) return;
        dragging.el.removeEventListener('pointermove', onMesaMove);
        dragging.el.removeEventListener('pointerup',   onMesaUp);
        if (dragging.moved) marcarAlterado();
        dragging = null;
    }

    /* ── pan + draw ── */
    canvasWrap.addEventListener('pointerdown', e => {
        if (ferramenta === 'linha' || ferramenta === 'texto') { onDrawStart(e); return; }
        if (e.target !== canvasWrap && e.target !== canvas && e.target.closest('svg') !== svgLayer) {
            // clique no fundo deseleciona
            if (e.target === canvasWrap || e.target === canvas) { selDec = null; renderDecoracoes(); }
            return;
        }
        selDec = null; renderDecoracoes();
        isPanning = true;
        panStartX = e.clientX - panX; panStartY = e.clientY - panY;
        canvasWrap.setPointerCapture(e.pointerId);
    });

    canvasWrap.addEventListener('pointermove', e => {
        if (ferramenta === 'linha' && drawStart) { onDrawMove(e); return; }
        if (editOp) { onDecMove(e); return; }
        if (!isPanning) return;
        panX = e.clientX - panStartX; panY = e.clientY - panStartY;
        applyTransform();
    });

    canvasWrap.addEventListener('pointerup', e => {
        if (ferramenta === 'linha' && drawStart) { onDrawEnd(e); return; }
        if (editOp) { editOp = null; return; }
        isPanning = false;
    });

    /* ── mover/esticar decoração ── */
    function onDecMove(e) {
        if (!editOp || !selDec) return;
        const p = clientToCanvas(e.clientX, e.clientY);
        const dx = p.x - editOp.startX;
        const dy = p.y - editOp.startY;
        const o  = editOp.origDec;
        const dec = decoracoes.find(d => d.id === selDec.id);
        if (!dec) return;

        if (editOp.type === 'move') {
            dec.x1 = Math.max(0, o.x1 + dx); dec.y1 = Math.max(0, o.y1 + dy);
            dec.x2 = Math.max(0, o.x2 + dx); dec.y2 = Math.max(0, o.y2 + dy);
        } else if (editOp.type === 'p1') {
            dec.x1 = Math.max(0, o.x1 + dx); dec.y1 = Math.max(0, o.y1 + dy);
        } else if (editOp.type === 'p2') {
            dec.x2 = Math.max(0, o.x2 + dx); dec.y2 = Math.max(0, o.y2 + dy);
        }
        selDec = dec;
        marcarAlterado();
        renderDecoracoes();
    }

    /* ── desenho nova linha ── */
    function onDrawStart(e) {
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        canvasWrap.setPointerCapture(e.pointerId);

        if (ferramenta === 'texto') {
            const p = clientToCanvas(e.clientX, e.clientY);
            const txt = prompt('Digite o texto para o mapa:');
            if (!txt || !txt.trim()) return;
            decoracoes.push({ id: Date.now(), ambiente: ambienteAtivo, tipo: 'texto', x1: p.x, y1: p.y, x2: p.x, y2: p.y, texto: txt.trim(), cor: cor() });
            marcarAlterado(); renderDecoracoes(); return;
        }

        drawStart = clientToCanvas(e.clientX, e.clientY);
        svgPreview = makeSvg('line', { x1: drawStart.x, y1: drawStart.y, x2: drawStart.x, y2: drawStart.y, stroke: cor(), 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-dasharray': '6 3' });
        svgLayer.appendChild(svgPreview);
    }

    function onDrawMove(e) {
        if (!svgPreview || !drawStart) return;
        const p = clientToCanvas(e.clientX, e.clientY);
        svgPreview.setAttribute('x2', p.x); svgPreview.setAttribute('y2', p.y);
    }

    function onDrawEnd(e) {
        if (!drawStart) return;
        const p = clientToCanvas(e.clientX, e.clientY);
        if (svgPreview) svgLayer.removeChild(svgPreview);
        svgPreview = null;
        if (Math.abs(p.x - drawStart.x) > 5 || Math.abs(p.y - drawStart.y) > 5) {
            decoracoes.push({ id: Date.now(), ambiente: ambienteAtivo, tipo: 'linha', x1: drawStart.x, y1: drawStart.y, x2: p.x, y2: p.y, texto: '', cor: cor() });
            marcarAlterado(); renderDecoracoes();
        }
        drawStart = null;
    }

    /* ── tecla Delete ── */
    document.addEventListener('keydown', e => {
        if ((e.key === 'Delete' || e.key === 'Backspace') && selDec && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            deletarDecoracao(selDec.id);
        }
    });

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
            const r = await fetch(salvarLayoutUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(payload) });
            const d = await r.json();
            if (!d.success) throw new Error(d.message);
            alteracoesPendentes = false;
            btnSalvar.classList.remove('btn-warning');
            setStatus('Layout salvo!', 'success');
            setTimeout(() => setStatus('', 'info'), 3000);
        } catch (e) {
            setStatus('Erro ao salvar: ' + e.message, 'error');
        } finally { btnSalvar.disabled = false; }
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
            id: parseInt(document.getElementById('mesa_id').value) || null,
            numero: document.getElementById('mesa_numero').value.trim(),
            ambiente: document.getElementById('mesa_ambiente').value.trim() || 'Salão Principal',
            formato: document.getElementById('mesa_formato').value,
            largura: parseInt(document.getElementById('mesa_largura').value) || 60,
            altura:  parseInt(document.getElementById('mesa_altura').value)  || 60,
            posicao_x: parseInt(document.getElementById('mesa_x').value) || 0,
            posicao_y: parseInt(document.getElementById('mesa_y').value) || 0,
            ativo: 1
        };
        try {
            const r = await fetch(salvarMesaUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(payload) });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.message || 'Não foi possível salvar.');
            bootstrap.Modal.getInstance(document.getElementById('mesaModal')).hide();
            await carregarMesas();
        } catch (e) { erro.textContent = e.message; erro.hidden = false; }
    };

    window.excluirMesa = async function (id) {
        if (!confirm('Excluir esta mesa? O histórico de comandas será preservado.')) return;
        try {
            const r = await fetch(deletarMesaUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ id }) });
            const d = await r.json();
            if (!r.ok || !d.success) throw new Error(d.message || 'Não foi possível excluir.');
            await carregarMesas();
        } catch (e) { alert(e.message); }
    };

    /* ── tabela lista ── */
    function renderTabela() {
        const tbody = document.getElementById('mesas-tbody');
        if (!tbody) return;
        if (!mesas.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhuma mesa cadastrada.</td></tr>'; return; }
        tbody.replaceChildren();
        mesas.forEach(mesa => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${mesa.numero}</td><td>${mesa.ambiente}</td><td>${mesa.formato}</td><td>${mesa.x}, ${mesa.y}</td><td><span class="badge bg-${mesa.ocupada ? 'danger' : 'success'}">${mesa.status}</span></td><td></td>`;
            const td = tr.lastElementChild;
            const be = document.createElement('button'); be.type = 'button'; be.className = 'btn btn-sm btn-outline-primary me-1'; be.innerHTML = '<i class="bi bi-pencil"></i>'; be.addEventListener('click', () => abrirModalMesa(mesa));
            const bd = document.createElement('button'); bd.type = 'button'; bd.className = 'btn btn-sm btn-outline-danger'; bd.innerHTML = '<i class="bi bi-trash"></i>'; bd.addEventListener('click', () => excluirMesa(mesa.id));
            td.append(be, bd); tbody.appendChild(tr);
        });
    }

    window.addEventListener('beforeunload', e => { if (alteracoesPendentes) { e.preventDefault(); e.returnValue = ''; } });

    carregarMesas();
})();
