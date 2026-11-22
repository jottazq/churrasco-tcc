<?php
/**
 * Conexão com o banco (PDO) e atalhos para consultas.
 * Todas as consultas usam parâmetros (?) — nunca concatene dados do usuário no SQL.
 */
declare(strict_types=1);

function db_config(): ?array
{
    static $cfg = false;
    if ($cfg === false) {
        $arq = __DIR__ . '/config.php';
        $cfg = is_file($arq) ? require $arq : null;
    }
    return is_array($cfg) ? $cfg : null;
}

function db_configurado(): bool
{
    return db_config() !== null;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = db_config();
    if ($c === null) {
        throw new RuntimeException('Banco de dados ainda não configurado (rode /install).');
    }

    $dsn = $c['dsn'] ?? sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_nome']);

    $pdo = new PDO($dsn, $c['db_user'] ?? null, $c['db_pass'] ?? null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    if (str_starts_with($dsn, 'sqlite')) {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}

/** Executa um comando (INSERT/UPDATE/DELETE) e devolve quantas linhas mudaram. */
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Todas as linhas. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Uma linha (ou null). */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

/** Um único valor (ou null). */
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

/** ID do último INSERT. */
function db_id(): int
{
    return (int) db()->lastInsertId();
}

/**
 * Sobe/desce um registro na ordem de exibição.
 * $tabela/$colunaGrupo vêm SEMPRE do código (lista fixa abaixo), nunca do usuário.
 */
function db_mover(string $tabela, ?string $colunaGrupo, $valorGrupo, int $id, string $direcao): void
{
    $permitidas = ['itens' => 'categoria_id', 'categorias' => null, 'galeria_fotos' => null, 'banners' => null];
    if (!array_key_exists($tabela, $permitidas) || $permitidas[$tabela] !== $colunaGrupo) {
        throw new InvalidArgumentException('Tabela não permitida para reordenar.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $sql = "SELECT id FROM {$tabela}" . ($colunaGrupo ? " WHERE {$colunaGrupo} = ?" : '') . ' ORDER BY ordem, id';
        $ids = array_map('intval', array_column(db_all($sql, $colunaGrupo ? [$valorGrupo] : []), 'id'));

        $pos = array_search($id, $ids, true);
        if ($pos !== false) {
            $alvo = $direcao === 'subir' ? $pos - 1 : $pos + 1;
            if ($alvo >= 0 && $alvo < count($ids)) {
                [$ids[$pos], $ids[$alvo]] = [$ids[$alvo], $ids[$pos]];
            }
            foreach ($ids as $i => $idLinha) {
                db_exec("UPDATE {$tabela} SET ordem = ? WHERE id = ?", [($i + 1) * 10, $idLinha]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Próxima posição no fim da lista. */
function db_proxima_ordem(string $tabela, ?string $colunaGrupo = null, $valorGrupo = null): int
{
    $permitidas = ['itens' => 'categoria_id', 'categorias' => null, 'galeria_fotos' => null, 'banners' => null];
    if (!array_key_exists($tabela, $permitidas) || $permitidas[$tabela] !== $colunaGrupo) {
        throw new InvalidArgumentException('Tabela não permitida.');
    }
    $sql = "SELECT MAX(ordem) FROM {$tabela}" . ($colunaGrupo ? " WHERE {$colunaGrupo} = ?" : '');
    $max = db_val($sql, $colunaGrupo ? [$valorGrupo] : []);
    return ((int) $max) + 10;
}
