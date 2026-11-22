<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

pub_inicio(
    'Sobre Nós - ' . cfg('site_nome'),
    'sobre',
    ['styles/style-sobre-nos.css'],
    'Conheça a história, a missão e os valores do ' . cfg('site_nome') . '.'
);

pub_topo(
    cfg('foto_fachada'),
    'Fachada ' . cfg('site_nome'),
    'Sobre Nós',
    'CONHEÇA NOSSA HISTÓRIA',
    'SOBRE NÓS',
    'Tradição, qualidade e sabor em cada porção servida com dedicação e carinho pela nossa equipe.',
    [['fa-fire', 'Na brasa'], ['fa-users', 'Ambiente familiar'], ['fa-star', 'Qualidade garantida']]
);
?>
<!-- ================= NOSSA HISTÓRIA ================= -->

<section class="pagina-sobre">

    <div class="container">

        <div class="historia-card">

            <div class="icon-badge"><i class="fas fa-book-open"></i></div>

            <div>
                <h2>Nossa História</h2>
                <?= paragrafos_html(cfg('sobre_historia')) ?>
            </div>

        </div>

        <!-- ================= MISSÃO / VISÃO / VALORES ================= -->

        <div class="missao-grid">

            <div class="mv-card">
                <div class="icon-badge"><i class="fas fa-bullseye"></i></div>
                <h3>Missão</h3>
                <p><?= nl2br(e(cfg('sobre_missao'))) ?></p>
            </div>

            <div class="mv-card">
                <div class="icon-badge"><i class="fas fa-eye"></i></div>
                <h3>Visão</h3>
                <p><?= nl2br(e(cfg('sobre_visao'))) ?></p>
            </div>

            <div class="mv-card">
                <div class="icon-badge"><i class="fas fa-heart"></i></div>
                <h3>Valores</h3>
                <p><?= nl2br(e(cfg('sobre_valores'))) ?></p>
            </div>

        </div>

        <!-- ================= DIFERENCIAIS ================= -->

        <div class="diferenciais-card">

            <div class="categoria-header">
                <div class="icon-badge"><i class="fas fa-medal"></i></div>
                <h2>Por que escolher o Baiano?</h2>
            </div>

            <ul class="diferenciais-lista">
                <li><i class="fas fa-drumstick-bite"></i> Carnes selecionadas</li>
                <li><i class="fas fa-fire"></i> Churrasco preparado na brasa</li>
                <li><i class="fas fa-people-roof"></i> Ambiente familiar</li>
                <li><i class="fas fa-star"></i> Atendimento de qualidade</li>
                <li><i class="fas fa-sack-dollar"></i> Excelente custo-benefício</li>
            </ul>

        </div>

        <!-- ================= FOTO ================= -->

        <div class="foto-wrap">
            <img src="<?= e(cfg('foto_fachada')) ?>" alt="<?= e(cfg('site_nome')) ?>">
        </div>

    </div>

</section>

<?php
pub_chamada('Bateu a Vontade?');
pub_fim();
