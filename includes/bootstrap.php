<?php
/**
 * Carregado por TODAS as páginas (site e painel).
 * Define fuso, erros, e carrega banco + funções de apoio.
 */
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

// Em produção nunca mostramos erro técnico na tela: vai para o log do servidor.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('RAIZ', dirname(__DIR__));          // pasta do site no servidor
define('PASTA_UPLOADS', 'img/uploads');    // onde ficam as fotos enviadas pelo painel

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

// Qualquer erro inesperado vira uma página amigável (sem expor detalhes).
set_exception_handler(function (Throwable $e): void {
    error_log('[churrasco] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Instabilidade temporária</title></head>'
       . '<body style="font-family:sans-serif;background:#0d0d0d;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px">'
       . '<div><h1 style="color:#ffc107;margin:0 0 10px">Ops, algo deu errado</h1>'
       . '<p style="color:#bbb">Estamos com uma instabilidade temporária. Tente novamente em instantes.</p></div>'
       . '</body></html>';
    exit;
});
