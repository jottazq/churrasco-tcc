<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

// Banner: fotos cadastradas no painel (se o banco falhar, usa a foto padrão)
$banners = [];
try {
    $banners = db_all('SELECT arquivo, texto_alt FROM banners WHERE ativo = 1 ORDER BY ordem, id');
} catch (Throwable $e) {
    error_log('[churrasco] banner indisponível: ' . $e->getMessage());
}
if (!$banners) {
    $banners = [['arquivo' => 'img/churrasco-misto.jpg', 'texto_alt' => 'Churrasco na brasa']];
}
$total = count($banners);

pub_inicio(cfg('site_nome'), 'inicio', [], cfg('hero_texto'));
?>
<!-- ================= HERO ================= -->

<section class="hero">

    <!-- Slides do Banner (deslizam para os lados) -->
    <div class="slider">

        <div class="slider-track" style="width:<?= $total * 100 ?>%">
<?php foreach ($banners as $b): ?>
            <div class="slide" style="width:<?= round(100 / $total, 4) ?>%">
                <div class="overlay"></div>
                <img src="<?= e($b['arquivo']) ?>" alt="<?= e($b['texto_alt'] !== '' ? $b['texto_alt'] : 'Churrasco do Baiano') ?>">
            </div>
<?php endforeach; ?>
        </div>

    </div>

    <!-- Conteúdo de texto fixo (não troca com as fotos) -->
    <div class="hero-content">

<?php if (cfg('hero_subtitulo') !== ''): ?>
        <span class="subtitle"><?= e(cfg('hero_subtitulo')) ?></span>
<?php endif; ?>

        <h1>CHURRASCO<br>DO BAIANO<br>SEM MISÉRIA</h1>

        <p><?= e(cfg('hero_texto')) ?></p>

        <div class="hero-buttons">

<?php if (cfg('link_pedido') !== ''): ?>
            <a href="<?= e(cfg('link_pedido')) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                PEDIR AGORA
            </a>
<?php endif; ?>

            <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline">
                <i class="fab fa-whatsapp"></i> WHATSAPP
            </a>

        </div>

    </div>

<?php if ($total > 1): ?>
    <!-- Setas -->
    <button class="arrow prev" aria-label="Foto anterior"><i class="fas fa-chevron-left"></i></button>
    <button class="arrow next" aria-label="Próxima foto"><i class="fas fa-chevron-right"></i></button>
<?php endif; ?>

    <!-- Indicadores -->
    <div class="indicators"<?= $total > 1 ? '' : ' style="display:none"' ?>>
<?php for ($i = 0; $i < $total; $i++): ?>
        <span class="dot<?= $i === 0 ? ' active' : '' ?>"></span>
<?php endfor; ?>
    </div>

</section>

<!-- ================= DESTAQUES ================= -->

<section class="destaques">

    <div class="container">

        <span class="section-subtitle">CONFIRA</span>

        <h2 class="titulo">NOSSOS DESTAQUES</h2>

        <div class="cards">

            <div class="card">
                <div class="icon"><i class="fas fa-drumstick-bite"></i></div>
                <h3>Carnes Selecionadas</h3>
                <p>Cortes especiais preparados na brasa.</p>
            </div>

            <div class="card">
                <div class="icon"><i class="fas fa-martini-glass-citrus"></i></div>
                <h3>Bebidas Geladas</h3>
                <p>As melhores bebidas para acompanhar seu churrasco.</p>
            </div>

            <div class="card">
                <div class="icon"><i class="fas fa-people-roof"></i></div>
                <h3>Ambiente Familiar</h3>
                <p>Conforto e qualidade para toda a família.</p>
            </div>

        </div>

    </div>

</section>

<?php
pub_chamada('Bora Comer?');
pub_fim();
