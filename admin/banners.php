<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

const BANNER_MAX_POR_ENVIO = 8;

if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');

    if ($acao === 'enviar') {
        $arquivos = array_slice(arquivos_multiplos('fotos'), 0, BANNER_MAX_POR_ENVIO);
        $enviadas = 0;
        $falhas = [];
        foreach ($arquivos as $arq) {
            $r = upload_foto($arq, 'banner', 1920);
            if ($r['ok']) {
                db_exec('INSERT INTO banners (arquivo, texto_alt, ordem, ativo) VALUES (?, ?, ?, 1)', [$r['caminho'], '', db_proxima_ordem('banners')]);
                $enviadas++;
            } elseif (!$r['vazio']) {
                $falhas[] = ($arq['name'] ?? 'foto') . ': ' . $r['erro'];
            }
        }
        if ($enviadas > 0) {
            aviso('ok', $enviadas === 1 ? '1 foto adicionada ao banner.' : $enviadas . ' fotos adicionadas ao banner.');
        } elseif (!$falhas) {
            aviso('info', 'Nenhuma foto foi escolhida.');
        }
        foreach ($falhas as $f) {
            aviso('erro', $f);
        }
        redirecionar('banners.php');
    }

    $id = post_int('id');
    $b = $id > 0 ? db_one('SELECT * FROM banners WHERE id = ?', [$id]) : null;
    if (!$b) {
        aviso('erro', 'Foto não encontrada.');
        redirecionar('banners.php');
    }

    switch ($acao) {
        case 'descricao':
            db_exec('UPDATE banners SET texto_alt = ? WHERE id = ?', [cortar(post('texto_alt'), 120), $id]);
            aviso('ok', 'Descrição salva.');
            break;
        case 'subir':
        case 'descer':
            db_mover('banners', null, null, $id, $acao);
            break;
        case 'visivel':
            $novo = $b['ativo'] ? 0 : 1;
            db_exec('UPDATE banners SET ativo = ? WHERE id = ?', [$novo, $id]);
            aviso('ok', $novo ? 'A foto voltou ao banner.' : 'A foto saiu do banner (continua guardada aqui).');
            break;
        case 'excluir':
            db_exec('DELETE FROM banners WHERE id = ?', [$id]);
            foto_apagar($b['arquivo']);
            aviso('ok', 'Foto excluída do banner.');
            break;
    }
    redirecionar('banners.php');
}

$banners = db_all('SELECT * FROM banners ORDER BY ordem, id');
$visiveis = count(array_filter($banners, fn($b) => (int) $b['ativo'] === 1));
painel_abrir('Banner da página inicial', 'banners');
?>

<div class="cartao">
    <h2><i class="fas fa-cloud-arrow-up"></i> Adicionar fotos ao banner</h2>
    <p class="ajuda">São as fotos grandes que passam sozinhas no topo da página inicial. Prefira fotos <strong>horizontais</strong> e bem iluminadas.</p>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="enviar">
        <div class="campo">
            <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required>
        </div>
        <button class="btn btn-primario" type="submit"><i class="fas fa-upload"></i> Enviar</button>
    </form>
</div>

<?php if ($visiveis === 0): ?>
    <div class="aviso info"><i class="fas fa-circle-info"></i><div>Nenhuma foto ativa: o site vai mostrar uma foto padrão do churrasco no banner.</div></div>
<?php endif; ?>

<?php if ($banners): ?>
<div class="fotos">
<?php foreach ($banners as $i => $b): ?>
    <div class="foto-card<?= $b['ativo'] ? '' : ' oculto' ?>">
        <div class="imagem">
            <img src="<?= e(foto_src($b['arquivo'])) ?>" alt="<?= e($b['texto_alt']) ?>" loading="lazy">
<?php if (!$b['ativo']): ?><span class="chip off">Fora do banner</span><?php endif; ?>
        </div>
        <div class="corpo">
            <form method="post" style="display:flex;gap:6px" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="descricao">
                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                <input type="text" name="texto_alt" maxlength="120" value="<?= e($b['texto_alt']) ?>" placeholder="Descrição da foto" aria-label="Descrição da foto">
                <button class="btn btn-icone" type="submit" aria-label="Salvar descrição" title="Salvar descrição"><i class="fas fa-check"></i></button>
            </form>
            <form method="post" class="rodape-card" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                <span style="display:flex;gap:6px">
                    <button class="btn btn-icone" type="submit" name="acao" value="subir" aria-label="Mover antes" title="Mover antes"<?= $i === 0 ? ' disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-icone" type="submit" name="acao" value="descer" aria-label="Mover depois" title="Mover depois"<?= $i === count($banners) - 1 ? ' disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
                </span>
                <span style="display:flex;gap:6px">
                    <button class="btn btn-icone<?= $b['ativo'] ? '' : ' on' ?>" type="submit" name="acao" value="visivel" aria-label="Mostrar ou esconder" title="<?= $b['ativo'] ? 'Tirar do banner' : 'Voltar ao banner' ?>"><i class="fas <?= $b['ativo'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                    <button class="btn btn-icone btn-perigo" type="submit" name="acao" value="excluir" aria-label="Excluir foto" title="Excluir"
                            data-confirmar="Excluir esta foto do banner?"><i class="fas fa-trash"></i></button>
                </span>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php else: ?>
    <div class="vazio"><i class="fas fa-images"></i> Nenhuma foto no banner ainda.</div>
<?php endif; ?>

<?php painel_fechar();
