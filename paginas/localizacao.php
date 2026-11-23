<?php
declare(strict_types=1);

// Código da página. A entrada pública é o arquivo de mesmo nome na raiz do site.
defined('ENTRADA_PAGINA') || exit;

require_once __DIR__ . '/../includes/layout.php';

pub_inicio(
    'Localização - ' . cfg('site_nome'),
    'localizacao',
    ['styles/style-localizacao.css'],
    'Veja onde fica o ' . cfg('site_nome') . ', o horário de funcionamento e como chegar.'
);

$selos = [];
if (cfg('bairro_destaque') !== '') {
    $selos[] = ['fa-location-dot', cfg('bairro_destaque')];
}
$selos[] = ['fa-clock', horarios_resumo()];

pub_topo(
    cfg('foto_fachada'),
    'Fachada ' . cfg('site_nome'),
    'Localização',
    'VENHA NOS VISITAR',
    'ONDE ESTAMOS',
    'Venha conhecer o ' . cfg('site_nome') . ' e provar o verdadeiro sabor do churrasco na brasa.',
    $selos
);
?>
<!-- ================= INFORMAÇÕES ================= -->

<section class="pagina-localizacao">

    <div class="container">

        <div class="info-grid">

            <div class="info-card">

                <div class="icon-badge"><i class="fas fa-location-dot"></i></div>

                <div>
                    <h2>Endereço</h2>
                    <p><?= nl2br(e(cfg('endereco'))) ?></p>
                </div>

            </div>

            <div class="info-card">

                <div class="icon-badge"><i class="fas fa-clock"></i></div>

                <div>
                    <h2>Horário de Funcionamento</h2>
                    <ul class="horario-lista">
<?php foreach (horarios() as $k => $d): ?>
                        <li><span><?= e(DIAS_SEMANA[$k]) ?></span><span<?= $d['aberto'] ? '' : ' class="fechado"' ?>><?= e(horario_texto($d)) ?></span></li>
<?php endforeach; ?>
                    </ul>
                </div>

            </div>

        </div>

<?php if (cfg('mapa_embed_url') !== ''): ?>
        <!-- ================= MAPA ================= -->

        <div class="mapa-card">

            <div class="categoria-header">
                <div class="icon-badge"><i class="fas fa-map"></i></div>
                <h2>Nossa Localização</h2>
            </div>

            <div class="mapa-wrap">
                <iframe
                    src="<?= e(cfg('mapa_embed_url')) ?>"
                    title="Mapa: <?= e(cfg('site_nome')) ?>"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen="">
                </iframe>
            </div>

        </div>
<?php endif; ?>

<?php if (cfg('link_maps') !== '' || cfg('link_waze') !== ''): ?>
        <!-- ================= BOTÕES ================= -->

        <div class="localizacao-botoes">

<?php if (cfg('link_maps') !== ''): ?>
            <a href="<?= e(cfg('link_maps')) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-map-location-dot"></i> Abrir no Google Maps
            </a>
<?php endif; ?>

<?php if (cfg('link_waze') !== ''): ?>
            <a href="<?= e(cfg('link_waze')) ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-car"></i> Abrir no Waze
            </a>
<?php endif; ?>

        </div>
<?php endif; ?>

    </div>

</section>

<?php
pub_chamada('Bateu a Vontade?');
pub_fim();
