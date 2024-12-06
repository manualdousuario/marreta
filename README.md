# 🛠️ Marreta

[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)

Marreta é uma ferramenta para analisar URLs e acessar conteúdo na web sem dor de cabeça.

## ✨ O que tem de legal?

- Limpa e arruma URLs automaticamente
- Remove parâmetros chatos de rastreamento
- Força HTTPS pra manter tudo seguro
- Troca de user agent pra evitar bloqueios
- DNS esperto
- Deixa o HTML limpinho e otimizado
- Conserta URLs relativas sozinho
- Permite colocar seus próprios estilos
- Remove elementos indesejados
- Cache, cache!
- Bloqueia domínios que você não quer
- Permite configurar headers e cookies do seu jeito
- Tudo com SSL/TLS
- PHP-FPM
- OPcache ligado

## 🐳 Docker

### Antes de começar

Só precisa ter instalado:
- Docker e docker compose

### Produção

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

Se necessario

`nano docker-compose.yml`

```
services:
  marreta:
    container_name: marreta
    image: ghcr.io/manualdousuario/marreta/marreta:latest
    ports:
      - "80:80"
    environment:
      - SITE_NAME=
      - SITE_DESCRIPTION=
      - SITE_URL=
      - DNS_SERVERS=
```

- `SITE_NAME`: Nome do seu Marreta
- `SITE_DESCRIPTION`: Conta pra que serve
- `SITE_URL`: Onde vai rodar, endereço completo com `https://`
- `DNS_SERVERS`: Quais servidores DNS usar `1.1.1.1, 8.8.8.8`

Agora pode rodar `docker compose up -d`

#### Desenvolvimento

1. Primeiro, clona o projeto:
```bash
git clone https://github.com/manualdousuario/marreta/
cd marreta
```

2. Cria o arquivo de configuração:
```bash
cp app/.env.sample app/.env
```

3. Configura do seu jeito no `app/.env`:
```env
SITE_NAME="Marreta"
SITE_DESCRIPTION="Chapéu de paywall é marreta!"
SITE_URL=http://localhost
DNS_SERVERS=1.1.1.1, 8.8.8.8
DISABLE_CACHE=true
```

4. Roda tudo:
```bash
docker-compose up -d
```

Pronto! Vai estar rodando em `http://localhost` 🎉

A opção de `DISABLE_CACHE` quando `true` não irá gerar cache!

## ⚙️ Personalizando

As configurações estão organizadas em `data/`:

- `domain_rules.php`: Regras específicas para cada site
- `global_rules.php`: Regras que se aplicam a todos os sites
- `blocked_domains.php`: Lista de sites bloqueados
- `user_agents.php`: Configurações de User Agents
- `messages.php`: Mensagens do sistema

### Cache S3

Suporte de armazenamento do cache em S3. Configure as seguintes variáveis no seu `.env`:

```env
S3_CACHE_ENABLED=true

S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_REGION=us-east-1
S3_FOLDER_=cache/
S3_ACL=private
S3_ENDPOINT=
```

Configurações possiveis:

```
## R2
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_ENDPOINT=https://{TOKEN}.r2.cloudflarestorage.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private

## DigitalOcean
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_ENDPOINT=https://{REGIAO}.digitaloceanspaces.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private
```

## 🛠️ Manutenção

### Logs

Ver o que tá acontecendo:
```bash
docker-compose logs app
```

As requisições de erro relacionadas a consulta de sites são integradas ao [LogOwl](https://logowl.io/), uma ferramenta de analise de erros com uma cota gratuita mensal de 5.000 registros.

Para habilitar, basta definir no environment:

```
LOGOWL_ENABLED=true
LOGOWL_TICKET=secret_key
```

### Limpando o cache

Quando precisar limpar:
```bash
docker-compose exec app rm -rf /app/cache/*
```

---

Feito com ❤️! Se tiver dúvidas ou sugestões, abre uma issue que a gente ajuda! 😉

Agradecimento ao projeto [Burlesco](https://github.com/burlesco/burlesco) que serviu de base para varias regras!

Instancia publica em [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!
