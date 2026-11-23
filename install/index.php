<?php
/**
 * INSTALADOR — rode UMA vez, logo depois de enviar os arquivos para a Hostinger.
 *
 *  Passo 1: informa os dados do banco MySQL (criado no hPanel) -> gera includes/config.php
 *  Passo 2: cria as tabelas, importa o cardápio atual e cria seu usuário administrador
 *
 * Depois de concluir, este arquivo tenta se apagar sozinho. Se não conseguir,
 * apague a pasta "install" pelo Gerenciador de Arquivos. (Mesmo sem apagar, ele
 * se recusa a rodar de novo enquanto existir um administrador.)
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$erros = [];
$concluido = false;
$apagou = false;
$arquivoConfig = RAIZ . '/includes/config.php';

/** Divide o schema.sql em comandos. */
function instalar_comandos(string $arquivo): array
{
    $sql = (string) file_get_contents($arquivo);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $cmds = array_filter(array_map('trim', explode(';', $sql)), fn($c) => $c !== '');
    return array_values($cmds);
}

/** Só para quem testa localmente com SQLite: adapta a sintaxe do MySQL. */
function instalar_adaptar(string $cmd): string
{
    if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
        return $cmd;
    }
    $cmd = preg_replace('/INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $cmd) ?? $cmd;
    $cmd = preg_replace('/\)\s*ENGINE=.*$/s', ')', $cmd) ?? $cmd;
    $cmd = str_replace('INT UNSIGNED', 'INT', $cmd);
    $cmd = preg_replace('/UNIQUE KEY\s+\w+\s*\(/', 'UNIQUE (', $cmd) ?? $cmd;
    $cmd = preg_replace('/,\s*KEY\s+\w+\s*\([^)]*\)/', '', $cmd) ?? $cmd;
    return $cmd;
}

$jaTemConfig = db_configurado();
$jaTemAdmin  = false;
if ($jaTemConfig) {
    try {
        $jaTemAdmin = (int) db_val('SELECT COUNT(*) FROM admins') > 0;
    } catch (Throwable $e) {
        $jaTemAdmin = false;   // tabelas ainda não existem
    }
}

/* ---------- já instalado: trava ---------- */
if ($jaTemAdmin) {
    $passo = 'travado';
} else {
    $passo = $jaTemConfig ? 2 : 1;
}

/* ---------- processamento ---------- */
if (eh_post() && $passo !== 'travado') {
    csrf_exigir();

    if ($passo === 1) {
        $host = post('db_host', 'localhost');
        $nome = post('db_nome');
        $user = post('db_user');
        $pass = (string) ($_POST['db_pass'] ?? '');

        if ($nome === '' || $user === '') {
            $erros[] = 'Preencha o nome do banco e o usuário do banco.';
        } else {
            try {
                new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $nome), $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $conteudo = "<?php\nreturn " . var_export([
                    'db_host' => $host,
                    'db_nome' => $nome,
                    'db_user' => $user,
                    'db_pass' => $pass,
                ], true) . ";\n";
                if (@file_put_contents($arquivoConfig, $conteudo, LOCK_EX) === false) {
                    $erros[] = 'Conectei no banco, mas não consegui criar o arquivo includes/config.php. '
                             . 'Dê permissão de escrita na pasta "includes" e tente de novo.';
                } else {
                    redirecionar('index.php');
                }
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'Access denied') !== false) {
                    $erros[] = 'Usuário ou senha do banco incorretos.';
                } elseif (stripos($msg, 'Unknown database') !== false) {
                    $erros[] = 'Esse banco de dados não existe. Confira o nome (na Hostinger ele começa com u + números).';
                } elseif (stripos($msg, 'getaddrinfo') !== false || stripos($msg, 'Connection refused') !== false) {
                    $erros[] = 'Não consegui falar com o servidor do banco. Na Hostinger o host costuma ser "localhost".';
                } else {
                    $erros[] = 'Não foi possível conectar ao banco. Confira os dados e tente de novo.';
                }
            }
        }
    }

    if ($passo === 2) {
        $nomeAdmin = cortar(post('nome'), 80);
        $usuario   = usuario_normalizar(post('usuario'));
        $senha     = (string) ($_POST['senha'] ?? '');
        $senha2    = (string) ($_POST['senha2'] ?? '');

        if ($nomeAdmin === '') {
            $erros[] = 'Informe seu nome.';
        }
        if (!usuario_valido($usuario)) {
            $erros[] = 'O usuário deve ter de 3 a 40 caracteres: letras minúsculas, números, ponto, hífen ou sublinhado.';
        }
        if ($senha !== $senha2) {
            $erros[] = 'As duas senhas não são iguais.';
        } elseif (($erroSenha = senha_erro($senha, $usuario)) !== null) {
            $erros[] = $erroSenha;
        }

        if (!$erros) {
            try {
                // 1) tabelas
                foreach (instalar_comandos(RAIZ . '/database/schema.sql') as $cmd) {
                    db()->exec(instalar_adaptar($cmd));
                }

                $agora = date('Y-m-d H:i:s');
                db()->beginTransaction();

                // 2) configurações padrão (só as que ainda não existem)
                foreach (config_padrao() as $chave => $valor) {
                    if ((int) db_val('SELECT COUNT(*) FROM configuracoes WHERE chave = ?', [$chave]) === 0) {
                        db_exec('INSERT INTO configuracoes (chave, valor) VALUES (?, ?)', [$chave, $valor]);
                    }
                }

                // 3) cardápio atual (só se ainda estiver vazio)
                if ((int) db_val('SELECT COUNT(*) FROM categorias') === 0) {
                    $cardapio = require RAIZ . '/database/seed-cardapio.php';
                    foreach ($cardapio as $cat) {
                        db_exec(
                            'INSERT INTO categorias (slug, nome, nome_menu, descricao, icone, estilo, ordem, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
                            [$cat['slug'], $cat['nome'], $cat['nome_menu'], $cat['descricao'], $cat['icone'], $cat['estilo'], $cat['ordem']]
                        );
                        $catId = db_id();
                        foreach ($cat['itens'] as $it) {
                            db_exec(
                                'INSERT INTO itens (categoria_id, nome, descricao, preco, foto, destaque, esgotado, ativo, ordem, atualizado_em) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?, ?)',
                                [$catId, $it['nome'], $it['descricao'], $it['preco'], $it['foto'], $it['destaque'], $it['ordem'], $agora]
                            );
                        }
                    }
                }

                // 4) banner da página inicial (mesmas 3 fotos que já existiam)
                if ((int) db_val('SELECT COUNT(*) FROM banners') === 0) {
                    foreach (['Churrasco na brasa', 'Picanha grelhada', 'Espeto de carnes'] as $i => $alt) {
                        db_exec('INSERT INTO banners (arquivo, texto_alt, ordem, ativo) VALUES (?, ?, ?, 1)', ['img/churrasco-misto.jpg', $alt, ($i + 1) * 10]);
                    }
                }

                // 5) administrador
                db_exec(
                    'INSERT INTO admins (usuario, nome, senha_hash, criado_em) VALUES (?, ?, ?, ?)',
                    [$usuario, $nomeAdmin, password_hash($senha, PASSWORD_DEFAULT), $agora]
                );

                db()->commit();
                $concluido = true;
                $passo = 'ok';
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                error_log('[churrasco][install] ' . $e->getMessage());
                $erros[] = 'Algo deu errado ao criar as tabelas. Confira se o usuário do banco tem permissão total no banco e tente de novo.';
            }
        }
    }
}

/* ---------- se concluiu, tenta se apagar ---------- */
if ($concluido) {
    $apagou = @unlink(__FILE__);
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Instalação — Churrasco do Baiano</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Poppins',system-ui,sans-serif;background:#0d0d0d;color:#fff;min-height:100vh;display:grid;place-items:center;padding:24px}
    .box{width:100%;max-width:480px;background:#161616;border:1px solid #2a2a2a;border-top:4px solid #ffc107;border-radius:18px;padding:30px 26px}
    h1{font-size:1.5rem;color:#ffc107;margin-bottom:6px}
    h1 small{display:block;font-size:.78rem;color:#c70000;letter-spacing:2px;margin-bottom:4px}
    p.sub{color:#aaa;font-size:.9rem;line-height:1.6;margin-bottom:20px}
    label{display:block;font-size:.82rem;font-weight:600;color:#ddd;margin:14px 0 6px}
    input{width:100%;padding:12px 14px;border-radius:10px;border:1px solid #333;background:#0f0f0f;color:#fff;font-size:1rem;font-family:inherit}
    input:focus{outline:none;border-color:#ffc107;box-shadow:0 0 0 3px rgba(255,193,7,.2)}
    small.dica{display:block;color:#888;font-size:.78rem;margin-top:5px;line-height:1.5}
    button,.btn{display:block;width:100%;margin-top:22px;padding:14px;border:0;border-radius:10px;background:#ffc107;color:#111;font-weight:700;font-size:1rem;cursor:pointer;text-align:center;text-decoration:none;font-family:inherit}
    .erro{background:rgba(199,0,0,.15);border:1px solid rgba(199,0,0,.5);color:#ffb3b3;padding:12px 14px;border-radius:10px;font-size:.88rem;margin-bottom:14px;line-height:1.5}
    .ok{background:rgba(46,160,67,.15);border:1px solid rgba(46,160,67,.5);color:#b8f0c4;padding:12px 14px;border-radius:10px;font-size:.9rem;line-height:1.6;margin-bottom:14px}
    .passos{display:flex;gap:6px;margin-bottom:20px}
    .passos span{flex:1;height:5px;border-radius:5px;background:#2a2a2a}
    .passos span.on{background:#ffc107}
</style>
</head>
<body>
<div class="box">

<?php foreach ($erros as $m): ?>
    <div class="erro"><?= e($m) ?></div>
<?php endforeach; ?>

<?php if ($passo === 'travado'): ?>

    <h1><small>CHURRASCO DO BAIANO</small>Já instalado</h1>
    <p class="sub">O site já foi instalado e tem um administrador. Por segurança, este instalador não roda de novo.
       Apague a pasta <strong>install</strong> do servidor.</p>
    <a class="btn" href="../admin/login.php">Ir para o painel</a>

<?php elseif ($passo === 'ok'): ?>

    <h1><small>CHURRASCO DO BAIANO</small>Tudo pronto! 🎉</h1>
    <div class="ok">
        Banco criado, cardápio importado (<?= (int) db_val('SELECT COUNT(*) FROM itens') ?> itens) e seu usuário foi cadastrado.<br>
        <?= $apagou
            ? 'O instalador já se apagou sozinho.'
            : '<strong>Importante:</strong> apague a pasta <strong>install</strong> pelo Gerenciador de Arquivos.' ?>
    </div>
    <a class="btn" href="../admin/login.php">Entrar no painel</a>

<?php elseif ($passo === 1): ?>

    <div class="passos"><span class="on"></span><span></span></div>
    <h1><small>INSTALAÇÃO · PASSO 1 DE 2</small>Banco de dados</h1>
    <p class="sub">No hPanel da Hostinger vá em <strong>Bancos de Dados → Gerenciamento</strong>, crie um banco MySQL
       e copie os dados abaixo.</p>
    <form method="post" autocomplete="off">
        <?= csrf_campo() ?>
        <label for="db_host">Servidor (host)</label>
        <input id="db_host" name="db_host" value="<?= e(post('db_host', 'localhost')) ?>" required>
        <small class="dica">Na Hostinger quase sempre é <strong>localhost</strong>.</small>

        <label for="db_nome">Nome do banco</label>
        <input id="db_nome" name="db_nome" value="<?= e(post('db_nome')) ?>" placeholder="u123456789_churrasco" required>

        <label for="db_user">Usuário do banco</label>
        <input id="db_user" name="db_user" value="<?= e(post('db_user')) ?>" placeholder="u123456789_admin" required>

        <label for="db_pass">Senha do banco</label>
        <input id="db_pass" name="db_pass" type="password" autocomplete="new-password">

        <button type="submit">Testar e continuar</button>
    </form>

<?php else: ?>

    <div class="passos"><span class="on"></span><span class="on"></span></div>
    <h1><small>INSTALAÇÃO · PASSO 2 DE 2</small>Seu acesso ao painel</h1>
    <p class="sub">Conexão com o banco OK. Agora crie o usuário e a senha que você vai usar para entrar no painel.
       Vamos também importar o cardápio que já está no site.</p>
    <form method="post" autocomplete="off">
        <?= csrf_campo() ?>
        <label for="nome">Seu nome</label>
        <input id="nome" name="nome" value="<?= e(post('nome')) ?>" required>

        <label for="usuario">Usuário (para o login)</label>
        <input id="usuario" name="usuario" value="<?= e(post('usuario')) ?>" placeholder="ex.: baiano" autocapitalize="none" required>

        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" required>
        <small class="dica">Mínimo 8 caracteres. Misture letras e números.</small>

        <label for="senha2">Repita a senha</label>
        <input id="senha2" name="senha2" type="password" autocomplete="new-password" minlength="8" required>

        <button type="submit">Instalar</button>
    </form>

<?php endif; ?>

</div>
</body>
</html>
