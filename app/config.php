<?php

/**
 * Arquivo de configuração principal
 * 
 * Este arquivo contém todas as configurações globais do sistema, incluindo:
 * - Carregamento de variáveis de ambiente
 * - Definições de constantes do sistema
 * - Configurações de segurança
 * - Mensagens do sistema
 * - Configurações de bots e user agents
 * - Lista de domínios bloqueados
 * - Configurações de cache S3
 */

require_once __DIR__ . '/vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Configurações básicas do sistema
 */
define('SITE_NAME', isset($_ENV['SITE_NAME']) ? $_ENV['SITE_NAME'] : 'Marreta');
define('SITE_DESCRIPTION', isset($_ENV['SITE_DESCRIPTION']) ? $_ENV['SITE_DESCRIPTION'] : 'Chapéu de paywall é marreta!');
define('SITE_URL', isset($_ENV['SITE_URL']) ? $_ENV['SITE_URL'] : 'https://' . $_SERVER['HTTP_HOST']);
define('DNS_SERVERS', isset($_ENV['DNS_SERVERS']) ? $_ENV['DNS_SERVERS'] : '1.1.1.1, 8.8.8.8');
define('CACHE_DIR', __DIR__ . '/cache');
define('DISABLE_CACHE', isset($_ENV['DISABLE_CACHE']) ? filter_var($_ENV['DISABLE_CACHE'], FILTER_VALIDATE_BOOLEAN) : false);

/**
 * Configurações de Cache S3
 */
define('S3_CACHE_ENABLED', isset($_ENV['S3_CACHE_ENABLED']) ? filter_var($_ENV['S3_CACHE_ENABLED'], FILTER_VALIDATE_BOOLEAN) : false);
if (S3_CACHE_ENABLED) {
    define('S3_ACCESS_KEY', $_ENV['S3_ACCESS_KEY'] ?? '');
    define('S3_SECRET_KEY', $_ENV['S3_SECRET_KEY'] ?? '');
    define('S3_BUCKET', $_ENV['S3_BUCKET'] ?? '');
    define('S3_REGION', $_ENV['S3_REGION'] ?? 'us-east-1');
    define('S3_FOLDER', $_ENV['S3_FOLDER'] ?? 'cache/');
    define('S3_ACL', $_ENV['S3_ACL'] ?? 'private');
    define('S3_ENDPOINT', $_ENV['S3_ENDPOINT'] ?? null);
}

/**
 * Configurações do LogOwl
 */
define('LOGOWL_ENABLED', isset($_ENV['LOGOWL_ENABLED']) ? filter_var($_ENV['LOGOWL_ENABLED'], FILTER_VALIDATE_BOOLEAN) : false);
if (LOGOWL_ENABLED) {
    define('LOGOWL_TICKET', isset($_ENV['LOGOWL_TICKET']) ? $_ENV['LOGOWL_TICKET'] : null);
}

/**
 * Carrega as configurações do sistema
 */
define('MESSAGES', require __DIR__ . '/data/messages.php');
define('USER_AGENTS', require __DIR__ . '/data/user_agents.php');
define('BLOCKED_DOMAINS', require __DIR__ . '/data/blocked_domains.php');
define('DOMAIN_RULES', require __DIR__ . '/data/domain_rules.php');
define('GLOBAL_RULES', require __DIR__ . '/data/global_rules.php');
