-- ============================================================
--  Churrasco do Baiano Sem Miséria — estrutura do banco (MySQL)
--  O instalador (install/index.php) já roda este arquivo sozinho.
--  Só use manualmente (phpMyAdmin > Importar) se preferir.
-- ============================================================

CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario       VARCHAR(40)  NOT NULL,
    nome          VARCHAR(80)  NOT NULL,
    senha_hash    VARCHAR(255) NOT NULL,
    criado_em     DATETIME     NOT NULL,
    ultimo_login  DATETIME     NULL,
    UNIQUE KEY uq_admins_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_tentativas (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45)  NOT NULL,
    usuario    VARCHAR(40)  NOT NULL,
    criado_em  DATETIME     NOT NULL,
    KEY idx_tentativas_ip (ip, criado_em),
    KEY idx_tentativas_usuario (usuario, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(60)  NOT NULL,
    nome       VARCHAR(80)  NOT NULL,
    nome_menu  VARCHAR(40)  NOT NULL,
    descricao  VARCHAR(160) NOT NULL DEFAULT '',
    icone      VARCHAR(40)  NOT NULL DEFAULT 'fa-utensils',
    estilo     VARCHAR(10)  NOT NULL DEFAULT 'completo',
    ordem      INT          NOT NULL DEFAULT 0,
    ativa      TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(120) NOT NULL,
    descricao     VARCHAR(400) NOT NULL DEFAULT '',
    preco         DECIMAL(8,2) NOT NULL DEFAULT 0,
    foto          VARCHAR(255) NULL,
    destaque      TINYINT(1)   NOT NULL DEFAULT 0,
    esgotado      TINYINT(1)   NOT NULL DEFAULT 0,
    ativo         TINYINT(1)   NOT NULL DEFAULT 1,
    ordem         INT          NOT NULL DEFAULT 0,
    atualizado_em DATETIME     NOT NULL,
    KEY idx_itens_categoria (categoria_id, ordem),
    CONSTRAINT fk_itens_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS galeria_fotos (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    arquivo  VARCHAR(255) NOT NULL,
    legenda  VARCHAR(120) NOT NULL DEFAULT '',
    ordem    INT          NOT NULL DEFAULT 0,
    ativa    TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS banners (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    arquivo   VARCHAR(255) NOT NULL,
    texto_alt VARCHAR(120) NOT NULL DEFAULT '',
    ordem     INT          NOT NULL DEFAULT 0,
    ativo     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave  VARCHAR(60) NOT NULL PRIMARY KEY,
    valor  TEXT        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
