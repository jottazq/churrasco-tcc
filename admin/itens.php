<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$admin = exigir_login();

/* ---------------------------------------------------------
   Ações rápidas (preço, ocultar, esgotado, ordem, excluir)
--------------------------------------------------------- */
if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');
    $id   = post_int('id');
    $item = $id > 0 ? db_one('SELECT * FROM itens WHERE id = ?', [$id]) : null;
    $destino = volta_segura('itens.php');

    if (!$item) {
        aviso('erro', 'Esse item não foi encontrado (talvez já tenha sido excluído).');
        redirecionar($destino);
    }
    $ancora = '#item-' . $id;
    $agora = date('Y-m-d H:i:s');

    switch ($acao) {
        case 'preco':
            $preco = preco_ler(post('preco'));
            if ($preco === null) {
                aviso('erro', 'Preço inválido para "' . $item['nome'] . '". Use algo como 26,00.');
            } else {
                db_exec('UPDATE itens SET preco = ?, atualizado_em = ? WHERE id = ?', [$preco, $agora, $id]);
                aviso('ok', '"' . $item['nome'] . '" agora custa ' . preco_br($preco) . '.');
            }
            break;

        case 'visivel':
            $novo = $item['ativo'] ? 0 : 1;
            db_exec('UPDATE itens SET ativo = ?, atualizado_em = ? WHERE id = ?', [$novo, $agora, $id]);
            aviso('ok', '"' . $item['nome'] . '" ' . ($novo ? 'voltou a aparecer no site.' : 'foi escondido do site.'));
            break;

        case 'esgotado':
            $novo = $item['esgotado'] ? 0 : 1;
            db_exec('UPDATE itens SET esgotado = ?, atualizado_em = ? WHERE id = ?', [$novo, $agora, $id]);
            aviso('ok', '"' . $item['nome'] . '" ' . ($novo ? 'está marcado como esgotado.' : 'voltou a estar disponível.'));
            break;

        case 'subir':
        case 'descer':
            db_mover('itens', 'categoria_id', (int) $item['categoria_id'], $id, $acao);
            break;

        case 'excluir':
            db_exec('DELETE FROM itens WHERE id = ?', [$id]);
            foto_apagar($item['foto']);
            aviso('ok', '"' . $item['nome'] . '" foi excluído.');
            $ancora = '';
            break;
    }
    redirecionar($destino . $ancora);
}

/* ---------------------------------------------------------
   Listagem
--------------------------------------------------------- */
$filtroSemFoto = ($_GET['filtro'] ?? '') === 'sem-foto';
$catId = get_int('cat');

$categorias = db_all('SELECT * FROM categorias ORDER BY ordem, id');
$contagem = [];
foreach (db_all('SELECT categoria_id, COUNT(*) AS n FROM itens GROUP BY categoria_id') as $r) {
    $contagem[(int) $r['categoria_id']] = (int) $r['n'];
}
$totalSemFoto = (int) db_val("SELECT COUNT(*) FROM itens WHERE foto IS NULL OR foto = ''");

$sql = 'SELECT * FROM itens';
$onde = [];
$params = [];
if ($catId > 0) { $onde[] = 'categoria_id = ?'; $params[] = $catId; }
if ($filtroSemFoto) { $onde[] = "(foto IS NULL OR foto = '')"; }
if ($onde) { $sql .= ' WHERE ' . implode(' AND ', $onde); }
$sql .= ' ORDER BY ordem, id';

$porCategoria = [];
foreach (db_all($sql, $params) as $it) {
    $porCategoria[(int) $it['categoria_id']][] = $it;
}

painel_abrir('Cardápio', 'itens');
$qs = querystring_atual();
?>

<div class="cabeca-pagina">
    <p>Mude preços direto na lista e toque em <strong>Salvar</strong>. Use os botões para esconder, marcar esgotado ou reordenar.</p>
    <a class="btn btn-primario" href="item.php<?= $catId ? '?cat=' . $catId : '' ?>"><i class="fas fa-plus"></i> Novo item</a>
</div>

<div class="abas">
    <a href="itens.php" class="<?= (!$catId && !$filtroSemFoto) ? 'ativa' : '' ?>">Todos<small><?= array_sum($contagem) ?></small></a>
<?php foreach ($categorias as $c): ?>
    <a href="itens.php?cat=<?= (int) $c['id'] ?>" class="<?= $catId === (int) $c['id'] ? 'ativa' : '' ?>"><?= e($c['nome_menu']) ?><small><?= $contagem[(int) $c['id']] ?? 0 ?></small></a>
<?php endforeach; ?>
    <a href="itens.php?filtro=sem-foto" class="<?= $filtroSemFoto ? 'ativa' : '' ?>"><i class="fas fa-camera"></i> Sem foto<small><?= $totalSemFoto ?></small></a>
</div>

<?php if (!$porCategoria): ?>
    <div class="vazio">
        <i class="fas fa-utensils"></i>
        <?= $filtroSemFoto ? 'Ótimo! Todos os itens já têm foto.' : 'Nenhum item aqui ainda.' ?>
    </div>
<?php endif; ?>

<?php foreach ($categorias as $cat):
    $lista = $porCategoria[(int) $cat['id']] ?? [];
    if (!$lista) { continue; }
?>
    <div class="grupo-titulo">
        <span class="icone"><i class="fas <?= e($cat['icone']) ?>"></i></span>
        <div>
            <h2><?= e($cat['nome']) ?><?= $cat['ativa'] ? '' : ' <span class="chip off">Categoria oculta</span>' ?></h2>
            <small><?= count($lista) ?> item(ns)</small>
        </div>
        <a class="btn btn-pequeno" href="item.php?cat=<?= (int) $cat['id'] ?>"><i class="fas fa-plus"></i> Adicionar aqui</a>
    </div>

<?php foreach ($lista as $i => $it):
        $oculto = !(int) $it['ativo'];
        $semFoto = empty($it['foto']);
?>
    <div class="linha<?= $oculto ? ' oculto' : '' ?>" id="item-<?= (int) $it['id'] ?>">

        <div class="miniatura<?= $semFoto ? ' sem' : '' ?>">
<?php if ($semFoto): ?>
            <i class="fas fa-camera"></i>
<?php else: ?>
            <img src="<?= e(foto_src($it['foto'])) ?>" alt="" loading="lazy">
<?php endif; ?>
        </div>

        <div class="dados">
            <strong><?= e($it['nome']) ?></strong>
            <div class="chips">
<?php if ($it['destaque']): ?><span class="chip destaque"><i class="fas fa-fire"></i> Mais pedido</span><?php endif; ?>
<?php if ($it['esgotado']): ?><span class="chip alerta">Esgotado</span><?php endif; ?>
<?php if ($oculto): ?><span class="chip off">Oculto no site</span><?php endif; ?>
<?php if ($semFoto): ?><span class="chip alerta"><i class="fas fa-camera"></i> Sem foto</span><?php endif; ?>
            </div>
        </div>

        <form class="preco-rapido" method="post" data-livre>
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="preco">
            <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
            <input type="hidden" name="volta" value="<?= e($qs) ?>">
            <div class="entrada-preco">
                <span>R$</span>
                <input type="text" name="preco" inputmode="decimal" value="<?= e(number_format((float) $it['preco'], 2, ',', '.')) ?>"
                       data-preco aria-label="Preço de <?= e($it['nome']) ?>">
            </div>
            <button class="btn btn-icone" type="submit" title="Salvar preço" aria-label="Salvar preço de <?= e($it['nome']) ?>"><i class="fas fa-check"></i></button>
        </form>

        <div class="acoes">
            <form method="post" data-livre>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                <input type="hidden" name="volta" value="<?= e($qs) ?>">
                <button class="btn btn-icone" type="submit" name="acao" value="subir" title="Subir" aria-label="Subir"<?= $i === 0 ? ' disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
                <button class="btn btn-icone" type="submit" name="acao" value="descer" title="Descer" aria-label="Descer"<?= $i === count($lista) - 1 ? ' disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
                <button class="btn btn-icone<?= $it['esgotado'] ? ' on' : '' ?>" type="submit" name="acao" value="esgotado" title="<?= $it['esgotado'] ? 'Marcar como disponível' : 'Marcar como esgotado' ?>" aria-label="Alternar esgotado"><i class="fas fa-ban"></i></button>
                <button class="btn btn-icone<?= $oculto ? ' on' : '' ?>" type="submit" name="acao" value="visivel" title="<?= $oculto ? 'Mostrar no site' : 'Esconder do site' ?>" aria-label="Alternar visibilidade"><i class="fas <?= $oculto ? 'fa-eye-slash' : 'fa-eye' ?>"></i></button>
                <a class="btn btn-pequeno" style="height:38px" href="item.php?id=<?= (int) $it['id'] ?>" title="Editar nome, foto e descrição" aria-label="Editar <?= e($it['nome']) ?>"><i class="fas fa-pen"></i> Editar</a>
                <button class="btn btn-icone btn-perigo" type="submit" name="acao" value="excluir" title="Excluir" aria-label="Excluir <?= e($it['nome']) ?>"
                        data-confirmar="Excluir &quot;<?= e($it['nome']) ?>&quot; do cardápio? Isso não pode ser desfeito."><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php endforeach; ?>

<?php painel_fechar();
