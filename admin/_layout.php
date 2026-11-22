<?php
/**
 * Estrutura comum das telas do painel (menu lateral, topo, avisos).
 * Cada página faz: require __DIR__ . '/_layout.php'; $admin = painel_abrir('Título', 'chave-do-menu');
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

if (!db_configurado()) {
    redirecionar('../install/index.php');
}

/** Foto do site vista de dentro de /admin. */
function foto_src(?string $caminho): string
{
    return $caminho ? '../' . ltrim($caminho, '/') : '';
}

/** Volta para a lista (mantendo filtros) depois de uma ação. Só aceita querystring "limpa". */
function volta_segura(string $arquivo): string
{
    $qs = post('volta');
    if ($qs !== '' && preg_match('/^[a-z0-9=&_-]{1,80}$/i', $qs)) {
        return $arquivo . '?' . $qs;
    }
    return $arquivo;
}

function querystring_atual(): string
{
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return preg_match('/^[a-z0-9=&_-]{1,80}$/i', $qs) ? $qs : '';
}

/** Exige login e desenha o topo da página. Devolve o admin logado. */
function painel_abrir(string $titulo, string $ativo): array
{
    $admin = exigir_login();
    header('Content-Type: text/html; charset=UTF-8');

    $menu = [
        'inicio'        => ['index.php',         'fa-gauge-high',    'Painel'],
        'itens'         => ['itens.php',         'fa-utensils',      'Cardápio'],
        'categorias'    => ['categorias.php',    'fa-layer-group',   'Categorias'],
        'galeria'       => ['galeria.php',       'fa-camera',        'Galeria'],
        'banners'       => ['banners.php',       'fa-images',        'Banner do início'],
        'configuracoes' => ['configuracoes.php', 'fa-gear',          'Configurações'],
        'usuarios'      => ['usuarios.php',      'fa-users',         'Usuários'],
        'conta'         => ['conta.php',         'fa-user-shield',   'Minha conta'],
    ];
    $v = (string) @filemtime(__DIR__ . '/assets/admin.css');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= e($titulo) ?> · Painel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?= e($v) ?>">
</head>
<body>
<div class="app">

    <aside class="lateral" id="lateral">
        <div class="marca">
            <h2>CHURRASCO</h2>
            <span>PAINEL DE CONTROLE</span>
        </div>

        <nav class="menu">
<?php foreach ($menu as $chave => [$href, $icone, $rotulo]): ?>
            <a href="<?= e($href) ?>"<?= $chave === $ativo ? ' class="ativo"' : '' ?>><i class="fas <?= e($icone) ?>"></i> <?= e($rotulo) ?></a>
<?php endforeach; ?>
            <a href="../index.php" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Ver o site</a>
        </nav>

        <div class="rodape-lateral">
            <strong><?= e($admin['nome']) ?></strong>
            @<?= e($admin['usuario']) ?>
            <form method="post" action="logout.php" style="margin-top:12px" data-livre>
                <?= csrf_campo() ?>
                <button class="btn btn-pequeno" type="submit" style="width:100%"><i class="fas fa-right-from-bracket"></i> Sair</button>
            </form>
        </div>
    </aside>

    <div class="sombra-menu"></div>

    <div class="principal">
        <header class="topo">
            <button class="btn btn-icone btn-menu" type="button" data-menu-abrir aria-label="Abrir menu"><i class="fas fa-bars"></i></button>
            <h1><?= e($titulo) ?></h1>
            <a class="btn btn-pequeno" href="../index.php" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Ver site</a>
        </header>

        <main class="conteudo">
<?php foreach (avisos_pegar() as $a): ?>
            <div class="aviso <?= e($a['tipo']) ?>" role="status">
                <i class="fas <?= $a['tipo'] === 'ok' ? 'fa-circle-check' : ($a['tipo'] === 'erro' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?>"></i>
                <div><?= e($a['texto']) ?></div>
            </div>
<?php endforeach; ?>
<?php
    return $admin;
}

function painel_fechar(): void
{
    ?>
        </main>
    </div>
</div>
<script src="assets/admin.js"></script>
</body>
</html>
<?php
}
