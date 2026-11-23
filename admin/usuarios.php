<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$eu = exigir_login();

$erros = [];
$form = ['nome' => '', 'usuario' => ''];

if (eh_post()) {
    csrf_exigir();
    $acao = post('acao');

    if ($acao === 'criar') {
        $form['nome']    = cortar(post('nome'), 80);
        $form['usuario'] = usuario_normalizar(post('usuario'));
        $senha = (string) ($_POST['senha'] ?? '');

        if ($form['nome'] === '') {
            $erros[] = 'Informe o nome.';
        }
        if (!usuario_valido($form['usuario'])) {
            $erros[] = 'Usuário inválido: use de 3 a 40 caracteres entre letras minúsculas, números, ponto, hífen ou sublinhado.';
        } elseif (db_one('SELECT id FROM admins WHERE usuario = ?', [$form['usuario']])) {
            $erros[] = 'Já existe alguém com esse usuário.';
        }
        if (($erroSenha = senha_erro($senha, $form['usuario'])) !== null) {
            $erros[] = $erroSenha;
        }

        if (!$erros) {
            db_exec(
                'INSERT INTO admins (usuario, nome, senha_hash, criado_em) VALUES (?, ?, ?, ?)',
                [$form['usuario'], $form['nome'], password_hash($senha, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]
            );
            aviso('ok', 'Usuário @' . $form['usuario'] . ' criado.');
            redirecionar('usuarios.php');
        }
    } else {
        $id = post_int('id');
        $alvo = $id > 0 ? db_one('SELECT * FROM admins WHERE id = ?', [$id]) : null;
        if (!$alvo) {
            aviso('erro', 'Usuário não encontrado.');
            redirecionar('usuarios.php');
        }

        if ($acao === 'senha') {
            $nova = (string) ($_POST['nova_senha'] ?? '');
            if (($erroSenha = senha_erro($nova, $alvo['usuario'])) !== null) {
                aviso('erro', $erroSenha);
            } else {
                db_exec('UPDATE admins SET senha_hash = ? WHERE id = ?', [password_hash($nova, PASSWORD_DEFAULT), $id]);
                aviso('ok', 'Senha de @' . $alvo['usuario'] . ' alterada.');
            }
        } elseif ($acao === 'excluir') {
            if ((int) $alvo['id'] === (int) $eu['id']) {
                aviso('erro', 'Você não pode excluir o próprio usuário.');
            } elseif ((int) db_val('SELECT COUNT(*) FROM admins') <= 1) {
                aviso('erro', 'É preciso manter pelo menos um usuário.');
            } else {
                db_exec('DELETE FROM admins WHERE id = ?', [$id]);
                aviso('ok', 'Usuário @' . $alvo['usuario'] . ' removido.');
            }
        }
        redirecionar('usuarios.php');
    }
}

$usuarios = db_all('SELECT id, usuario, nome, criado_em, ultimo_login FROM admins ORDER BY nome, id');
painel_abrir('Usuários', 'usuarios');
?>

<?php foreach ($erros as $m): ?>
    <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($m) ?></div></div>
<?php endforeach; ?>

<div class="cartao">
    <h2><i class="fas fa-users"></i> Quem pode entrar no painel</h2>
    <p class="ajuda">Todos os usuários têm acesso total. Dê acesso só a quem você confia.</p>

<?php foreach ($usuarios as $u): $sou = (int) $u['id'] === (int) $eu['id']; ?>
    <div class="usuario">
        <div class="quem">
            <strong><?= e($u['nome']) ?><?= $sou ? ' <span class="chip destaque">Você</span>' : '' ?></strong>
            <small>@<?= e($u['usuario']) ?> · último acesso: <?= e(data_br($u['ultimo_login'])) ?></small>
        </div>
        <form method="post" data-livre>
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <input type="password" name="nova_senha" placeholder="Nova senha" autocomplete="new-password" minlength="8" aria-label="Nova senha de <?= e($u['usuario']) ?>">
            <button class="btn btn-pequeno" type="submit" name="acao" value="senha"><i class="fas fa-key"></i> Trocar senha</button>
<?php if (!$sou): ?>
            <button class="btn btn-pequeno btn-perigo" type="submit" name="acao" value="excluir"
                    data-confirmar="Remover o acesso de @<?= e($u['usuario']) ?>?"><i class="fas fa-user-minus"></i> Remover</button>
<?php endif; ?>
        </form>
    </div>
<?php endforeach; ?>
</div>

<form method="post" class="cartao" autocomplete="off">
    <?= csrf_campo() ?>
    <input type="hidden" name="acao" value="criar">
    <h2><i class="fas fa-user-plus"></i> Adicionar usuário</h2>
    <p class="ajuda">Por exemplo, um funcionário que vai atualizar preços.</p>

    <div class="grade-2">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" maxlength="80" value="<?= e($form['nome']) ?>" required>
        </div>
        <div class="campo">
            <label for="usuario">Usuário (para o login)</label>
            <input type="text" id="usuario" name="usuario" maxlength="40" value="<?= e($form['usuario']) ?>" autocapitalize="none" required>
        </div>
    </div>
    <div class="campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required>
        <span class="dica">Mínimo 8 caracteres.</span>
    </div>
    <button class="btn btn-primario" type="submit"><i class="fas fa-plus"></i> Adicionar</button>
</form>

<?php painel_fechar();
