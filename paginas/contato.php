<?php
declare(strict_types=1);

// Código da página. A entrada pública é o arquivo de mesmo nome na raiz do site.
defined('ENTRADA_PAGINA') || exit;

require_once __DIR__ . '/../includes/layout.php';

pub_inicio(
    'Contato - ' . cfg('site_nome'),
    'contato',
    ['styles/style-contato.css'],
    'Fale com o ' . cfg('site_nome') . ': pedidos, dúvidas e informações.',
    ['https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js']
);

pub_topo(
    cfg('foto_fachada'),
    'Fachada ' . cfg('site_nome'),
    'Contato',
    'ESTAMOS À DISPOSIÇÃO',
    'FALE CONOSCO',
    'Faça seu pedido ou tire suas dúvidas com a gente.',
    [['fa-bolt', 'Resposta rápida'], ['fa-headset', 'Atendimento humanizado']]
);
?>
<!-- ================= CANAIS DE CONTATO ================= -->

<section class="pagina-contato">

    <div class="container">

        <div class="contato-grid<?= cfg('instagram_url') === '' ? ' dois' : '' ?>">

            <div class="contato-card">
                <div class="icon-badge"><i class="fab fa-whatsapp"></i></div>
                <h3>WhatsApp</h3>
                <p>Faça seu pedido de forma rápida.</p>
                <a href="<?= e(wa_link()) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Chamar no WhatsApp</a>
            </div>

<?php if (cfg('instagram_url') !== ''): ?>
            <div class="contato-card">
                <div class="icon-badge"><i class="fab fa-instagram"></i></div>
                <h3>Instagram</h3>
                <p>Veja nossas novidades e promoções.</p>
                <a href="<?= e(cfg('instagram_url')) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Acessar Instagram</a>
            </div>
<?php endif; ?>

            <div class="contato-card">
                <div class="icon-badge"><i class="fas fa-clock"></i></div>
                <h3>Funcionamento</h3>
                <p>
<?php foreach (horarios_agrupados() as $g): ?>
                    <?= e($g['dias']) ?>: <?= e($g['texto']) ?><br>
<?php endforeach; ?>
                </p>
            </div>

        </div>

        <!-- ================= FORMULÁRIO ================= -->

        <div class="formulario-card">

            <div class="categoria-header">
                <div class="icon-badge"><i class="fas fa-envelope"></i></div>
                <h2>Envie uma mensagem</h2>
            </div>

            <form id="form-contato">

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Seu nome"
                    required
                >

                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    placeholder="Seu telefone"
                    required
                >

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Seu e-mail"
                    required
                >

                <textarea
                    id="mensagem"
                    name="mensagem"
                    placeholder="Digite sua mensagem"
                    required></textarea>

                <button type="submit" class="btn btn-primary">
                    Enviar mensagem
                </button>

            </form>

        </div>

    </div>

</section>

<?php
pub_fim(['scripts/script.js', 'scripts/script-contato.js']);
