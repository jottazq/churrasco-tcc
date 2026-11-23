<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

try {
    $categorias = db_all('SELECT * FROM categorias WHERE ativa = 1 ORDER BY ordem, id');
    $porCategoria = [];
    foreach (db_all('SELECT * FROM itens WHERE ativo = 1 ORDER BY ordem, id') as $it) {
        $porCategoria[(int) $it['categoria_id']][] = $it;
    }
} catch (Throwable $e) {
    error_log('[churrasco] cardápio indisponível: ' . $e->getMessage());
    pub_indisponivel();
    exit;
}

// só mostra categorias que têm pelo menos um item visível
$categorias = array_values(array_filter($categorias, fn($c) => !empty($porCategoria[(int) $c['id']])));

pub_inicio(
    'Cardápio - ' . cfg('site_nome'),
    'cardapio',
    ['styles/style-cardapio.css', 'styles/viewer.css'],
    'Cardápio do ' . cfg('site_nome') . ': churrasco na brasa, acompanhamentos, porções e bebidas geladas.'
);

$botaoPedir = cfg('link_pedido') !== ''
    ? '        <a href="' . e(cfg('link_pedido')) . '" class="btn btn-primary" target="_blank" rel="noopener noreferrer">PEDIR AGORA</a>' . "\n"
    : '';

pub_topo(
    'img/churrasco-misto.jpg',
    'Churrasco na brasa',
    'Cardápio',
    'SABOR NA BRASA',
    'CARDÁPIO',
    'Cortes selecionados, acompanhamentos e bebidas geladas para deixar seu churrasco completo.',
    [['fa-fire', 'Direto da brasa'], ['fa-motorcycle', 'Delivery rápido'], ['fa-kitchen-set', 'Receita da casa']],
    $botaoPedir
);
?>
<!-- ================= NAVEGAÇÃO RÁPIDA ================= -->
<nav class="menu-nav">
    <div class="container">
<?php foreach ($categorias as $c): ?>
        <a href="#<?= e($c['slug']) ?>"><?= e($c['nome_menu']) ?></a>
<?php endforeach; ?>
    </div>
</nav>


<!-- ================= CARDÁPIO ================= -->
<section class="pagina-cardapio">

    <div class="container">

        <div class="cardapio-lista">

<?php if (!$categorias): ?>
            <p style="text-align:center;color:#aaa;padding:40px 0">O cardápio está sendo atualizado. Volte em instantes ou chame a gente no WhatsApp.</p>
<?php endif; ?>

<?php foreach ($categorias as $c):
    $lista = $porCategoria[(int) $c['id']];
    $qtd = count($lista);
?>
            <!-- ================= <?= e(mb_strtoupper($c['nome'])) ?> ================= -->
            <div class="categoria" id="<?= e($c['slug']) ?>">

                <div class="categoria-header">
                    <span class="icon-badge"><i class="fas <?= e($c['icone']) ?>"></i></span>

                    <div class="categoria-titulo">
                        <h2><?= e($c['nome']) ?></h2>
<?php if ($c['descricao'] !== ''): ?>
                        <p><?= e($c['descricao']) ?></p>
<?php endif; ?>
                    </div>

                    <span class="count"><?= $qtd ?> <?= $qtd === 1 ? 'item' : 'itens' ?></span>
                </div>

                <div class="items-grid">

<?php foreach ($lista as $it):
        $classes = 'item';
        if ($c['estilo'] === 'simples') { $classes .= ' item-simples'; }
        if ($it['destaque']) { $classes .= ' destaque'; }
        if ($it['esgotado']) { $classes .= ' esgotado'; }
        $temFoto = !empty($it['foto']);
?>
                    <article class="<?= e($classes) ?>">
<?php if ($temFoto): ?>
                        <button type="button" class="item-foto"
                            data-viewer
                            data-src="<?= e($it['foto']) ?>"
                            data-caption="<?= e($it['nome']) ?>">
                            <img src="<?= e($it['foto']) ?>" alt="<?= e($it['nome']) ?>" loading="lazy">
                            <span class="zoom"><i class="fas fa-expand"></i></span>
                        </button>
<?php else: ?>
                        <div class="item-foto" style="cursor:default" aria-hidden="true">
                            <span style="display:grid;place-items:center;width:100%;height:100%;color:#555;font-size:1.6rem"><i class="fas <?= e($c['icone']) ?>"></i></span>
                        </div>
<?php endif; ?>

                        <div class="item-info">
                            <h3><?= e($it['nome']) ?></h3>
<?php if ($it['descricao'] !== ''): ?>
                            <p><?= e($it['descricao']) ?></p>
<?php endif; ?>
<?php if ($it['esgotado']): ?>
                            <span class="item-preco esgotado-tag">Esgotado</span>
<?php else: ?>
                            <span class="item-preco"><?= e(preco_br($it['preco'])) ?></span>
<?php endif; ?>
                        </div>
                    </article>

<?php endforeach; ?>
                </div>
            </div>

<?php endforeach; ?>
        </div>

<?php if (cfg('obs_cardapio') !== ''): ?>
        <!-- ================= OBSERVAÇÃO ================= -->
        <p class="observacao">
            <i class="fas fa-circle-info"></i>
            <?= e(cfg('obs_cardapio')) ?>
        </p>
<?php endif; ?>

    </div>

</section>

<!-- ================= CHAMADA ================= -->
<section class="call">

    <h2>Ficou com fome?</h2>

    <p>
        Peça agora e receba o verdadeiro sabor do churrasco na brasa
        direto na sua casa, ou venha nos visitar!
    </p>

    <a href="<?= e(wa_link()) ?>"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-primary">
        <i class="fab fa-whatsapp"></i> CHAMAR NO WHATSAPP
    </a>

</section>

<?php
pub_visualizador();
pub_fim(['scripts/script.js', 'scripts/viewer.js']);
