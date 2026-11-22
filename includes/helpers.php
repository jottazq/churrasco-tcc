<?php
/**
 * Funções de apoio usadas em todo o site.
 */
declare(strict_types=1);

/** Escapa texto para HTML (protege contra XSS). Use SEMPRE ao imprimir dados do banco. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 26.5 -> "R$ 26,50" */
function preco_br($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** "26,5" | "R$ 26,50" | "26.50" -> "26.50"  (ou null se for inválido) */
function preco_ler(string $texto): ?string
{
    $t = trim(str_ireplace(['R$', ' '], '', $texto));
    if ($t === '') {
        return null;
    }
    if (str_contains($t, ',')) {
        $t = str_replace('.', '', $t);   // ponto = separador de milhar
        $t = str_replace(',', '.', $t);  // vírgula = decimal
    }
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $t)) {
        return null;
    }
    return number_format((float) $t, 2, '.', '');
}

/** "Meia Porção!" -> "meia-porcao" */
function slugify(string $texto): string
{
    $t = mb_strtolower($texto, 'UTF-8');
    $t = strtr($t, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ]);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
    $t = trim($t, '-');
    return $t !== '' ? mb_substr($t, 0, 50) : 'categoria';
}

/** Corta o texto no limite de caracteres (sem quebrar acentos). */
function cortar(string $texto, int $max): string
{
    $texto = trim($texto);
    return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max) : $texto;
}

/** Quebra um texto em parágrafos (linha em branco separa). Retorna HTML seguro. */
function paragrafos_html(string $texto): string
{
    $partes = preg_split('/\R{2,}/', trim($texto)) ?: [];
    $out = '';
    foreach ($partes as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out .= '<p>' . nl2br(e($p)) . '</p>';
        }
    }
    return $out;
}

function eh_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Valor de um campo de formulário, já sem espaços nas pontas. */
function post(string $campo, string $padrao = ''): string
{
    $v = $_POST[$campo] ?? $padrao;
    return is_string($v) ? trim($v) : $padrao;
}

function post_bool(string $campo): int
{
    return !empty($_POST[$campo]) ? 1 : 0;
}

function get_int(string $campo, int $padrao = 0): int
{
    $v = $_GET[$campo] ?? null;
    return (is_string($v) && ctype_digit($v)) ? (int) $v : $padrao;
}

function post_int(string $campo, int $padrao = 0): int
{
    $v = $_POST[$campo] ?? null;
    return (is_string($v) && ctype_digit($v)) ? (int) $v : $padrao;
}

function redirecionar(string $destino): never
{
    header('Location: ' . $destino);
    exit;
}

/** Caminho de um arquivo do site visto de uma página que está em subpasta (ex.: /admin). */
function url_relativa(string $caminho, string $prefixo = ''): string
{
    return $prefixo . ltrim($caminho, '/');
}

/** "https://exemplo.com" válido? (só http/https) */
function url_valida(string $url): bool
{
    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }
    $partes = parse_url($url);
    return is_array($partes) && !empty($partes['host']) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/** Texto do tipo "há 3 dias" / "hoje" para a lista do painel. */
function data_br(?string $datahora): string
{
    if (!$datahora) {
        return '—';
    }
    $ts = strtotime($datahora);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}
