# Churrasco do Baiano Sem Miséria

Site do restaurante, com cardápio, galeria, localização e contato, e um **painel de administração** (`/admin`) para editar preços, nomes, fotos, horários e textos sem mexer no código.

**Tecnologias:** PHP 8.1+, MySQL, HTML, CSS e JavaScript.

## Como colocar no ar
Siga o passo a passo em [`docs/INSTALACAO.md`](docs/INSTALACAO.md).

## Pastas
| Pasta | O que tem |
| --- | --- |
| `paginas/` | código das páginas públicas (as entradas ficam na raiz) |
| `admin/` | painel de administração |
| `includes/` | banco de dados, login, upload e funções comuns |
| `install/` | instalador (roda uma vez) |
| `database/` | estrutura das tabelas e o cardápio inicial |
| `styles/`, `scripts/`, `img/` | visual, comportamentos e fotos |
| `docs/` | documentação |
| `backup-site-antigo/` | o site como era antes do painel |
