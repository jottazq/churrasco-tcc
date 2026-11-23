<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

const GALERIA_MAX_POR_ENVIO = 12;

if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');

    if ($acao === 'enviar') {
        $arquivos = array_slice(arquivos_multiplos('fotos'), 0, GALERIA_MAX_POR_ENVIO);
        $enviadas = 0;
        $falhas = [];
        foreach ($arquivos as $arq) {
            $r = upload_foto($arq, 'ambiente', 1600);
            if ($r['ok']) {
                db_exec(
                    'INSERT INTO galeria_fotos (arquivo, legenda, ordem, ativa) VALUES (?, ?, ?, 1)',
                    [$r['caminho'], '', db_proxima_ordem('galeria_fotos')]
                );
                $enviadas++;
            } elseif (!$r['vazio']) {
                $falhas[] = ($arq['name'] ?? 'foto') . ': ' . $r['erro'];
            }
        }
        if ($enviadas > 0) {
            aviso('ok', $enviadas === 1 ? '1 foto enviada. Escreva a legenda abaixo, se quiser.' : $enviadas . ' fotos enviadas. Escreva as legendas abaixo, se quiser.');
        } elseif (!$falhas) {
            aviso('info', 'Nenhuma foto foi escolhida.');
        }
        foreach ($falhas as $f) {
            aviso('erro', $f);
        }
        redirecionar('galeria.php');
    }

    $id = post_int('id');
    $foto = $id > 0 ? db_one('SELECT * FROM galeria_fotos WHERE id = ?', [$id]) : null;
    if (!$foto) {
        aviso('erro', 'Foto não encontrada.');
        redirecionar('galeria.php');
    }

    switch ($acao) {
        case 'legenda':
            db_exec('UPDATE galeria_fotos SET legenda = ? WHERE id = ?', [cortar(post('legenda'), 120), $id]);
            aviso('ok', 'Legenda salva.');
            break;
        case 'subir':
        case 'descer':
            db_mover('galeria_fotos', null, null, $id, $acao);
            break;
        case 'visivel':
            $novo = $foto['ativa'] ? 0 : 1;
            db_exec('UPDATE galeria_fotos SET ativa = ? WHERE id = ?', [$novo, $id]);
            aviso('ok', $novo ? 'A foto voltou a aparecer na galeria.' : 'A foto foi escondida da galeria.');
            break;
        case 'excluir':
            db_exec('DELETE FROM galeria_fotos WHERE id = ?', [$id]);
            foto_apagar($foto['arquivo']);
            aviso('ok', 'Foto excluída.');
            break;
    }
    redirecionar('galeria.php');
}

$fotos = db_all('SELECT * FROM galeria_fotos ORDER BY ordem, id');
painel_abrir('Galeria do ambiente', 'galeria');
?>

<div class="cartao">
    <h2><i class="fas fa-cloud-arrow-up"></i> Enviar fotos</h2>
    <p class="ajuda">Escolha uma ou várias fotos do seu espaço (até <?= GALERIA_MAX_POR_ENVIO ?> por vez). A primeira da lista aparece maior no site.</p>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="enviar">
        <div class="campo">
            <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required>
        </div>
        <button class="btn btn-primario" type="submit"><i class="fas fa-upload"></i> Enviar</button>
    </form>
</div>

<?php if (!$fotos): ?>
    <div class="vazio"><i class="fas fa-camera"></i> Ainda não há fotos. Envie as primeiras acima.</div>
<?php else: ?>
<div class="fotos">
<?php foreach ($fotos as $i => $f): ?>
    <div class="foto-card<?= $f['ativa'] ? '' : ' oculto' ?>">
        <div class="imagem">
            <img src="<?= e(foto_src($f['arquivo'])) ?>" alt="<?= e($f['legenda']) ?>" loading="lazy">
<?php if ($i === 0 && $f['ativa']): ?><span class="chip destaque">Destaque</span><?php elseif (!$f['ativa']): ?><span class="chip off">Oculta</span><?php endif; ?>
        </div>
        <div class="corpo">
            <form method="post" style="display:flex;gap:6px" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="legenda">
                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                <input type="text" name="legenda" maxlength="120" value="<?= e($f['legenda']) ?>" placeholder="Legenda" aria-label="Legenda da foto">
                <button class="btn btn-icone" type="submit" aria-label="Salvar legenda" title="Salvar legenda"><i class="fas fa-check"></i></button>
            </form>
            <form method="post" class="rodape-card" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                <span style="display:flex;gap:6px">
                    <button class="btn btn-icone" type="submit" name="acao" value="subir" aria-label="Mover antes" title="Mover antes"<?= $i === 0 ? ' disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-icone" type="submit" name="acao" value="descer" aria-label="Mover depois" title="Mover depois"<?= $i === count($fotos) - 1 ? ' disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
                </span>
                <span style="display:flex;gap:6px">
                    <button class="btn btn-icone<?= $f['ativa'] ? '' : ' on' ?>" type="submit" name="acao" value="visivel" aria-label="Mostrar ou esconder" title="<?= $f['ativa'] ? 'Esconder' : 'Mostrar' ?>"><i class="fas <?= $f['ativa'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                    <button class="btn btn-icone btn-perigo" type="submit" name="acao" value="excluir" aria-label="Excluir foto" title="Excluir"
                            data-confirmar="Excluir esta foto da galeria?"><i class="fas fa-trash"></i></button>
                </span>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php painel_fechar();
