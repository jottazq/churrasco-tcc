<?php
/**
 * Upload seguro de fotos.
 *
 * Toda foto enviada é lida, validada de verdade (não confiamos no nome nem na
 * extensão), reduzida, corrigida de rotação (fotos de celular) e REGRAVADA como
 * JPEG com nome aleatório. Isso elimina qualquer código escondido no arquivo.
 */
declare(strict_types=1);

const UPLOAD_MAX_BYTES  = 15 * 1024 * 1024;   // 15 MB por foto
const UPLOAD_MAX_PIXELS = 40000000;           // 40 megapixels

/**
 * Salva uma foto vinda de <input type="file">.
 * Retorna ['ok' => true, 'caminho' => 'img/uploads/xxx.jpg']
 *      ou ['ok' => false, 'erro' => '...', 'vazio' => bool]  (vazio = nenhum arquivo escolhido)
 */
function upload_foto(array $arq, string $prefixo = 'foto', int $ladoMax = 1600): array
{
    $codigo = $arq['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($codigo === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'erro' => '', 'vazio' => true];
    }
    if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'erro' => 'A foto é grande demais para o servidor. Tente uma menor (até 15 MB).', 'vazio' => false];
    }
    if ($codigo !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Não foi possível receber a foto. Tente novamente.', 'vazio' => false];
    }

    $tmp = (string) ($arq['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        return ['ok' => false, 'erro' => 'Arquivo inválido.', 'vazio' => false];
    }
    if (($arq['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'erro' => 'Foto muito grande (máximo 15 MB).', 'vazio' => false];
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'erro' => 'Esse arquivo não é uma imagem válida. Envie JPG, PNG ou WEBP.', 'vazio' => false];
    }
    [$larg, $alt, $tipo] = $info;
    if (!in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return ['ok' => false, 'erro' => 'Formato não aceito. Envie JPG, PNG ou WEBP.', 'vazio' => false];
    }
    if ($larg < 1 || $alt < 1 || $larg * $alt > UPLOAD_MAX_PIXELS) {
        return ['ok' => false, 'erro' => 'Imagem com resolução alta demais. Reduza o tamanho e tente de novo.', 'vazio' => false];
    }

    $pasta = RAIZ . '/' . PASTA_UPLOADS;
    if (!is_dir($pasta) && !@mkdir($pasta, 0755, true)) {
        return ['ok' => false, 'erro' => 'Não consegui criar a pasta de fotos no servidor.', 'vazio' => false];
    }

    $prefixo = preg_replace('/[^a-z0-9]+/', '-', strtolower($prefixo)) ?: 'foto';
    $nome    = $prefixo . '-' . bin2hex(random_bytes(8)) . '.jpg';
    $destino = $pasta . '/' . $nome;

    if (extension_loaded('gd')) {
        @ini_set('memory_limit', '512M');
        $img = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
            default        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        };
        if ($img === false) {
            return ['ok' => false, 'erro' => 'Não consegui ler essa imagem. Tente salvar de novo como JPG.', 'vazio' => false];
        }

        // corrige a rotação de fotos tiradas em pé no celular
        if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $o = (int) ($exif['Orientation'] ?? 1);
            $graus = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($graus !== 0) {
                $girada = imagerotate($img, $graus, 0);
                if ($girada !== false) {
                    $img = $girada;
                }
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $ladoMax) {
            $escala = $ladoMax / max($w, $h);
            $nw = max(1, (int) round($w * $escala));
            $nh = max(1, (int) round($h * $escala));
        } else {
            $nw = $w;
            $nh = $h;
        }

        // fundo branco (resolve PNG com transparência), já redimensionado
        $final = imagecreatetruecolor($nw, $nh);
        imagefill($final, 0, 0, imagecolorallocate($final, 255, 255, 255));
        imagecopyresampled($final, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imageinterlace($final, true);

        $gravou = imagejpeg($final, $destino, 84);
        imagedestroy($img);
        imagedestroy($final);
        if (!$gravou) {
            return ['ok' => false, 'erro' => 'Não consegui salvar a foto no servidor.', 'vazio' => false];
        }
    } else {
        // Sem a biblioteca GD (raro): copia o arquivo já validado como imagem.
        if ($tipo !== IMAGETYPE_JPEG) {
            return ['ok' => false, 'erro' => 'O servidor só aceita JPG no momento.', 'vazio' => false];
        }
        if (!move_uploaded_file($tmp, $destino)) {
            return ['ok' => false, 'erro' => 'Não consegui salvar a foto no servidor.', 'vazio' => false];
        }
    }

    @chmod($destino, 0644);
    return ['ok' => true, 'caminho' => PASTA_UPLOADS . '/' . $nome];
}

/** A foto está em uso em algum lugar do site? */
function foto_em_uso(string $caminho, ?string $ignorarTabela = null, ?int $ignorarId = null): bool
{
    $consultas = [
        'itens'         => 'SELECT id FROM itens WHERE foto = ?',
        'galeria_fotos' => 'SELECT id FROM galeria_fotos WHERE arquivo = ?',
        'banners'       => 'SELECT id FROM banners WHERE arquivo = ?',
    ];
    foreach ($consultas as $tabela => $sql) {
        foreach (db_all($sql, [$caminho]) as $linha) {
            if ($tabela === $ignorarTabela && (int) $linha['id'] === $ignorarId) {
                continue;
            }
            return true;
        }
    }
    return (bool) db_val('SELECT COUNT(*) FROM configuracoes WHERE chave = ? AND valor = ?', ['foto_fachada', $caminho]);
}

/**
 * Apaga o arquivo de uma foto enviada pelo painel, se ninguém mais usa.
 * Fotos originais do site (img/xxx.jpg) NUNCA são apagadas por aqui.
 */
function foto_apagar(?string $caminho, ?string $ignorarTabela = null, ?int $ignorarId = null): void
{
    if (!$caminho || !preg_match('#^' . preg_quote(PASTA_UPLOADS, '#') . '/[a-z0-9._-]+\.jpg$#', $caminho)) {
        return;
    }
    if (foto_em_uso($caminho, $ignorarTabela, $ignorarId)) {
        return;
    }
    $arquivo = RAIZ . '/' . $caminho;
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

/**
 * Normaliza o array de vários arquivos ($_FILES['fotos'] com multiple)
 * em uma lista de arquivos individuais.
 */
function arquivos_multiplos(string $campo): array
{
    $f = $_FILES[$campo] ?? null;
    if (!$f || !is_array($f['name'] ?? null)) {
        return [];
    }
    $lista = [];
    foreach ($f['name'] as $i => $nome) {
        $lista[] = [
            'name'     => $nome,
            'type'     => $f['type'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i] ?? '',
            'error'    => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $f['size'][$i] ?? 0,
        ];
    }
    return $lista;
}
