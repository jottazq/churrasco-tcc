<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

admin_cabecalhos();

// Sair só funciona por POST com token (evita que outro site te deslogue sem querer).
if (eh_post()) {
    csrf_exigir();
    logout_executar();
}
redirecionar('login.php');
