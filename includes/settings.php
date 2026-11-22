<?php
/**
 * Configurações editáveis pelo painel (telefone, redes, endereço, horários, textos...).
 * Se o banco estiver fora do ar, o site continua abrindo com os valores padrão abaixo.
 */
declare(strict_types=1);

const DIAS_SEMANA = [
    'seg' => 'Segunda',
    'ter' => 'Terça',
    'qua' => 'Quarta',
    'qui' => 'Quinta',
    'sex' => 'Sexta',
    'sab' => 'Sábado',
    'dom' => 'Domingo',
];

function horarios_padrao(): array
{
    $util = ['aberto' => 1, 'abre' => '18:00', 'fecha' => '22:00'];
    return [
        'seg' => $util,
        'ter' => ['aberto' => 0, 'abre' => '18:00', 'fecha' => '22:00'],
        'qua' => $util,
        'qui' => $util,
        'sex' => $util,
        'sab' => ['aberto' => 1, 'abre' => '11:00', 'fecha' => '21:30'],
        'dom' => ['aberto' => 1, 'abre' => '11:00', 'fecha' => '15:30'],
    ];
}

/** Valores iniciais (são os mesmos textos que já estavam no site). */
function config_padrao(): array
{
    return [
        'site_nome'         => 'Churrasco do Baiano Sem Miséria',
        'whatsapp'          => '5521991633808',
        'whatsapp_mensagem' => '',
        'link_pedido'       => 'https://pedido.anota.ai/loja/churrasco-do-baiano-sem-miseria-1?f=msa',
        'instagram_url'     => '',
        'facebook_url'      => '',
        'endereco'          => 'Av. Automóvel Clube, 1471 - Santa Cruz da Serra, Duque de Caxias - RJ, 25255-030',
        'cidade_rodape'     => 'Duque de Caxias - RJ',
        'bairro_destaque'   => 'Santa Cruz da Serra',
        'mapa_embed_url'    => 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14728.835949009852!2d-43.242477!3d-22.6459954!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x99739fee4b2177%3A0xe0c76dd580b94c85!2sChurrasco%20do%20Baiano%20Sem%20Mis%C3%A9ria!5e0!3m2!1spt-BR!2sbr!4v1783979134145!5m2!1spt-BR!2sbr',
        'link_maps'         => 'https://maps.app.goo.gl/tHkhDJP7Q5EtsfBT7',
        'link_waze'         => 'https://waze.com/ul/h75cppqf9k',
        'horarios'          => json_encode(horarios_padrao(), JSON_UNESCAPED_UNICODE),
        'obs_cardapio'      => 'Preços sujeitos a alteração sem aviso prévio. Consulte a disponibilidade dos itens antes de fechar o pedido.',
        'hero_subtitulo'    => 'DESDE 2020',
        'hero_texto'        => 'Sabor, tradição e qualidade em cada corte. O verdadeiro churrasco na brasa, com carnes selecionadas e ambiente familiar em Duque de Caxias.',
        'sobre_historia'    => 'O Churrasco do Baiano Sem Miséria nasceu com o objetivo de oferecer um churrasco de qualidade, preparado com dedicação e servido em um ambiente familiar. Nosso compromisso é entregar refeições saborosas, bem servidas e com excelente atendimento para todos os nossos clientes.',
        'sobre_missao'      => 'Servir alimentos de qualidade, proporcionando uma experiência agradável para nossos clientes.',
        'sobre_visao'       => 'Ser referência em churrasco na região de Duque de Caxias.',
        'sobre_valores'     => 'Respeito, honestidade, qualidade, higiene e atendimento com excelência.',
        'foto_fachada'      => 'img/fachada.jpg',
    ];
}

function cfg_carregar(bool $recarregar = false): array
{
    static $cache = null;
    if ($cache === null || $recarregar) {
        $cache = config_padrao();
        try {
            if (db_configurado()) {
                foreach (db_all('SELECT chave, valor FROM configuracoes') as $linha) {
                    $cache[$linha['chave']] = (string) $linha['valor'];
                }
            }
        } catch (Throwable $e) {
            error_log('[churrasco] configurações indisponíveis, usando padrão: ' . $e->getMessage());
        }
    }
    return $cache;
}

function cfg(string $chave): string
{
    return (string) (cfg_carregar()[$chave] ?? '');
}

/** Grava (cria ou atualiza) uma configuração. */
function cfg_salvar(string $chave, string $valor): void
{
    $existe = db_val('SELECT COUNT(*) FROM configuracoes WHERE chave = ?', [$chave]);
    if ((int) $existe > 0) {
        db_exec('UPDATE configuracoes SET valor = ? WHERE chave = ?', [$valor, $chave]);
    } else {
        db_exec('INSERT INTO configuracoes (chave, valor) VALUES (?, ?)', [$chave, $valor]);
    }
    cfg_carregar(true);
}

/* ---------------------------------------------------------
   Links prontos
--------------------------------------------------------- */

function wa_link(string $mensagem = ''): string
{
    $url = 'https://wa.me/' . preg_replace('/\D+/', '', cfg('whatsapp'));
    $msg = $mensagem !== '' ? $mensagem : cfg('whatsapp_mensagem');
    return $msg !== '' ? $url . '?text=' . rawurlencode($msg) : $url;
}

/** 5521991633808 -> (21) 99163-3808 (só para mostrar no painel) */
function telefone_br(string $numero): string
{
    $d = preg_replace('/\D+/', '', $numero) ?? '';
    if (str_starts_with($d, '55') && strlen($d) >= 12) {
        $d = substr($d, 2);
    }
    if (strlen($d) === 11) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
    }
    if (strlen($d) === 10) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    }
    return $numero;
}

/* ---------------------------------------------------------
   Horário de funcionamento
--------------------------------------------------------- */

function horarios(): array
{
    $padrao = horarios_padrao();
    $salvo  = json_decode(cfg('horarios'), true);
    $salvo  = is_array($salvo) ? $salvo : [];
    $out = [];
    foreach (DIAS_SEMANA as $k => $_) {
        $d = is_array($salvo[$k] ?? null) ? $salvo[$k] : [];
        $out[$k] = [
            'aberto' => isset($d['aberto']) ? (int) (bool) $d['aberto'] : $padrao[$k]['aberto'],
            'abre'   => preg_match('/^\d{2}:\d{2}$/', (string) ($d['abre'] ?? '')) ? $d['abre'] : $padrao[$k]['abre'],
            'fecha'  => preg_match('/^\d{2}:\d{2}$/', (string) ($d['fecha'] ?? '')) ? $d['fecha'] : $padrao[$k]['fecha'],
        ];
    }
    return $out;
}

function horario_texto(array $dia): string
{
    return $dia['aberto'] ? $dia['abre'] . ' às ' . $dia['fecha'] : 'Fechado';
}

/** "Aberto todos os dias" / "Aberto todos os dias, exceto terça" */
function horarios_resumo(): string
{
    $fechados = [];
    foreach (horarios() as $k => $d) {
        if (!$d['aberto']) {
            $fechados[] = mb_strtolower(DIAS_SEMANA[$k], 'UTF-8');
        }
    }
    if (count($fechados) === 0) {
        return 'Aberto todos os dias';
    }
    if (count($fechados) === 7) {
        return 'Fechado temporariamente';
    }
    if (count($fechados) === 1) {
        return 'Aberto todos os dias, exceto ' . $fechados[0];
    }
    $ultimo = array_pop($fechados);
    return 'Aberto todos os dias, exceto ' . implode(', ', $fechados) . ' e ' . $ultimo;
}

/**
 * Junta dias seguidos com o mesmo horário: "Quarta a Sexta: 18:00 às 22:00".
 * Devolve [['dias' => 'Quarta a Sexta', 'texto' => '18:00 às 22:00', 'fechado' => false], ...]
 */
function horarios_agrupados(): array
{
    $grupos = [];
    foreach (horarios() as $k => $d) {
        $texto = horario_texto($d);
        $ult = count($grupos) - 1;
        if ($ult >= 0 && $grupos[$ult]['texto'] === $texto) {
            $grupos[$ult]['fim'] = DIAS_SEMANA[$k];
            $grupos[$ult]['qtd']++;
        } else {
            $grupos[] = ['inicio' => DIAS_SEMANA[$k], 'fim' => DIAS_SEMANA[$k], 'qtd' => 1, 'texto' => $texto, 'fechado' => !$d['aberto']];
        }
    }
    $out = [];
    foreach ($grupos as $g) {
        $dias = $g['qtd'] === 1 ? $g['inicio'] : ($g['qtd'] === 2 ? $g['inicio'] . ' e ' . $g['fim'] : $g['inicio'] . ' a ' . $g['fim']);
        $out[] = ['dias' => $dias, 'texto' => $g['texto'], 'fechado' => $g['fechado']];
    }
    return $out;
}
