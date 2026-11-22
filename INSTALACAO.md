# Painel de administração — guia de instalação (Hostinger)

Este guia leva uns 10 minutos. Você só faz isso **uma vez**.

## O que você vai ter

Um painel em `seusite.com.br/admin`, protegido por usuário e senha, onde dá para:

- mudar **preços**, **nomes**, **descrições** e **fotos** de cada item do cardápio
- marcar item como **esgotado**, **esconder** do site ou colocar em **destaque** ("Mais pedido")
- criar, reordenar e esconder **categorias** (ex.: Sobremesas)
- enviar as **fotos do ambiente** (galeria) e as **fotos do banner** da página inicial
- editar **WhatsApp, Instagram, Facebook, endereço, mapa, horários** e os **textos** do site
- cadastrar outros **usuários** (um funcionário, por exemplo) e trocar sua senha

O site continua com o mesmo visual. A diferença é que cardápio, galeria e textos agora vêm do banco de dados.

---

## Antes de começar

No painel da Hostinger (hPanel), em **Avançado → Configuração do PHP**, confira que o PHP é **8.1 ou mais novo** (recomendo 8.2 ou 8.3). As extensões `pdo_mysql`, `gd` e `mbstring` já vêm ligadas por padrão.

## Passo 1 — Criar o banco de dados

1. No hPanel, vá em **Bancos de Dados → Gerenciamento**.
2. Em **Criar um novo banco de dados MySQL e um usuário**, preencha o nome do banco, o usuário e uma **senha forte**.
3. Clique em **Criar** e **anote** os três dados (nome do banco, usuário, senha). Todos começam com `u` + números, por exemplo `u123456789_churrasco`.

## Passo 2 — Enviar os arquivos

Envie todo o conteúdo do pacote para a pasta **public_html** (ou para a pasta do seu site), **substituindo** os arquivos antigos. Pode ser pelo **Gerenciador de Arquivos** (envie o ZIP e use "Extrair") ou pelo Git, se você já usa.

> As páginas antigas (`.html`) foram trocadas por `.php`. Quem abrir um link antigo, como `cardapio.html`, é levado sozinho para a página nova.

## Passo 3 — Rodar o instalador

1. Abra `https://seusite.com.br/install` no navegador.
2. **Passo 1 de 2:** informe os dados do banco que você anotou. O servidor (host) quase sempre é `localhost`.
3. **Passo 2 de 2:** escolha **seu nome, usuário e senha** do painel. Use uma senha forte, com no mínimo 8 caracteres.
4. Clique em **Instalar**. O cardápio atual (34 itens) é importado automaticamente.

## Passo 4 — Apagar a pasta `install`

O instalador tenta se apagar sozinho. Mesmo assim, confira no Gerenciador de Arquivos se a pasta **`install`** sumiu. Se não sumiu, apague. (Enquanto existir um administrador, ela se recusa a rodar de novo, mas o certo é não deixar sobrando.)

## Pronto: entrar no painel

Acesse `https://seusite.com.br/admin` e entre com o usuário e a senha que você criou.

---

## Dicas de uso

- **Mudar um preço:** *Cardápio* → digite o novo valor no campo do item → toque no ✓.
- **Trocar nome ou foto:** *Cardápio* → botão **Editar** no item.
- **Item acabou hoje:** botão 🚫 (esgotado). No site aparece "Esgotado" no lugar do preço. Toque de novo para liberar.
- **Fotos:** podem ser tiradas direto do celular (JPG, PNG ou WEBP). O painel reduz e otimiza sozinho.
- **Itens sem foto:** na aba **Sem foto** do cardápio você vê todos que ainda faltam.

## Segurança

- A senha é guardada criptografada. Depois de 5 tentativas erradas, o login fica bloqueado por 15 minutos.
- A sessão expira sozinha após 3 horas sem uso. Use **Sair** em computadores compartilhados.
- Ative o **SSL (HTTPS)** gratuito no hPanel, para a senha trafegar protegida.
- O arquivo `includes/config.php` guarda a senha do banco. Ele é criado pelo instalador e **nunca** vai para o GitHub (está no `.gitignore`).
- Faça **backup** de vez em quando: no hPanel, em **Backups**, ou exporte o banco pelo **phpMyAdmin**. As fotos enviadas pelo painel ficam em `img/uploads`.

## Se algo der errado

| Problema | O que fazer |
| --- | --- |
| "Usuário ou senha do banco incorretos" no instalador | Confira os dados no hPanel, em Bancos de Dados. Copie e cole, sem espaços. |
| Tela "Ops, algo deu errado" | Confira se o PHP está em 8.1 ou mais novo. |
| Foto não sobe | A foto precisa ter até 15 MB. Se a mensagem falar do servidor, reduza a foto. |
| Esqueci a senha | Se houver outro usuário, ele troca em *Usuários*. Se não houver, é preciso redefinir pelo phpMyAdmin (tabela `admins`). |

## Para quem for mexer no código

- `admin/` — as telas do painel · `includes/` — banco, login, upload e funções comuns
- `database/schema.sql` — estrutura das tabelas · `database/seed-cardapio.php` — cardápio inicial
- `index.php`, `cardapio.php`, `galeria.php`, `sobre-nos.php`, `localizacao.php`, `contato.php` — páginas públicas
- Todas as consultas ao banco usam parâmetros (sem SQL montado na mão) e todo texto é escapado ao ser exibido.
