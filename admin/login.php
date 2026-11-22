<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (!db_configurado()) {
    redirecionar('../install/index.php');
}

admin_cabecalhos();

// já está logado? vai direto para o painel
if (admin_atual()) {
    redirecionar('index.php');
}

$erro = '';
$usuarioDigitado = '';

if (eh_post()) {
    csrf_exigir();
    $usuarioDigitado = post('usuario');
    [$ok, $msg] = login_tentar($usuarioDigitado, (string) ($_POST['senha'] ?? ''));
    if ($ok) {
        redirecionar('index.php');
    }
    $erro = $msg;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Entrar · Painel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?= e((string) @filemtime(__DIR__ . '/assets/admin.css')) ?>">
</head>
<body>
<div class="login-pagina">
    <div class="login-caixa">

        <div class="marca">
            <h2>CHURRASCO</h2>
            <span>DO BAIANO SEM MISÉRIA</span>
        </div>

        <h1>Entrar no painel</h1>
        <p class="sub">Use o usuário e a senha de administrador.</p>

<?php if ($erro !== ''): ?>
        <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($erro) ?></div></div>
<?php endif; ?>

        <form method="post" autocomplete="on">
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" value="<?= e($usuarioDigitado) ?>"
                       autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required autofocus>
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" autocomplete="current-password" required>
            </div>

            <button class="btn btn-primario" type="submit"><i class="fas fa-right-to-bracket"></i> Entrar</button>
        </form>

        <a class="voltar" href="../index.php"><i class="fas fa-arrow-left"></i> Voltar ao site</a>
    </div>
</div>
<script src="assets/admin.js"></script>
</body>
</html>
