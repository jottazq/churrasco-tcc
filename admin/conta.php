<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$eu = exigir_login();

$erros = [];

if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');

    if ($acao === 'dados') {
        $nome = cortar(post('nome'), 80);
        if ($nome === '') {
            $erros[] = 'Informe seu nome.';
        } else {
            db_exec('UPDATE admins SET nome = ? WHERE id = ?', [$nome, (int) $eu['id']]);
            aviso('ok', 'Nome atualizado.');
            redirecionar('conta.php');
        }
    }

    if ($acao === 'senha') {
        $atualSenha = (string) ($_POST['senha_atual'] ?? '');
        $nova  = (string) ($_POST['nova_senha'] ?? '');
        $nova2 = (string) ($_POST['nova_senha2'] ?? '');

        $linha = db_one('SELECT senha_hash FROM admins WHERE id = ?', [(int) $eu['id']]);
        if (!$linha || !password_verify($atualSenha, $linha['senha_hash'])) {
            $erros[] = 'A senha atual não confere.';
        } elseif ($nova !== $nova2) {
            $erros[] = 'As duas senhas novas não são iguais.';
        } elseif (($erroSenha = senha_erro($nova, $eu['usuario'])) !== null) {
            $erros[] = $erroSenha;
        } else {
            db_exec('UPDATE admins SET senha_hash = ? WHERE id = ?', [password_hash($nova, PASSWORD_DEFAULT), (int) $eu['id']]);
            session_regenerate_id(true);   // renova a sessão após trocar a senha
            aviso('ok', 'Senha alterada com sucesso.');
            redirecionar('conta.php');
        }
    }
}

painel_abrir('Minha conta', 'conta');
?>

<?php foreach ($erros as $m): ?>
    <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($m) ?></div></div>
<?php endforeach; ?>

<form method="post" class="cartao">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="dados">
    <h2><i class="fas fa-id-card"></i> Meus dados</h2>
    <p class="ajuda">Seu usuário de login é <strong>@<?= e($eu['usuario']) ?></strong>.</p>
    <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" maxlength="80" value="<?= e($eu['nome']) ?>" required>
    </div>
    <button class="btn btn-primario" type="submit"><i class="fas fa-floppy-disk"></i> Salvar</button>
</form>

<form method="post" class="cartao" autocomplete="off">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="senha">
    <h2><i class="fas fa-key"></i> Trocar senha</h2>
    <p class="ajuda">Use uma senha que só você conheça: mínimo 8 caracteres.</p>

    <div class="campo">
        <label for="senha_atual">Senha atual</label>
        <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
    </div>
    <div class="grade-2">
        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="campo">
            <label for="nova_senha2">Repita a nova senha</label>
            <input type="password" id="nova_senha2" name="nova_senha2" minlength="8" autocomplete="new-password" required>
        </div>
    </div>
    <button class="btn btn-primario" type="submit"><i class="fas fa-key"></i> Alterar senha</button>
</form>

<?php painel_fechar();
