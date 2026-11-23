<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');
    $id   = post_int('id');
    $cat  = $id > 0 ? db_one('SELECT * FROM categorias WHERE id = ?', [$id]) : null;

    if (!$cat) {
        aviso('erro', 'Categoria não encontrada.');
        redirecionar('categorias.php');
    }

    switch ($acao) {
        case 'subir':
        case 'descer':
            db_mover('categorias', null, null, $id, $acao);
            break;

        case 'visivel':
            $novo = $cat['ativa'] ? 0 : 1;
            db_exec('UPDATE categorias SET ativa = ? WHERE id = ?', [$novo, $id]);
            aviso('ok', 'A categoria "' . $cat['nome'] . '" ' . ($novo ? 'voltou a aparecer no site.' : 'foi escondida do site (os itens continuam guardados).'));
            break;

        case 'excluir':
            $qtd = (int) db_val('SELECT COUNT(*) FROM itens WHERE categoria_id = ?', [$id]);
            if ($qtd > 0) {
                aviso('erro', 'Não dá para excluir "' . $cat['nome'] . '" porque ela ainda tem ' . $qtd . ' item(ns). Mova ou exclua os itens antes, ou apenas esconda a categoria.');
            } else {
                db_exec('DELETE FROM categorias WHERE id = ?', [$id]);
                aviso('ok', 'A categoria "' . $cat['nome'] . '" foi excluída.');
            }
            break;
    }
    redirecionar('categorias.php');
}

$cats = db_all('SELECT c.*, (SELECT COUNT(*) FROM itens i WHERE i.categoria_id = c.id) AS qtd FROM categorias c ORDER BY c.ordem, c.id');

painel_abrir('Categorias', 'categorias');
?>

<div class="cabeca-pagina">
    <p>As categorias organizam o cardápio (Churrasco, Bebidas, Cervejas...). A ordem aqui é a ordem no site.</p>
    <a class="btn btn-primario" href="categoria.php"><i class="fas fa-plus"></i> Nova categoria</a>
</div>

<?php if (!$cats): ?>
    <div class="vazio"><i class="fas fa-layer-group"></i> Nenhuma categoria ainda. Crie a primeira.</div>
<?php endif; ?>

<?php foreach ($cats as $i => $c): ?>
    <div class="linha<?= $c['ativa'] ? '' : ' oculto' ?>" style="grid-template-columns:56px minmax(0,1fr) auto">
        <div class="miniatura" style="color:var(--amarelo);font-size:1.3rem"><i class="fas <?= e($c['icone']) ?>"></i></div>

        <div class="dados">
            <strong><?= e($c['nome']) ?></strong>
            <small>Menu: <?= e($c['nome_menu']) ?> · <?= (int) $c['qtd'] ?> item(ns) · <?= $c['estilo'] === 'simples' ? 'layout compacto' : 'layout com foto grande' ?></small>
<?php if (!$c['ativa']): ?><div class="chips"><span class="chip off">Oculta no site</span></div><?php endif; ?>
        </div>

        <div class="acoes">
            <form method="post" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-icone" type="submit" name="acao" value="subir" aria-label="Subir" title="Subir"<?= $i === 0 ? ' disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
                <button class="btn btn-icone" type="submit" name="acao" value="descer" aria-label="Descer" title="Descer"<?= $i === count($cats) - 1 ? ' disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
                <button class="btn btn-icone<?= $c['ativa'] ? '' : ' on' ?>" type="submit" name="acao" value="visivel" aria-label="Mostrar ou esconder" title="<?= $c['ativa'] ? 'Esconder do site' : 'Mostrar no site' ?>"><i class="fas <?= $c['ativa'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                <a class="btn btn-icone" href="categoria.php?id=<?= (int) $c['id'] ?>" aria-label="Editar" title="Editar"><i class="fas fa-pen"></i></a>
                <button class="btn btn-icone btn-perigo" type="submit" name="acao" value="excluir" aria-label="Excluir" title="Excluir"
                        data-confirmar="Excluir a categoria &quot;<?= e($c['nome']) ?>&quot;?"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php painel_fechar();
