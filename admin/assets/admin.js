/* ==========================================================
   PAINEL ADMIN — comportamentos de tela (sem bibliotecas)
========================================================== */
(function () {
    'use strict';

    /* ---- menu lateral no celular ---- */
    var corpo = document.body;
    var abrir = document.querySelector('[data-menu-abrir]');
    var fundo = document.querySelector('.sombra-menu');
    if (abrir) {
        abrir.addEventListener('click', function () { corpo.classList.toggle('menu-aberto'); });
    }
    if (fundo) {
        fundo.addEventListener('click', function () { corpo.classList.remove('menu-aberto'); });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') corpo.classList.remove('menu-aberto');
    });

    /* ---- confirmação antes de ações perigosas ---- */
    document.addEventListener('click', function (e) {
        var alvo = e.target.closest('[data-confirmar]');
        if (alvo && !window.confirm(alvo.getAttribute('data-confirmar'))) {
            e.preventDefault();
        }
    });

    /* ---- evita clique duplo ao enviar formulários ---- */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || form.hasAttribute('data-livre')) return;
        var botao = e.submitter || form.querySelector('button[type=submit]');
        if (botao) {
            // atrasa 1 tick para o nome/valor do botão ainda ser enviado
            setTimeout(function () { botao.classList.add('carregando'); }, 0);
        }
    });

    /* ---- prévia da foto escolhida ---- */
    document.querySelectorAll('input[type=file][data-previa]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            var alvo = document.querySelector(campo.getAttribute('data-previa'));
            if (!alvo || !campo.files || !campo.files[0]) return;
            var url = URL.createObjectURL(campo.files[0]);
            alvo.innerHTML = '';
            var img = document.createElement('img');
            img.src = url;
            img.alt = 'Prévia da foto';
            alvo.appendChild(img);
        });
    });

    /* ---- preço: "26" vira "26,00" ao sair do campo ---- */
    document.querySelectorAll('input[data-preco]').forEach(function (campo) {
        campo.addEventListener('blur', function () {
            var t = campo.value.replace(/[^\d.,]/g, '');
            if (!t) return;
            if (t.indexOf(',') !== -1) { t = t.replace(/\./g, '').replace(',', '.'); }
            var n = parseFloat(t);
            if (!isNaN(n)) campo.value = n.toFixed(2).replace('.', ',');
        });
    });

    /* ---- horários: escurece os campos quando o dia está fechado ---- */
    document.querySelectorAll('.horario').forEach(function (linha) {
        var chave = linha.querySelector('input[type=checkbox]');
        if (!chave) return;
        var atualizar = function () { linha.classList.toggle('fechado', !chave.checked); };
        chave.addEventListener('change', atualizar);
        atualizar();
    });

    /* ---- some com os avisos de sucesso depois de alguns segundos ---- */
    document.querySelectorAll('.aviso.ok').forEach(function (a) {
        setTimeout(function () {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(function () { a.remove(); }, 500);
        }, 5000);
    });
})();
