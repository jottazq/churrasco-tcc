<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
exigir_login();

/** Campos de texto simples: chave => [tamanho máximo] */
const CAMPOS_TEXTO = [
    'whatsapp_mensagem' => 200,
    'endereco'          => 200,
    'cidade_rodape'     => 80,
    'bairro_destaque'   => 60,
    'obs_cardapio'      => 300,
    'hero_subtitulo'    => 40,
    'hero_texto'        => 300,
    'sobre_historia'    => 1500,
    'sobre_missao'      => 400,
    'sobre_visao'       => 400,
    'sobre_valores'     => 400,
];

$atual = cfg_carregar();
$v = $atual;                       // valores mostrados no formulário
$horariosForm = horarios();
$erros = [];

/** Aceita "@baiano", "instagram.com/baiano" ou a URL completa. */
function normalizar_rede(string $texto, string $rede): string
{
    $t = trim($texto);
    if ($t === '') {
        return '';
    }
    if ($rede === 'instagram' && preg_match('/^@?([A-Za-z0-9._]{2,30})$/', $t, $m)) {
        return 'https://www.instagram.com/' . $m[1] . '/';
    }
    if (!preg_match('#^https?://#i', $t)) {
        $t = 'https://' . ltrim($t, '/');
    }
    return $t;
}

if (eh_post()) {
    csrf_exigir();

    foreach (CAMPOS_TEXTO as $chave => $max) {
        $v[$chave] = cortar(str_replace("\r\n", "\n", post($chave)), $max);
    }

    // WhatsApp: só números, com DDI 55
    $digitos = preg_replace('/\D+/', '', post('whatsapp')) ?? '';
    if (strlen($digitos) === 10 || strlen($digitos) === 11) {
        $digitos = '55' . $digitos;
    }
    $v['whatsapp'] = $digitos;
    if (!preg_match('/^55\d{10,11}$/', $digitos)) {
        $erros[] = 'WhatsApp inválido. Digite com DDD, por exemplo (21) 99163-3808.';
    }

    // Links
    $v['link_pedido'] = trim(post('link_pedido'));
    if ($v['link_pedido'] !== '' && !url_valida($v['link_pedido'])) {
        $erros[] = 'O link de pedidos precisa começar com https://';
    }

    $v['instagram_url'] = normalizar_rede(post('instagram_url'), 'instagram');
    if ($v['instagram_url'] !== '' && !url_valida($v['instagram_url'])) {
        $erros[] = 'Instagram inválido. Use @seuusuario ou o link completo do perfil.';
    }
    $v['facebook_url'] = normalizar_rede(post('facebook_url'), 'facebook');
    if ($v['facebook_url'] !== '' && !url_valida($v['facebook_url'])) {
        $erros[] = 'Facebook inválido. Cole o link completo da página.';
    }

    foreach (['link_maps' => 'Google Maps', 'link_waze' => 'Waze'] as $chave => $rotulo) {
        $v[$chave] = trim(post($chave));
        if ($v[$chave] !== '' && !url_valida($v[$chave])) {
            $erros[] = "O link do {$rotulo} precisa começar com https://";
        }
    }

    // Mapa: aceita o código <iframe> inteiro ou só a URL
    $mapa = trim(post('mapa_embed_url'));
    if ($mapa !== '' && stripos($mapa, '<iframe') !== false && preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $mapa, $m)) {
        $mapa = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    $v['mapa_embed_url'] = $mapa;
    if ($mapa !== '' && !str_starts_with($mapa, 'https://www.google.com/maps/embed')) {
        $erros[] = 'Mapa inválido. No Google Maps use Compartilhar → Incorporar um mapa e cole o código.';
    }

    if ($v['endereco'] === '') {
        $erros[] = 'Informe o endereço.';
    }

    // Horários
    $novoHorario = [];
    foreach (DIAS_SEMANA as $k => $nomeDia) {
        $abre  = post("h_{$k}_abre");
        $fecha = post("h_{$k}_fecha");
        $aberto = post_bool("h_{$k}_aberto");
        $okAbre  = (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $abre);
        $okFecha = (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $fecha);
        if ($aberto && (!$okAbre || !$okFecha)) {
            $erros[] = "Confira o horário de {$nomeDia}.";
        }
        $novoHorario[$k] = [
            'aberto' => $aberto,
            'abre'   => $okAbre ? $abre : $horariosForm[$k]['abre'],
            'fecha'  => $okFecha ? $fecha : $horariosForm[$k]['fecha'],
        ];
    }
    $horariosForm = $novoHorario;

    // Foto da fachada
    $novaFachada = null;
    if (!$erros) {
        $up = upload_foto($_FILES['fachada'] ?? [], 'fachada', 1920);
        if ($up['ok']) {
            $novaFachada = $up['caminho'];
        } elseif (!$up['vazio']) {
            $erros[] = $up['erro'];
        }
    }

    if (!$erros) {
        foreach (array_merge(['whatsapp', 'link_pedido', 'instagram_url', 'facebook_url', 'link_maps', 'link_waze', 'mapa_embed_url'], array_keys(CAMPOS_TEXTO)) as $chave) {
            cfg_salvar($chave, (string) $v[$chave]);
        }
        cfg_salvar('horarios', json_encode($novoHorario, JSON_UNESCAPED_UNICODE));

        if ($novaFachada !== null) {
            $antiga = $atual['foto_fachada'];
            cfg_salvar('foto_fachada', $novaFachada);
            foto_apagar($antiga);
        } elseif (post_bool('restaurar_fachada') && $atual['foto_fachada'] !== 'img/fachada.jpg') {
            $antiga = $atual['foto_fachada'];
            cfg_salvar('foto_fachada', 'img/fachada.jpg');
            foto_apagar($antiga);
        }

        aviso('ok', 'Configurações salvas. O site já está atualizado.');
        redirecionar('configuracoes.php');
    }
}

painel_abrir('Configurações', 'configuracoes');
?>

<?php foreach ($erros as $m): ?>
    <div class="aviso erro" role="alert"><i class="fas fa-triangle-exclamation"></i><div><?= e($m) ?></div></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_campo() ?>

    <div class="cartao" id="pedidos">
        <h2><i class="fab fa-whatsapp"></i> WhatsApp e pedidos</h2>
        <p class="ajuda">Usados nos botões "WhatsApp" e "Pedir agora" de todas as páginas.</p>

        <div class="grade-2">
            <div class="campo">
                <label for="whatsapp">Número do WhatsApp</label>
                <input type="tel" id="whatsapp" name="whatsapp" inputmode="tel" value="<?= e(telefone_br($v['whatsapp'])) ?>" placeholder="(21) 99999-9999">
                <span class="dica">Com DDD. O código do Brasil (55) é adicionado sozinho.</span>
            </div>
            <div class="campo">
                <label for="whatsapp_mensagem">Mensagem inicial <span style="font-weight:400;color:var(--mudo)">(opcional)</span></label>
                <input type="text" id="whatsapp_mensagem" name="whatsapp_mensagem" maxlength="200" value="<?= e($v['whatsapp_mensagem']) ?>" placeholder="Olá! Gostaria de fazer um pedido.">
                <span class="dica">Já vem escrita quando o cliente abre a conversa.</span>
            </div>
        </div>

        <div class="campo" style="margin-bottom:0">
            <label for="link_pedido">Link para pedir online</label>
            <input type="url" id="link_pedido" name="link_pedido" value="<?= e($v['link_pedido']) ?>" placeholder="https://pedido.anota.ai/...">
            <span class="dica">Botão "Pedir agora". Se deixar vazio, o botão some.</span>
        </div>
    </div>

    <div class="cartao" id="redes">
        <h2><i class="fas fa-share-nodes"></i> Redes sociais</h2>
        <p class="ajuda">Se ficar vazio, o ícone não aparece no site.</p>

        <div class="grade-2">
            <div class="campo" style="margin-bottom:0">
                <label for="instagram_url">Instagram</label>
                <input type="text" id="instagram_url" name="instagram_url" value="<?= e($v['instagram_url']) ?>" placeholder="@churrascodobaiano" autocapitalize="none">
            </div>
            <div class="campo" style="margin-bottom:0">
                <label for="facebook_url">Facebook</label>
                <input type="text" id="facebook_url" name="facebook_url" value="<?= e($v['facebook_url']) ?>" placeholder="https://facebook.com/..." autocapitalize="none">
            </div>
        </div>
    </div>

    <div class="cartao" id="horarios">
        <h2><i class="fas fa-clock"></i> Horário de funcionamento</h2>
        <p class="ajuda">Aparece nas páginas Localização e Contato. Desligue o dia para mostrar "Fechado".</p>

<?php foreach (DIAS_SEMANA as $k => $nomeDia): $h = $horariosForm[$k]; ?>
        <div class="horario<?= $h['aberto'] ? '' : ' fechado' ?>">
            <strong><?= e($nomeDia) ?></strong>
            <label class="interruptor">
                <input type="checkbox" name="h_<?= $k ?>_aberto" value="1"<?= $h['aberto'] ? ' checked' : '' ?>>
                <span class="trilho"></span>
                <span><strong style="font-size:.85rem">Aberto</strong></span>
            </label>
            <input type="time" name="h_<?= $k ?>_abre" value="<?= e($h['abre']) ?>" aria-label="Abre — <?= e($nomeDia) ?>">
            <input type="time" name="h_<?= $k ?>_fecha" value="<?= e($h['fecha']) ?>" aria-label="Fecha — <?= e($nomeDia) ?>">
        </div>
<?php endforeach; ?>
    </div>

    <div class="cartao" id="endereco">
        <h2><i class="fas fa-location-dot"></i> Endereço e mapa</h2>
        <p class="ajuda">Página Localização e rodapé.</p>

        <div class="campo">
            <label for="endereco_campo">Endereço completo *</label>
            <textarea id="endereco_campo" name="endereco" maxlength="200" style="min-height:76px"><?= e($v['endereco']) ?></textarea>
        </div>

        <div class="grade-2">
            <div class="campo">
                <label for="bairro_destaque">Bairro (selo no topo da página)</label>
                <input type="text" id="bairro_destaque" name="bairro_destaque" maxlength="60" value="<?= e($v['bairro_destaque']) ?>">
            </div>
            <div class="campo">
                <label for="cidade_rodape">Cidade no rodapé</label>
                <input type="text" id="cidade_rodape" name="cidade_rodape" maxlength="80" value="<?= e($v['cidade_rodape']) ?>">
            </div>
        </div>

        <div class="campo">
            <label for="mapa_embed_url">Mapa incorporado (Google Maps)</label>
            <textarea id="mapa_embed_url" name="mapa_embed_url" style="min-height:76px" placeholder='Cole aqui o código <iframe ...> ou apenas o link do mapa'><?= e($v['mapa_embed_url']) ?></textarea>
            <span class="dica">No Google Maps: abra o local → Compartilhar → <em>Incorporar um mapa</em> → Copiar HTML. Se deixar vazio, o mapa some.</span>
        </div>

        <div class="grade-2">
            <div class="campo" style="margin-bottom:0">
                <label for="link_maps">Botão "Abrir no Google Maps"</label>
                <input type="url" id="link_maps" name="link_maps" value="<?= e($v['link_maps']) ?>" placeholder="https://maps.app.goo.gl/...">
            </div>
            <div class="campo" style="margin-bottom:0">
                <label for="link_waze">Botão "Abrir no Waze"</label>
                <input type="url" id="link_waze" name="link_waze" value="<?= e($v['link_waze']) ?>" placeholder="https://waze.com/ul/...">
            </div>
        </div>
    </div>

    <div class="cartao" id="textos">
        <h2><i class="fas fa-pen-to-square"></i> Textos do site</h2>
        <p class="ajuda">Para separar parágrafos, deixe uma linha em branco.</p>

        <div class="grade-2">
            <div class="campo">
                <label for="hero_subtitulo">Selo da página inicial</label>
                <input type="text" id="hero_subtitulo" name="hero_subtitulo" maxlength="40" value="<?= e($v['hero_subtitulo']) ?>" placeholder="DESDE 2020">
            </div>
        </div>
        <div class="campo">
            <label for="hero_texto">Texto da página inicial</label>
            <textarea id="hero_texto" name="hero_texto" maxlength="300" style="min-height:90px"><?= e($v['hero_texto']) ?></textarea>
        </div>
        <div class="campo">
            <label for="obs_cardapio">Aviso no fim do cardápio</label>
            <textarea id="obs_cardapio" name="obs_cardapio" maxlength="300" style="min-height:76px"><?= e($v['obs_cardapio']) ?></textarea>
            <span class="dica">Se deixar vazio, o aviso não aparece.</span>
        </div>
        <div class="campo">
            <label for="sobre_historia">Sobre nós — Nossa história</label>
            <textarea id="sobre_historia" name="sobre_historia" maxlength="1500" style="min-height:140px"><?= e($v['sobre_historia']) ?></textarea>
        </div>
        <div class="grade-3">
            <div class="campo" style="margin-bottom:0">
                <label for="sobre_missao">Missão</label>
                <textarea id="sobre_missao" name="sobre_missao" maxlength="400" style="min-height:110px"><?= e($v['sobre_missao']) ?></textarea>
            </div>
            <div class="campo" style="margin-bottom:0">
                <label for="sobre_visao">Visão</label>
                <textarea id="sobre_visao" name="sobre_visao" maxlength="400" style="min-height:110px"><?= e($v['sobre_visao']) ?></textarea>
            </div>
            <div class="campo" style="margin-bottom:0">
                <label for="sobre_valores">Valores</label>
                <textarea id="sobre_valores" name="sobre_valores" maxlength="400" style="min-height:110px"><?= e($v['sobre_valores']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="cartao" id="fachada">
        <h2><i class="fas fa-store"></i> Foto da fachada</h2>
        <p class="ajuda">Aparece no topo das páginas Sobre Nós, Localização e Contato, e na seção "Sobre nós".</p>

        <div class="envio-foto">
            <div class="previa" id="previa-fachada" style="width:170px;height:110px">
                <img src="<?= e(foto_src($atual['foto_fachada'])) ?>" alt="Fachada atual">
            </div>
            <div style="flex:1;min-width:220px">
                <input type="file" name="fachada" accept="image/jpeg,image/png,image/webp" data-previa="#previa-fachada">
<?php if ($atual['foto_fachada'] !== 'img/fachada.jpg'): ?>
                <label class="interruptor" style="margin:12px 0 0">
                    <input type="checkbox" name="restaurar_fachada" value="1">
                    <span class="trilho"></span>
                    <span><strong>Voltar para a foto original</strong></span>
                </label>
<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="barra-salvar">
        <button class="btn btn-primario" type="submit"><i class="fas fa-floppy-disk"></i> Salvar tudo</button>
    </div>
</form>

<?php painel_fechar();
