<?php
/**
 * Login, sessão e proteção contra CSRF do painel.
 *
 * - Senhas guardadas com password_hash (nunca em texto puro).
 * - Sessão com cookie HttpOnly + SameSite, renovada no login.
 * - Bloqueio temporário após várias tentativas erradas (força bruta).
 * - Todo formulário POST exige token CSRF.
 */
declare(strict_types=1);

const SESSAO_NOME        = 'CBSESSID';
const SESSAO_OCIOSA_SEG  = 10800;   // 3 horas sem usar = pede login de novo
const LOGIN_MAX_ERROS    = 5;       // erros por usuário+IP
const LOGIN_MAX_ERROS_IP = 30;      // erros por IP (qualquer usuário)
const LOGIN_JANELA_MIN   = 15;      // janela de bloqueio, em minutos
// hash "de mentira": faz o tempo de resposta ser igual quando o usuário não existe
const HASH_FALSO = '$2y$10$A5SgZN0FOYcnBuCqFOo25e7OCQ9qPh8puLXg3yvmh9NIRxbIg27T.';

function sessao_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name(SESSAO_NOME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ip_cliente(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function usuario_normalizar(string $u): string
{
    return mb_strtolower(trim($u), 'UTF-8');
}

function usuario_valido(string $u): bool
{
    return (bool) preg_match('/^[a-z0-9._-]{3,40}$/', $u);
}

/** Cabeçalhos que todo o painel deve enviar. */
function admin_cabecalhos(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

/** Admin logado (ou null). Cacheia durante a requisição. */
function admin_atual(): ?array
{
    static $atual = false;
    if ($atual !== false) {
        return $atual;
    }
    sessao_iniciar();

    $id = $_SESSION['admin_id'] ?? null;
    $ultima = $_SESSION['ultima_atividade'] ?? 0;
    if (!$id || (time() - (int) $ultima) > SESSAO_OCIOSA_SEG) {
        $atual = null;
        return null;
    }
    $admin = db_one('SELECT id, usuario, nome FROM admins WHERE id = ?', [(int) $id]);
    if (!$admin) {               // usuário foi removido: derruba a sessão
        $atual = null;
        return null;
    }
    $_SESSION['ultima_atividade'] = time();
    $atual = $admin;
    return $atual;
}

/** Use no topo de toda página do painel (menos o login). */
function exigir_login(): array
{
    admin_cabecalhos();
    $admin = admin_atual();
    if (!$admin) {
        sessao_iniciar();
        $_SESSION = [];
        redirecionar('login.php');
    }
    return $admin;
}

/**
 * Tenta entrar. Retorna [true, ''] ou [false, 'mensagem'].
 */
function login_tentar(string $usuarioBruto, string $senha): array
{
    $usuario = usuario_normalizar($usuarioBruto);
    $ip      = ip_cliente();
    $agora   = date('Y-m-d H:i:s');
    $janela  = date('Y-m-d H:i:s', time() - LOGIN_JANELA_MIN * 60);

    // limpeza de registros antigos (mais de 1 dia)
    db_exec('DELETE FROM login_tentativas WHERE criado_em < ?', [date('Y-m-d H:i:s', time() - 86400)]);

    $errosPar = (int) db_val(
        'SELECT COUNT(*) FROM login_tentativas WHERE ip = ? AND usuario = ? AND criado_em > ?',
        [$ip, substr($usuario, 0, 40), $janela]
    );
    $errosIp = (int) db_val('SELECT COUNT(*) FROM login_tentativas WHERE ip = ? AND criado_em > ?', [$ip, $janela]);

    if ($errosPar >= LOGIN_MAX_ERROS || $errosIp >= LOGIN_MAX_ERROS_IP) {
        return [false, 'Muitas tentativas erradas. Aguarde ' . LOGIN_JANELA_MIN . ' minutos e tente de novo.'];
    }

    $admin = $usuario !== '' ? db_one('SELECT * FROM admins WHERE usuario = ?', [$usuario]) : null;
    $ok = password_verify($senha, $admin['senha_hash'] ?? HASH_FALSO) && $admin !== null;

    if (!$ok) {
        db_exec('INSERT INTO login_tentativas (ip, usuario, criado_em) VALUES (?, ?, ?)', [$ip, substr($usuario, 0, 40), $agora]);
        return [false, 'Usuário ou senha incorretos.'];
    }

    // sucesso
    if (password_needs_rehash($admin['senha_hash'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE admins SET senha_hash = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $admin['id']]);
    }
    db_exec('DELETE FROM login_tentativas WHERE ip = ? AND usuario = ?', [$ip, substr($usuario, 0, 40)]);
    db_exec('UPDATE admins SET ultimo_login = ? WHERE id = ?', [$agora, $admin['id']]);

    sessao_iniciar();
    session_regenerate_id(true);
    $_SESSION = [
        'admin_id'         => (int) $admin['id'],
        'ultima_atividade' => time(),
    ];
    return [true, ''];
}

function logout_executar(): void
{
    sessao_iniciar();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/* ---------------------------------------------------------
   Senhas
--------------------------------------------------------- */

/** Erro de regra de senha, ou null se estiver ok. */
function senha_erro(string $senha, string $usuario = ''): ?string
{
    if (mb_strlen($senha) < 8) {
        return 'A senha precisa ter pelo menos 8 caracteres.';
    }
    if (mb_strlen($senha) > 200) {
        return 'Senha muito longa.';
    }
    if ($usuario !== '' && mb_strtolower($senha) === mb_strtolower($usuario)) {
        return 'A senha não pode ser igual ao usuário.';
    }
    if (preg_match('/^(.)\1+$/u', $senha) || in_array(mb_strtolower($senha), ['12345678', '123456789', '1234567890', 'senha123', 'password', 'churrasco', 'churrasco123'], true)) {
        return 'Escolha uma senha mais difícil de adivinhar.';
    }
    return null;
}

/* ---------------------------------------------------------
   Proteção CSRF (formulários forjados por outros sites)
--------------------------------------------------------- */

function csrf_token(): string
{
    sessao_iniciar();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Chame no começo de todo tratamento de POST. */
function csrf_exigir(): void
{
    sessao_iniciar();
    $enviado = $_POST['_csrf'] ?? '';
    $guardado = $_SESSION['csrf'] ?? '';
    if (!is_string($enviado) || $guardado === '' || !hash_equals($guardado, $enviado)) {
        http_response_code(419);
        admin_cabecalhos();
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Sessão expirada</title></head><body style="font-family:sans-serif;background:#0d0d0d;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px">'
           . '<div><h1 style="color:#ffc107">Sessão expirada</h1><p style="color:#bbb">Por segurança, essa ação não foi feita. Volte, recarregue a página e tente de novo.</p>'
           . '<p><a style="color:#ffc107" href="index.php">Voltar ao painel</a></p></div></body></html>';
        exit;
    }
}

/* ---------------------------------------------------------
   Mensagens de aviso (aparecem uma vez após salvar)
--------------------------------------------------------- */

function aviso(string $tipo, string $texto): void
{
    sessao_iniciar();
    $_SESSION['avisos'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function avisos_pegar(): array
{
    sessao_iniciar();
    $a = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $a;
}
