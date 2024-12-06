# 🛠️ Marreta

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

Marreta is a tool for analyzing URLs and accessing web content without hassle.

## ✨ Features

- Automatically cleans and fixes URLs
- Removes annoying tracking parameters
- Forces HTTPS to keep everything secure
- Changes user agent to avoid blocks
- Smart DNS
- Keeps HTML clean and optimized
- Fixes relative URLs automatically
- Allows custom styles
- Removes unwanted elements
- Cache, cache!
- Blocks domains you don't want
- Allows custom headers and cookies configuration
- Everything with SSL/TLS
- PHP-FPM
- OPcache enabled

## 🐳 Docker

### Prerequisites

You only need:
- Docker and docker compose

### Production

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

If needed

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

- `SITE_NAME`: Your Marreta's name
- `SITE_DESCRIPTION`: Tell what it's for
- `SITE_URL`: Where it will run, full address with `https://`
- `DNS_SERVERS`: Which DNS servers to use `1.1.1.1, 8.8.8.8`

Now you can run `docker compose up -d`

#### Development

1. First, clone the project:
```bash
git clone https://github.com/manualdousuario/marreta/
cd marreta
```

2. Create the configuration file:
```bash
cp app/.env.sample app/.env
```

3. Configure it your way in `app/.env`:
```env
SITE_NAME="Marreta"
SITE_DESCRIPTION="Paywall hammer!"
SITE_URL=http://localhost
DNS_SERVERS=1.1.1.1, 8.8.8.8
DISABLE_CACHE=true
```

4. Run everything:
```bash
docker-compose up -d
```

Done! It will be running at `http://localhost` 🎉

The `DISABLE_CACHE` option when `true` will not generate cache!

## ⚙️ Customization

The configurations are organized in `data/`:

- `domain_rules.php`: Site-specific rules
- `global_rules.php`: Rules that apply to all sites
- `blocked_domains.php`: List of blocked sites
- `user_agents.php`: User Agents configurations
- `messages.php`: System messages

### S3 Cache

Cache storage support in S3. Configure the following variables in your `.env`:

```env
S3_CACHE_ENABLED=true

S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_REGION=us-east-1
S3_FOLDER_=cache/
S3_ACL=private
S3_ENDPOINT=
```

Possible configurations:

```
## R2
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_ENDPOINT=https://{TOKEN}.r2.cloudflarestorage.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private

## DigitalOcean
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_ENDPOINT=https://{REGION}.digitaloceanspaces.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private
```

## 🛠️ Maintenance

### Logs

See what's happening:
```bash
docker-compose logs app
```

Error requests related to website queries are integrated with [LogOwl](https://logowl.io/), an error analysis tool with a free monthly quota of 5,000 records.

To enable this, simply set the following in the environment:

```
LOGOWL_ENABLED=true
LOGOWL_TICKET=secret_key
```

### Clearing the cache

When you need to clear:
```bash
docker-compose exec app rm -rf /app/cache/*
```

---

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉

Thanks to the project [Burlesco](https://github.com/burlesco/burlesco) which served as the basis for several rules!

Public instance at [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!
