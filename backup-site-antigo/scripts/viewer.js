/* ==========================================
   VISUALIZADOR DE FOTOS (cardápio + galeria)
   Abre qualquer elemento com o atributo data-viewer
========================================== */

(function () {
    const box = document.getElementById('viewer');
    if (!box) return;

    const img = document.getElementById('viewerImg');
    const caption = document.getElementById('viewerCaption');
    const counter = document.getElementById('viewerCounter');
    const btnClose = document.getElementById('viewerClose');
    const btnPrev = document.getElementById('viewerPrev');
    const btnNext = document.getElementById('viewerNext');

    let itens = [];
    let atual = 0;

    /* marca como "vazia" toda foto que não carregou */
    function marcarVazia(el) {
        const host = el.closest('[data-viewer]');
        if (host) host.classList.add('vazia');
    }

    document.addEventListener('error', function (e) {
        if (e.target.tagName === 'IMG') marcarVazia(e.target);
    }, true);

    document.querySelectorAll('[data-viewer] img').forEach(function (i) {
        if (i.complete && i.naturalWidth === 0) marcarVazia(i);
    });

    function coletar() {
        return Array.from(document.querySelectorAll('[data-viewer]:not(.vazia)'));
    }

    function mostrar(i, animar) {
        const el = itens[i];

        const aplicar = function () {
            img.src = el.dataset.src;
            img.alt = el.dataset.caption || '';
            caption.textContent = el.dataset.caption || '';
            counter.textContent = (i + 1) + ' / ' + itens.length;
            img.classList.remove('trocando');
        };

        if (animar) {
            img.classList.add('trocando');
            setTimeout(aplicar, 200);
        } else {
            aplicar();
        }

        const varias = itens.length > 1;
        btnPrev.style.display = varias ? '' : 'none';
        btnNext.style.display = varias ? '' : 'none';
    }

    function abrir(el) {
        itens = coletar();
        atual = itens.indexOf(el);
        if (atual < 0) return;
        mostrar(atual, false);
        box.classList.add('show');
        box.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function fechar() {
        box.classList.remove('show');
        box.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function navegar(direcao) {
        atual = (atual + direcao + itens.length) % itens.length;
        mostrar(atual, true);
    }

    /* clique nas fotos */
    document.addEventListener('click', function (e) {
        const el = e.target.closest('[data-viewer]');
        if (!el || el.classList.contains('vazia')) return;
        abrir(el);
    });

    /* Enter / espaço em fotos focadas (galeria) */
    document.addEventListener('keydown', function (e) {
        if (box.classList.contains('show')) return;
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const el = e.target.closest && e.target.closest('figure[data-viewer]');
        if (el && !el.classList.contains('vazia')) {
            e.preventDefault();
            abrir(el);
        }
    });

    btnClose.addEventListener('click', fechar);
    btnPrev.addEventListener('click', function () { navegar(-1); });
    btnNext.addEventListener('click', function () { navegar(1); });

    /* clique no fundo escuro fecha */
    box.addEventListener('click', function (e) {
        if (e.target === box) fechar();
    });

    /* teclado */
    document.addEventListener('keydown', function (e) {
        if (!box.classList.contains('show')) return;
        if (e.key === 'Escape') fechar();
        if (e.key === 'ArrowLeft') navegar(-1);
        if (e.key === 'ArrowRight') navegar(1);
    });

    /* deslizar no celular */
    let toqueX = 0;

    box.addEventListener('touchstart', function (e) {
        toqueX = e.changedTouches[0].clientX;
    }, { passive: true });

    box.addEventListener('touchend', function (e) {
        const dx = e.changedTouches[0].clientX - toqueX;
        if (Math.abs(dx) > 50) navegar(dx > 0 ? -1 : 1);
    }, { passive: true });
})();
