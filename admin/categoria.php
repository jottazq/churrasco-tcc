<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

/** Ícones que o painel oferece (todos existem no Font Awesome gratuito usado no site). */
const ICONES_CATEGORIA = [
    'fa-fire'              => 'Brasa',
    'fa-drumstick-bite'    => 'Carnes',
    'fa-bowl-rice'         => 'Arroz',
    'fa-bowl-food'         => 'Pratos',
    'fa-utensils'          => 'Refeições',
    'fa-bacon'             => 'Porções',
    'fa-burger'            => 'Lanches',
    'fa-pizza-slice'       => 'Pizza',
    'fa-fish'              => 'Peixes',
    'fa-cheese'            => 'Queijos',
    'fa-seedling'          => 'Saladas',
    'fa-ice-cream'         => 'Sobremesas',
    'fa-cookie-bite'       => 'Doces',
    'fa-glass-water'       => 'Bebidas',
    'fa-bottle-water'      => 'Refrigerantes',
    'fa-beer-mug-empty'    => 'Cervejas',
    'fa-wine-glass'        => 'Vinhos',
    'fa-martini-glass-citrus' => 'Drinks',
    'fa-mug-hot'           => 'Café',
    'fa-star'              => 'Promoções',
    'fa-tag'               => 'Ofertas',
];

$id  = get_int('id');
$cat = $id > 0 ? db_one('SELECT * FROM categorias WHERE id = ?', [$id]) : null;
if ($id > 0 && !$cat) {
    aviso('erro', 'Categoria não encontrada.');
    redirecionar('categorias.php');
}
$editando = $cat !== null;

$dados = $cat ?? ['nome' => '', 'nome_menu' => '', 'descricao' => '', 'icone' => 'fa-utensils', 'estilo' => 'completo', 'ativa' => 1];
$erros = [];

if (eh_post()) {
    csrf_exigir();

    $dados['nome']      = cortar(post('nome'), 80);
    $dados['nome_menu'] = cortar(post('nome_menu'), 40);
    $dados['descricao'] = cortar(post('descricao'), 160);
    $dados['icone']     = post('icone');
    $dados['estilo']    = post('estilo') === 'simples' ? 'simples' : 'completo';
    $dados['ativa']     = post_bool('ativa');

    if ($dados['nome'] === '') {
        $erros[] = 'Informe o nome da categoria.';
    }
    if ($dados['nome_menu'] === '') {
        $dados['nome_menu'] = cortar($dados['nome'], 40);
    }
    // aceita só ícones da lista (ou o que já estava salvo)
    if (!isset(ICONES_CATEGORIA[$dados['icone']]) && !($editando && $dados['icone'] === $cat['icone'])) {
        $dados['icone'] = 'fa-utensils';
    }

    if (!$erros) {
        if ($editando) {
            db_exec(
                'UPDATE categorias SET nome = ?, nome_menu = ?, descricao = ?, icone = ?, estilo = ?, ativa = ? WHERE id = ?',
                [$dados['nome'], $dados['nome_menu'], $dados['descricao'], $dados['icone'], $dados['estilo'], $dados['ativa'], $id]
            );
            aviso('ok', 'Categoria "' . $dados['nome'] . '" atualizada.');
        } else {
            // endereço (âncora) único para o menu rápido do cardápio
            $base = slugify($dados['nome_menu']);
            $slug = $base;
            $n = 2;
            while (db_one('SELECT id FROM categorias WHERE slug = ?', [$slug])) {
                $slug = $base . '-' . $n++;
            }
            db_exec(
                'INSERT INTO categorias (slug, nome, nome_menu, descricao, icone, estilo, ordem, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$slug, $dados['nome'], $dados['nome_menu'], $dados['descricao'], $dados['icone'], $dados['estilo'],
                 db_proxima_ordem('categorias'), $dados['ativa']]
            );
            aviso('ok', 'Categoria "' . $dados['nome'] . '" criada. Agora é só adicionar os itens.');
        }
        redirecionar('categorias.php');
    }
}

painel_abrir($editando ? 'Editar categoria' : 'Nova categoria', 'categorias');
?>

<?php foreach ($erros as $m): ?>
    <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($m) ?></div></div>
<?php endforeach; ?>

<form method="post">
    <?= csrf_campo() ?>

    <div class="cartao">
        <h2><i class="fas fa-layer-group"></i> Dados da categoria</h2>
        <p class="ajuda">Como a seção aparece no cardápio.</p>

        <div class="campo">
            <label for="nome">Nome da seção *</label>
            <input type="text" id="nome" name="nome" maxlength="80" value="<?= e($dados['nome']) ?>" placeholder="Ex.: Churrasco para 5 Pessoas" required>
        </div>

        <div class="grade-2">
            <div class="campo">
                <label for="nome_menu">Nome curto (menu rápido)</label>
                <input type="text" id="nome_menu" name="nome_menu" maxlength="40" value="<?= e($dados['nome_menu']) ?>" placeholder="Ex.: Churrasco 5P">
                <span class="dica">Aparece nos botões no topo do cardápio. Se deixar vazio, usa o nome da seção.</span>
            </div>
            <div class="campo">
                <label for="descricao">Frase de apoio</label>
                <input type="text" id="descricao" name="descricao" maxlength="160" value="<?= e($dados['descricao']) ?>" placeholder="Ex.: Cortes preparados na brasa">
            </div>
        </div>

        <div class="campo">
            <label for="estilo">Layout dos itens</label>
            <select id="estilo" name="estilo">
                <option value="completo"<?= $dados['estilo'] === 'completo' ? ' selected' : '' ?>>Com foto grande (pratos, porções, acompanhamentos)</option>
                <option value="simples"<?= $dados['estilo'] === 'simples' ? ' selected' : '' ?>>Compacto (bebidas, cervejas, refrigerantes)</option>
            </select>
        </div>
    </div>

    <div class="cartao">
        <h2><i class="fas fa-icons"></i> Ícone</h2>
        <p class="ajuda">Aparece ao lado do nome da seção.</p>
        <div class="icones">
<?php
$lista = ICONES_CATEGORIA;
if (!isset($lista[$dados['icone']])) { $lista = [$dados['icone'] => 'Atual'] + $lista; }
foreach ($lista as $classe => $rotulo): ?>
            <label>
                <input type="radio" name="icone" value="<?= e($classe) ?>"<?= $dados['icone'] === $classe ? ' checked' : '' ?>>
                <span><i class="fas <?= e($classe) ?>"></i><?= e($rotulo) ?></span>
            </label>
<?php endforeach; ?>
        </div>
    </div>

    <div class="cartao">
        <label class="interruptor" style="margin:0">
            <input type="checkbox" name="ativa" value="1"<?= $dados['ativa'] ? ' checked' : '' ?>>
            <span class="trilho"></span>
            <span><strong>Visível no site</strong><small>Desligue para esconder a seção inteira sem apagar os itens.</small></span>
        </label>
    </div>

    <div class="barra-salvar">
        <a class="btn" href="categorias.php">Cancelar</a>
        <button class="btn btn-primario" type="submit"><i class="fas fa-floppy-disk"></i> Salvar</button>
    </div>
</form>

<?php painel_fechar();
