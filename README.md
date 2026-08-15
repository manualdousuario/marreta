# 🛠️ Marreta

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-purple.svg)](https://www.php.net/)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com/)

[![Forks](https://img.shields.io/github/forks/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/network/members)
[![Stars](https://img.shields.io/github/stars/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/stargazers)
[![Issues](https://img.shields.io/github/issues/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/issues)

Marreta é uma ferramenta que quebra barreiras de acesso e elementos que atrapalham a leitura!

![Antes e depois do Marreta](https://github.com/manualdousuario/marreta/blob/main/screen.png?raw=true)

Instancia publica em [marreta.link](https://marreta.link)!

## ✨ O que tem de legal?

- Limpa e corrige URLs automaticamente
- Remove parâmetros chatos de rastreamento
- Força HTTPS pra manter tudo seguro
- Deixa o HTML limpinho e otimizado
- Conserta URLs relativas sozinho
- Permite colocar seus próprios estilos e scripts
- Remove elementos indesejados
- Cache, cache!
- Bloqueia domínios que você não quer
- Proteção DMCA com mensagens personalizadas
- Permite configurar headers e cookies do seu jeito
- PHP-FPM e OPcache
- Suporte a Proxy

## 🐳 Instalando em Docker

Instale [Docker e Docker Compose](https://docs.docker.com/engine/install/)

`curl -o ./compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/compose.yml`

Agora modifique com suas preferencias:

`nano compose.yml`

- `APP_NAME`: Nome do seu Marreta
- `APP_DESCRIPTION`: Conta pra que serve
- `APP_URL`: Onde vai rodar, endereço completo com `https://`. O container serve HTTP na porta `8080` e o compose.yml publica ela em `81` no host; se você mudar isso (ex: `8080:8080`), inclua a porta também no APP_URL (ex: https://seusite:8080)
- `APP_LOCALE`: pt-br (Português Brasil), en (Inglês), es (Espanhol) ou de-de (Alemão), ru-ru (Russo)
- `APP_KEY`: opcional. Se ficar vazio, uma chave é gerada no primeiro boot e guardada no volume
- `DISABLE_CACHE`: opcional. O cache de páginas vem ligado por padrão; coloque `true` para desligar
- `ADMIN_EMAIL`: admin@marreta.local
- `ADMIN_PASSWORD`: password

Agora só rodar `docker compose up -d`

## ⚠️ Breaking changes: migrando da 2.x pra 3.x

A partir da 3.0.0 o Marreta virou uma aplicação Laravel. É uma reescrita completa do zero, então não existe caminho de upgrade "no lugar", o recomendado é subir os containers novos e reconfigurar o essencial.

### O que mudou

### Variáveis de ambiente

| Antes (2.x)                      | Agora (3.x)                       | Observação |
|----------------------------------|-----------------------------------|------------|
| `SITE_NAME`                      | `APP_NAME`                        | |
| `SITE_DESCRIPTION`               | `APP_DESCRIPTION`                 | |
| `SITE_URL`                       | `APP_URL`                         | |
| `LANGUAGE`                       | `APP_LOCALE`                      | |
| `DEBUG`                          | `APP_DEBUG`                       | |
| —                                | `APP_KEY`                         | Novo e obrigatório. Se deixar vazio, é gerada uma chave automaticamente no primeiro boot |
| `SELENIUM_HOST`                  | `BROWSER_WS_ENDPOINT`             | Agora aponta pro Lightpanda (`ws://marreta_browser:9222`), não mais pro Selenium |
| `DNS_SERVERS`                    | *(removido)*                      | |
| `CLEANUP_DAYS`                   | *(removido)*                      | |
| `LOG_LEVEL` / `LOG_DAYS_TO_KEEP` | *(removido)*                      | |
| `S3_CACHE_ENABLED` / `S3_*`      | *(removido)*                      | |
| —                                | `ADMIN_EMAIL` / `ADMIN_PASSWORD`  | Novo. Credenciais de login do painel `/admin` |

### Docker

- Porta interna do container mudou de `80` pra `8080` (o `compose.yml` já publica em `81:8080` no host, ajuste se você tinha `80:80` mapeado).
- Os bind mounts antigos (`./app/cache` e `./app/logs`) deixaram de existir. Agora tudo (banco SQLite + cache) mora num volume nomeado, `marreta_storage`, montado em `/var/www/html/storage/app`.

### Passo a passo pra migrar

1. Antes de derrubar os containers antigos, anote as customizações que você tenha feito em `app/data/domain_rules.php`, `app/data/blocked_domains.php` e `app/data/global_rules.php`, e também os domínios cadastrados em `app/cache/dmca_domains.json`. Nada disso é migrado automaticamente.
2. Baixe o novo `compose.yml`, `APP_KEY` (gere uma em [laravel-encryption-key-generator.vercel.app](https://laravel-encryption-key-generator.vercel.app)), `ADMIN_EMAIL` e `ADMIN_PASSWORD`.
3. Suba os containers com `docker compose up -d`. No primeiro boot, as migrations rodam e o banco é populado com o conjunto padrão de regras/domínios bloqueados que vêm com o Marreta.
4. Acesse `SEU_DOMINIO/admin`, entre com `ADMIN_EMAIL`/`ADMIN_PASSWORD` e recadastre manualmente: suas regras de domínio customizadas, domínios bloqueados extras e domínios com DMCA que você tinha anotado no passo 1.

## 🚀 Integrações

- 🤖 **Telegram**: [Bot oficial](https://t.me/leissoai_bot)
- 🦊 **Firefox**: Extensão por [Clarissa Mendes](https://claromes.com/pages/whoami) - [Baixar](https://addons.mozilla.org/pt-BR/firefox/addon/marreta/) | [Código fonte](https://github.com/manualdousuario/marreta-extensao)
- 🌀 **Chrome**: Extensão por [Clarissa Mendes](https://claromes.com/pages/whoami) - [Baixar](https://chromewebstore.google.com/detail/marreta/ipelapagohjgjcgpncpbmaaacemafppe) | [Código fonte](https://github.com/manualdousuario/marreta-extensao)
- 🦋 **Bluesky**: Bot por [Joselito](https://bsky.app/profile/joseli.to) - [Perfil](https://bsky.app/profile/marreta.link) | [Código fonte](https://github.com/manualdousuario/marreta-bot)
- 🍎 **Apple**: Integração ao [Atalhos](https://www.icloud.com/shortcuts/3594074b69ee4707af52ed78922d624f)

---

Feito com ❤️! Se tiver dúvidas ou sugestões, abre uma issue que a gente ajuda! 😉

Agradecimento ao projeto [Burlesco](https://github.com/burlesco/burlesco) e [Hover](https://github.com/nang-dev/hover-paywalls-browser-extension/) que serviu de base para varias regras!

## Star History

[![Star History Chart](https://star-history.dera.page/svg?repos=manualdousuario/marreta&type=Date)](https://star-history.dera.page/#manualdousuario/marreta&Date)
