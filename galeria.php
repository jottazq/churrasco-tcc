<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

try {
    $fotos = db_all('SELECT arquivo, legenda FROM galeria_fotos WHERE ativa = 1 ORDER BY ordem, id');
} catch (Throwable $e) {
    error_log('[churrasco] galeria indisponível: ' . $e->getMessage());
    pub_indisponivel();
    exit;
}

pub_inicio(
    'Galeria - ' . cfg('site_nome'),
    'galeria',
    ['styles/style-galeria.css', 'styles/viewer.css'],
    'Conheça o ambiente do ' . cfg('site_nome') . '.'
);

pub_topo(
    $fotos ? $fotos[0]['arquivo'] : 'img/churrasco-misto.jpg',
    'Ambiente do ' . cfg('site_nome'),
    'Galeria',
    'NOSSO ESPAÇO',
    'AMBIENTE',
    'Conheça o nosso espaço, pensado para você aproveitar um churrasco de verdade com conforto e bom atendimento.',
    [['fa-people-roof', 'Ambiente familiar'], ['fa-heart', 'Atendimento acolhedor'], ['fa-location-dot', 'Venha nos visitar']]
);
?>
<!-- ================= GALERIA DO AMBIENTE ================= -->
<section class="pagina-galeria">

    <div class="container">

<?php if (!$fotos): ?>
        <p style="text-align:center;color:#aaa;padding:30px 0 60px">
            <i class="fas fa-camera" style="display:block;font-size:2rem;color:#444;margin-bottom:12px"></i>
            Em breve, fotos do nosso espaço por aqui.
        </p>
<?php else: ?>
        <div class="galeria">
<?php foreach ($fotos as $f):
        $legenda = $f['legenda'] !== '' ? $f['legenda'] : 'Nosso espaço';
?>
            <figure class="foto" data-viewer data-src="<?= e($f['arquivo']) ?>" data-caption="<?= e($f['legenda']) ?>" tabindex="0" role="button">
                <img src="<?= e($f['arquivo']) ?>" alt="<?= e($legenda) ?>" loading="lazy">
<?php if ($f['legenda'] !== ''): ?>
                <figcaption class="foto-legenda"><span><?= e($f['legenda']) ?></span><i class="fas fa-expand"></i></figcaption>
<?php endif; ?>
            </figure>
<?php endforeach; ?>
        </div>
<?php endif; ?>

    </div>

</section>

<?php
pub_chamada('Venha Conhecer!');
pub_visualizador();
pub_fim(['scripts/script.js', 'scripts/viewer.js']);
