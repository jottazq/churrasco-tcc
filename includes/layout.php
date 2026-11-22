<?php
/**
 * Cabeçalho, rodapé e visualizador de fotos do site público.
 * Mantém exatamente o mesmo HTML/CSS que as páginas já tinham.
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/** Abre a página: <html>, <head> e o menu do topo. */
function pub_inicio(string $titulo, string $ativa, array $cssExtras = [], string $descricao = '', array $scriptsHead = []): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');

    $nome = cfg('site_nome');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?></title>
<?php if ($descricao !== ''): ?>
    <meta name="description" content="<?= e($descricao) ?>">
<?php endif; ?>

    <link rel="stylesheet" href="styles/style.css">
<?php foreach ($cssExtras as $css): ?>
    <link rel="stylesheet" href="<?= e($css) ?>">
<?php endforeach; ?>
    <link rel="icon" type="image/png" href="img/icon.png">

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<?php foreach ($scriptsHead as $src): ?>
    <script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</head>

<body>

<!-- ================= NAVBAR ================= -->
<header class="header">
    <div class="container">

        <button class="menu-btn" onclick="toggleMenu()" aria-label="Abrir menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="logo">
            <div class="logo-text">
                <h2>CHURRASCO</h2>
                <span>DO BAIANO SEM MISÉRIA</span>
            </div>
        </div>

        <nav class="navbar" id="menu">
            <ul>
<?php
    $links = [
        'inicio'      => ['index.php',       'fa-house',         'Início'],
        'cardapio'    => ['cardapio.php',    'fa-drumstick-bite', 'Cardápio'],
        'galeria'     => ['galeria.php',     'fa-camera',        'Galeria'],
        'sobre'       => ['sobre-nos.php',   'fa-user-tie',      'Sobre Nós'],
        'localizacao' => ['localizacao.php', 'fa-location-dot',  'Localização'],
        'contato'     => ['contato.php',     'fa-phone',         'Contato'],
    ];
    foreach ($links as $chave => [$href, $icone, $rotulo]): ?>
                <li><a<?= $chave === $ativa ? ' class="active"' : '' ?> href="<?= e($href) ?>"><i class="fas <?= e($icone) ?>"></i> <?= e($rotulo) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>

        <div class="social">
<?php if (cfg('instagram_url') !== ''): ?>
            <a href="<?= e(cfg('instagram_url')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
<?php endif; ?>
<?php if (cfg('facebook_url') !== ''): ?>
            <a href="<?= e(cfg('facebook_url')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
<?php endif; ?>
            <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>

    </div>
</header>

<?php
}

/** Fecha a página: rodapé + scripts. */
function pub_fim(array $scripts = ['scripts/script.js']): void
{
    ?>

<!-- ================= RODAPÉ ================= -->
<footer>
    <div class="container footer-content">
        <p><strong><?= e(cfg('site_nome')) ?></strong></p>
        <p><i class="fas fa-location-dot"></i> <?= e(cfg('cidade_rodape')) ?></p>
        <p class="copyright">&copy; <?= date('Y') ?> Todos os direitos reservados.</p>
    </div>
</footer>

<?php foreach ($scripts as $js): ?>
<script src="<?= e($js) ?>"></script>
<?php endforeach; ?>

</body>
</html>
<?php
}

/** Janela que amplia as fotos (usada no cardápio e na galeria). */
function pub_visualizador(): void
{
    ?>

<!-- ================= VISUALIZADOR DE FOTOS ================= -->
<div class="viewer" id="viewer" aria-hidden="true" role="dialog" aria-modal="true">

    <button class="viewer-close" id="viewerClose" aria-label="Fechar">
        <i class="fas fa-xmark"></i>
    </button>

    <button class="viewer-arrow viewer-prev" id="viewerPrev" aria-label="Anterior">
        <i class="fas fa-chevron-left"></i>
    </button>

    <figure class="viewer-figure">
        <img src="" alt="" id="viewerImg">
        <figcaption id="viewerCaption"></figcaption>
        <span class="viewer-counter" id="viewerCounter"></span>
    </figure>

    <button class="viewer-arrow viewer-next" id="viewerNext" aria-label="Próxima">
        <i class="fas fa-chevron-right"></i>
    </button>

</div>
<?php
}

/** Faixa de título das páginas internas (foto + título + selos). */
function pub_topo(string $foto, string $altFoto, string $trilha, string $subtitulo, string $titulo, string $texto, array $selos = [], string $botaoHtml = ''): void
{
    ?>
<!-- ================= FAIXA DE TÍTULO ================= -->
<section class="pagina-topo">

    <div class="topo-overlay"></div>

    <img src="<?= e($foto) ?>" alt="<?= e($altFoto) ?>" class="topo-bg">

    <div class="container topo-content">

        <div class="breadcrumb">
            <a href="index.php">Início</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= e($trilha) ?></span>
        </div>

        <span class="section-subtitle"><?= e($subtitulo) ?></span>

        <h1 class="titulo"><?= e($titulo) ?></h1>

        <p><?= e($texto) ?></p>

<?php if ($selos): ?>
        <div class="topo-badges">
<?php foreach ($selos as [$icone, $rotulo]): ?>
            <span><i class="fas <?= e($icone) ?>"></i> <?= e($rotulo) ?></span>
<?php endforeach; ?>
        </div>
<?php endif; ?>
<?= $botaoHtml ?>

    </div>

</section>

<?php
}

/** Chamada final ("Bora comer?") com botão para o cardápio. */
function pub_chamada(string $titulo): void
{
    ?>
<!-- ================= CHAMADA ================= -->
<section class="call">

    <h2><?= e($titulo) ?></h2>

    <p>Peça agora e receba o verdadeiro sabor do churrasco na brasa
    direto na sua casa, ou venha nos visitar!</p>

    <a href="cardapio.php" class="btn btn-primary">VER CARDÁPIO</a>

</section>

<?php
}

/** Se a conexão com o banco falhar, páginas que dependem dele mostram isto. */
function pub_indisponivel(): void
{
    pub_inicio('Voltamos já - ' . cfg('site_nome'), '');
    ?>
<section class="pagina-topo" style="min-height:70vh">
    <div class="container topo-content">
        <h1 class="titulo">VOLTAMOS JÁ</h1>
        <p>Esta página está temporariamente indisponível. Tente novamente em alguns minutos
           ou chame a gente no WhatsApp.</p>
        <a href="<?= e(wa_link()) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-whatsapp"></i> CHAMAR NO WHATSAPP
        </a>
    </div>
</section>
<?php
    pub_fim();
}
