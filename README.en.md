# 🛠️ Marreta

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-purple.svg)](https://www.php.net/)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com/)

[![Forks](https://img.shields.io/github/forks/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/network/members)
[![Stars](https://img.shields.io/github/stars/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/stargazers)
[![Issues](https://img.shields.io/github/issues/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/issues)

Marreta is a tool that breaks down access barriers and elements that get in the way of reading!

![Before and after Marreta](https://github.com/manualdousuario/marreta/blob/main/screen.png?raw=true)

Public instance at [marreta.link](https://marreta.link)!

## ✨ What's cool about it?

- Automatically cleans up and fixes URLs
- Removes annoying tracking parameters
- Forces HTTPS to keep everything secure
- Leaves the HTML clean and optimized
- Fixes relative URLs on its own
- Lets you add your own styles and scripts
- Removes unwanted elements
- Cache, cache!
- Blocks domains you don't want
- DMCA protection with custom messages
- Lets you configure headers and cookies your way
- PHP-FPM and OPcache
- Proxy support

## 🐳 Installing with Docker

Install [Docker and Docker Compose](https://docs.docker.com/engine/install/)

`curl -o ./compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/compose.yml`

Now edit it to your liking:

`nano compose.yml`

- `APP_NAME`: Your Marreta's name
- `APP_DESCRIPTION`: What it's for
- `APP_URL`: Where it will run, full address with `https://`. The container serves HTTP on port `8080` and compose.yml publishes it on `81` on the host; if you change that (e.g. `8080:8080`), include the port in APP_URL too (e.g. https://yoursite:8080)
- `APP_LOCALE`: pt-br (Brazilian Portuguese), en (English), es (Spanish), de-de (German), or ru-ru (Russian)
- `APP_KEY`: optional. If left empty, a key is generated on first boot and stored in the volume
- `DISABLE_CACHE`: optional. Page caching is on by default; set `true` to turn it off
- `ADMIN_EMAIL`: admin@marreta.local
- `ADMIN_PASSWORD`: password

Now just run `docker compose up -d`

## ⚠️ Breaking changes: migrating from 2.x to 3.x

Starting with 3.0.0, Marreta became a Laravel application. It's a complete rewrite from scratch, so there's no "in-place" upgrade path; the recommendation is to spin up new containers and reconfigure the essentials.

### What changed

### Environment variables

| Before (2.x)                     | Now (3.x)                         | Note |
|----------------------------------|-----------------------------------|------------|
| `SITE_NAME`                      | `APP_NAME`                        | |
| `SITE_DESCRIPTION`               | `APP_DESCRIPTION`                 | |
| `SITE_URL`                       | `APP_URL`                         | |
| `LANGUAGE`                       | `APP_LOCALE`                      | |
| `DEBUG`                          | `APP_DEBUG`                       | |
| —                                | `APP_KEY`                         | New and required. If left empty, a key is generated automatically on first boot |
| `SELENIUM_HOST`                  | `BROWSER_WS_ENDPOINT`             | Now points to Lightpanda (`ws://marreta_browser:9222`), no longer Selenium |
| `DNS_SERVERS`                    | *(removed)*                       | |
| `CLEANUP_DAYS`                   | *(removed)*                       | |
| `LOG_LEVEL` / `LOG_DAYS_TO_KEEP` | *(removed)*                       | |
| `S3_CACHE_ENABLED` / `S3_*`      | *(removed)*                       | |
| —                                | `ADMIN_EMAIL` / `ADMIN_PASSWORD`  | New. Login credentials for the `/admin` panel |

### Docker

- The container's internal port changed from `80` to `8080` (compose.yml now publishes it as `81:8080` on the host; adjust if you had `80:80` mapped).
- The old bind mounts (`./app/cache` and `./app/logs`) no longer exist. Everything (SQLite database + cache) now lives in a named volume, `marreta_storage`, mounted at `/var/www/html/storage/app`.

### Step-by-step migration guide

1. Before tearing down the old containers, note down any customizations you've made in `app/data/domain_rules.php`, `app/data/blocked_domains.php`, and `app/data/global_rules.php`, as well as the domains registered in `app/cache/dmca_domains.json`. None of this is migrated automatically.
2. Download the new `compose.yml`, generate an `APP_KEY` (at [laravel-encryption-key-generator.vercel.app](https://laravel-encryption-key-generator.vercel.app)), and set `ADMIN_EMAIL` and `ADMIN_PASSWORD`.
3. Start the containers with `docker compose up -d`. On first boot, migrations run and the database is populated with the default set of rules/blocked domains that ship with Marreta.
4. Go to `YOUR_DOMAIN/admin`, log in with `ADMIN_EMAIL`/`ADMIN_PASSWORD`, and manually re-register: your custom domain rules, extra blocked domains, and DMCA domains you noted down in step 1.

## 🚀 Integrations

- 🤖 **Telegram**: [Official bot](https://t.me/leissoai_bot)
- 🦊 **Firefox**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://addons.mozilla.org/pt-BR/firefox/addon/marreta/) | [Source code](https://github.com/manualdousuario/marreta-extensao)
- 🌀 **Chrome**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://chromewebstore.google.com/detail/marreta/ipelapagohjgjcgpncpbmaaacemafppe) | [Source code](https://github.com/manualdousuario/marreta-extensao)
- 🦋 **Bluesky**: Bot by [Joselito](https://bsky.app/profile/joseli.to) - [Profile](https://bsky.app/profile/marreta.link) | [Source code](https://github.com/manualdousuario/marreta-bot)
- 🍎 **Apple**: [Shortcuts](https://www.icloud.com/shortcuts/3594074b69ee4707af52ed78922d624f) integration

---

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉

Thanks to the [Burlesco](https://github.com/burlesco/burlesco) and [Hover](https://github.com/nang-dev/hover-paywalls-browser-extension/) projects, which served as a basis for several rules!

## Star History

[![Star History Chart](https://star-history.dera.page/svg?repos=manualdousuario/marreta&type=Date)](https://star-history.dera.page/#manualdousuario/marreta&Date)
