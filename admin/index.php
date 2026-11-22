<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$admin = painel_abrir('Painel', 'inicio');

$totItens     = (int) db_val('SELECT COUNT(*) FROM itens');
$totCats      = (int) db_val('SELECT COUNT(*) FROM categorias');
$totGaleria   = (int) db_val('SELECT COUNT(*) FROM galeria_fotos WHERE ativa = 1');
$totBanners   = (int) db_val('SELECT COUNT(*) FROM banners WHERE ativo = 1');

$semFoto      = (int) db_val("SELECT COUNT(*) FROM itens WHERE foto IS NULL OR foto = ''");
$esgotados    = (int) db_val('SELECT COUNT(*) FROM itens WHERE esgotado = 1');
$ocultos      = (int) db_val('SELECT COUNT(*) FROM itens WHERE ativo = 0');
$semPreco     = (int) db_val('SELECT COUNT(*) FROM itens WHERE preco <= 0');

$ultimaEdicao = db_val('SELECT MAX(atualizado_em) FROM itens');

$pendencias = [];
if ($semFoto > 0)  { $pendencias[] = ['fa-camera', "$semFoto " . ($semFoto === 1 ? 'item está sem foto' : 'itens estão sem foto'), 'itens.php?filtro=sem-foto', 'Adicionar fotos']; }
if ($semPreco > 0) { $pendencias[] = ['fa-tag', "$semPreco " . ($semPreco === 1 ? 'item está com preço zerado' : 'itens estão com preço zerado'), 'itens.php', 'Conferir']; }
if ($totGaleria === 0) { $pendencias[] = ['fa-images', 'A galeria do ambiente ainda não tem fotos', 'galeria.php', 'Enviar fotos']; }
if (cfg('instagram_url') === '') { $pendencias[] = ['fa-link', 'O Instagram ainda não foi informado (o ícone fica escondido no site)', 'configuracoes.php#redes', 'Informar']; }
if (cfg('facebook_url') === '') { $pendencias[] = ['fa-link', 'O Facebook ainda não foi informado (o ícone fica escondido no site)', 'configuracoes.php#redes', 'Informar']; }
?>

<div class="cabeca-pagina">
    <div>
        <p>Olá, <strong><?= e(explode(' ', $admin['nome'])[0]) ?></strong>! Aqui você controla tudo o que aparece no site.</p>
    </div>
    <a class="btn btn-primario" href="item.php"><i class="fas fa-plus"></i> Novo item do cardápio</a>
</div>

<div class="numeros">
    <a class="numero" href="itens.php"><i class="fas fa-utensils"></i><strong><?= $totItens ?></strong><span>itens no cardápio</span></a>
    <a class="numero" href="categorias.php"><i class="fas fa-layer-group"></i><strong><?= $totCats ?></strong><span>categorias</span></a>
    <a class="numero" href="galeria.php"><i class="fas fa-camera"></i><strong><?= $totGaleria ?></strong><span>fotos na galeria</span></a>
    <a class="numero" href="banners.php"><i class="fas fa-images"></i><strong><?= $totBanners ?></strong><span>fotos no banner</span></a>
</div>

<div class="cartao">
    <h2><i class="fas fa-list-check"></i> O que precisa de atenção</h2>
<?php if (!$pendencias): ?>
    <p class="ajuda" style="margin:10px 0 0"><i class="fas fa-circle-check" style="color:var(--ok)"></i> Tudo em dia! Nenhuma pendência encontrada.</p>
<?php else: ?>
    <ul class="lista-atencao">
<?php foreach ($pendencias as [$icone, $texto, $link, $botao]): ?>
        <li>
            <span><i class="fas <?= e($icone) ?>"></i><?= e($texto) ?></span>
            <a class="btn btn-pequeno" href="<?= e($link) ?>"><?= e($botao) ?></a>
        </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
</div>

<div class="cartao">
    <h2><i class="fas fa-bolt"></i> Atalhos</h2>
    <p class="ajuda">As tarefas mais comuns.</p>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
        <a class="btn" href="itens.php"><i class="fas fa-tag"></i> Mudar preços</a>
        <a class="btn" href="item.php"><i class="fas fa-plus"></i> Novo item</a>
        <a class="btn" href="galeria.php"><i class="fas fa-camera"></i> Enviar fotos do ambiente</a>
        <a class="btn" href="configuracoes.php#horarios"><i class="fas fa-clock"></i> Horário de funcionamento</a>
        <a class="btn" href="configuracoes.php"><i class="fab fa-whatsapp"></i> WhatsApp e redes</a>
    </div>
</div>

<div class="cartao">
    <h2><i class="fas fa-circle-info"></i> Resumo</h2>
    <p class="ajuda" style="margin:8px 0 0">
        <?= $esgotados ?> item(ns) marcados como esgotado ·
        <?= $ocultos ?> item(ns) escondidos do site ·
        última alteração no cardápio: <?= e(data_br($ultimaEdicao)) ?>
    </p>
</div>

<?php painel_fechar();
