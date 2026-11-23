<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

$id = get_int('id');
$item = $id > 0 ? db_one('SELECT * FROM itens WHERE id = ?', [$id]) : null;
if ($id > 0 && !$item) {
    aviso('erro', 'Esse item não foi encontrado.');
    redirecionar('itens.php');
}
$editando = $item !== null;

$categorias = db_all('SELECT id, nome FROM categorias ORDER BY ordem, id');
if (!$categorias) {
    aviso('info', 'Crie primeiro uma categoria para poder cadastrar itens.');
    redirecionar('categorias.php');
}

$dados = $item ?? [
    'categoria_id' => get_int('cat', (int) $categorias[0]['id']),
    'nome' => '', 'descricao' => '', 'preco' => '', 'foto' => null,
    'destaque' => 0, 'esgotado' => 0, 'ativo' => 1,
];
$erros = [];

if (eh_post()) {
    csrf_exigir();

    // excluir
    if ($editando && post('acao') === 'excluir') {
        db_exec('DELETE FROM itens WHERE id = ?', [$id]);
        foto_apagar($item['foto']);
        aviso('ok', '"' . $item['nome'] . '" foi excluído.');
        redirecionar('itens.php');
    }

    $dados['nome']         = cortar(post('nome'), 120);
    $dados['descricao']    = cortar(post('descricao'), 400);
    $dados['categoria_id'] = post_int('categoria_id');
    $precoTxt              = post('preco');
    $dados['destaque']     = post_bool('destaque');
    $dados['esgotado']     = post_bool('esgotado');
    $dados['ativo']        = post_bool('ativo');
    $removerFoto           = post_bool('remover_foto');

    if ($dados['nome'] === '') {
        $erros[] = 'Informe o nome do item.';
    }
    if (!db_one('SELECT id FROM categorias WHERE id = ?', [$dados['categoria_id']])) {
        $erros[] = 'Escolha uma categoria.';
    }
    $preco = preco_ler($precoTxt);
    if ($preco === null) {
        $erros[] = 'Preço inválido. Digite algo como 26,00.';
    }
    $dados['preco'] = $precoTxt;

    // foto nova?
    $novaFoto = null;
    if (!$erros) {
        $up = upload_foto($_FILES['foto'] ?? [], 'item', 1400);
        if ($up['ok']) {
            $novaFoto = $up['caminho'];
        } elseif (!$up['vazio']) {
            $erros[] = $up['erro'];
        }
    }

    if (!$erros) {
        $agora = date('Y-m-d H:i:s');
        $fotoFinal = $editando ? $item['foto'] : null;
        if ($novaFoto !== null) {
            $fotoFinal = $novaFoto;
        } elseif ($removerFoto) {
            $fotoFinal = null;
        }

        if ($editando) {
            // se mudou de categoria, vai para o fim da nova lista
            $ordem = (int) $item['ordem'];
            if ((int) $item['categoria_id'] !== $dados['categoria_id']) {
                $ordem = db_proxima_ordem('itens', 'categoria_id', $dados['categoria_id']);
            }
            db_exec(
                'UPDATE itens SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, foto = ?, destaque = ?, esgotado = ?, ativo = ?, ordem = ?, atualizado_em = ? WHERE id = ?',
                [$dados['categoria_id'], $dados['nome'], $dados['descricao'], $preco, $fotoFinal, $dados['destaque'], $dados['esgotado'], $dados['ativo'], $ordem, $agora, $id]
            );
            if ($fotoFinal !== $item['foto']) {
                foto_apagar($item['foto']);
            }
            aviso('ok', '"' . $dados['nome'] . '" foi atualizado.');
        } else {
            db_exec(
                'INSERT INTO itens (categoria_id, nome, descricao, preco, foto, destaque, esgotado, ativo, ordem, atualizado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$dados['categoria_id'], $dados['nome'], $dados['descricao'], $preco, $fotoFinal, $dados['destaque'], $dados['esgotado'], $dados['ativo'],
                 db_proxima_ordem('itens', 'categoria_id', $dados['categoria_id']), $agora]
            );
            $id = db_id();
            aviso('ok', '"' . $dados['nome'] . '" foi adicionado ao cardápio.');
        }
        redirecionar('itens.php?cat=' . $dados['categoria_id'] . '#item-' . $id);
    }

    // deu erro: mantém a foto atual na tela
    $dados['foto'] = $editando ? $item['foto'] : null;
}

painel_abrir($editando ? 'Editar item' : 'Novo item', 'itens');

$precoCampo = $dados['preco'] === '' ? '' : (is_numeric(str_replace(',', '.', (string) $dados['preco']))
    ? number_format((float) str_replace(',', '.', (string) $dados['preco']), 2, ',', '.') : (string) $dados['preco']);
?>

<?php foreach ($erros as $m): ?>
    <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($m) ?></div></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_campo() ?>

    <div class="cartao">
        <h2><i class="fas fa-utensils"></i> Dados do item</h2>
        <p class="ajuda">O que o cliente vê no cardápio.</p>

        <div class="campo">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" maxlength="120" value="<?= e($dados['nome']) ?>" required>
        </div>

        <div class="grade-2">
            <div class="campo">
                <label for="categoria_id">Categoria *</label>
                <select id="categoria_id" name="categoria_id" required>
<?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"<?= (int) $dados['categoria_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['nome']) ?></option>
<?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="preco">Preço *</label>
                <div class="entrada-preco">
                    <span>R$</span>
                    <input type="text" id="preco" name="preco" inputmode="decimal" value="<?= e($precoCampo) ?>" placeholder="0,00" data-preco required>
                </div>
            </div>
        </div>

        <div class="campo">
            <label for="descricao">Descrição <span style="font-weight:400;color:var(--mudo)">(opcional)</span></label>
            <textarea id="descricao" name="descricao" maxlength="400" style="min-height:90px"><?= e($dados['descricao']) ?></textarea>
            <span class="dica">Ex.: o que vem no prato ou o que acompanha. Máximo 400 caracteres.</span>
        </div>
    </div>

    <div class="cartao">
        <h2><i class="fas fa-camera"></i> Foto</h2>
        <p class="ajuda">JPG, PNG ou WEBP. A foto é reduzida e otimizada automaticamente.</p>

        <div class="envio-foto">
            <div class="previa" id="previa">
<?php if (!empty($dados['foto'])): ?>
                <img src="<?= e(foto_src($dados['foto'])) ?>" alt="Foto atual">
<?php else: ?>
                <i class="fas fa-camera"></i>
<?php endif; ?>
            </div>
            <div style="flex:1;min-width:220px">
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-previa="#previa">
<?php if (!empty($dados['foto'])): ?>
                <label class="interruptor" style="margin:12px 0 0">
                    <input type="checkbox" name="remover_foto" value="1">
                    <span class="trilho"></span>
                    <span><strong>Remover a foto atual</strong><small>O item fica sem foto no site.</small></span>
                </label>
<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="cartao">
        <h2><i class="fas fa-sliders"></i> Como aparece no site</h2>
        <p class="ajuda">&nbsp;</p>

        <label class="interruptor">
            <input type="checkbox" name="ativo" value="1"<?= $dados['ativo'] ? ' checked' : '' ?>>
            <span class="trilho"></span>
            <span><strong>Visível no site</strong><small>Desligue para esconder sem excluir.</small></span>
        </label>

        <label class="interruptor">
            <input type="checkbox" name="esgotado" value="1"<?= $dados['esgotado'] ? ' checked' : '' ?>>
            <span class="trilho"></span>
            <span><strong>Esgotado</strong><small>Aparece com o aviso "Esgotado" no lugar do preço.</small></span>
        </label>

        <label class="interruptor">
            <input type="checkbox" name="destaque" value="1"<?= $dados['destaque'] ? ' checked' : '' ?>>
            <span class="trilho"></span>
            <span><strong>Mais pedido (destaque)</strong><small>Aparece maior, com a etiqueta "Mais pedido".</small></span>
        </label>
    </div>

    <div class="barra-salvar">
        <a class="btn" href="itens.php<?= $dados['categoria_id'] ? '?cat=' . (int) $dados['categoria_id'] : '' ?>">Cancelar</a>
        <button class="btn btn-primario" type="submit"><i class="fas fa-floppy-disk"></i> Salvar</button>
    </div>
</form>

<?php if ($editando): ?>
<form method="post" style="margin-top:10px">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="excluir">
    <button class="btn btn-perigo btn-pequeno" type="submit" data-confirmar="Excluir este item do cardápio? Isso não pode ser desfeito."><i class="fas fa-trash"></i> Excluir este item</button>
</form>
<?php endif; ?>

<?php painel_fechar();
