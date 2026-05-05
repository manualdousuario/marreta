<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute;

/**
 * Router Class - Application route manager
 * Manages all application routes, processes HTTP requests, and directs to appropriate handlers
 */
class Router
{
    /**
     * @var FastRoute\Dispatcher FastRoute dispatcher instance
     */
    private $dispatcher;

    /**
     * Constructor - Initializes application routes
     */
    public function __construct()
    {
        $this->dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            // Main route - home page
            $r->addRoute('GET', '/', function() {
                require_once __DIR__ . '/../config.php';
                require_once __DIR__ . '/../inc/Cache.php';
                require_once __DIR__ . '/../inc/Language.php';

                \Inc\Language::init(LANGUAGE);
                
                $message = '';
                $message_type = '';
                $url = '';
                
                // Sanitize and process query string messages
                if (isset($_GET['message'])) {
                    $message_key = htmlspecialchars(trim($_GET['message']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $messageData = \Inc\Language::getMessage($message_key);
                    $message = htmlspecialchars($messageData['message'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $message_type = htmlspecialchars($messageData['type'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                
                // Process form submission
                if (isset($_GET['url'])) {
                    $originalUrl = trim($_GET['url']);
                    if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                        $sanitizedUrl = $this->sanitizeUrl($originalUrl);
                        if (!empty($sanitizedUrl)) {
                            header('Location: ' . SITE_URL . '/p/' . $sanitizedUrl);
                            exit;
                        }
                    }
                    $url = $originalUrl;
                    $messageData = \Inc\Language::getMessage('INVALID_URL');
                    $message = $messageData['message'];
                    $message_type = $messageData['type'];
                }
                
                // Initialize cache for counting
                $cache = new \Inc\Cache();
                $cache_folder = $cache->getCacheFileCount();
                
                require __DIR__ . '/views/home.php';
            });

            // API route - uses URLProcessor in API mode
            $r->addRoute('GET', '/api/{url:.+}', function($vars) {
                require_once __DIR__ . '/../config.php';
                
                $processed = $this->processUrlRoute($vars['url'], true);
                
                if ($processed['valid']) {
                    if ($processed['needsRedirect']) {
                        $redirectUrl = preg_replace('#^https://#', '', $processed['url']);
                        header('Location: ' . SITE_URL . '/api/' . $redirectUrl);
                        exit;
                    }
                    
                    $processor = new URLProcessor($processed['url'], true);
                    $processor->process();
                } else {
                    header('Location: /?message=INVALID_URL');
                    exit;
                }
            });

            // API route without parameters - redirects to root
            $r->addRoute('GET', '/api[/]', function() {
                header('Location: /');
                exit;
            });

            // Processing route - uses URLProcessor in web mode
            $r->addRoute('GET', '/p/{url:.+}', function($vars) {
                require_once __DIR__ . '/../config.php';
                
                $processed = $this->processUrlRoute($vars['url'], true);
                
                if ($processed['valid']) {
                    if ($processed['needsRedirect']) {
                        $redirectUrl = preg_replace('#^https://#', '', $processed['url']);
                        header('Location: ' . SITE_URL . '/p/' . $redirectUrl);
                        exit;
                    }
                    
                    $processor = new URLProcessor($processed['url'], false);
                    $processor->process();
                } else {
                    header('Location: /?message=INVALID_URL');
                    exit;
                }
            });
            
            // Processing route with query parameter or without parameters
            $r->addRoute('GET', '/p[/]', function() {
                if (isset($_GET['url']) || isset($_GET['text'])) {
                    $originalUrl = isset($_GET['url']) ? trim($_GET['url']) : '';
                    $originalText = isset($_GET['text']) ? trim($_GET['text']) : '';
                    
                    if (!empty($originalUrl) && filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                        $sanitizedUrl = $this->sanitizeUrl($originalUrl);
                        if (!empty($sanitizedUrl)) {
                            header('Location: /p/' . $sanitizedUrl);
                            exit;
                        }
                    } elseif (!empty($originalText) && filter_var($originalText, FILTER_VALIDATE_URL)) {
                        $sanitizedText = $this->sanitizeUrl($originalText);
                        if (!empty($sanitizedText)) {
                            header('Location: /p/' . $sanitizedText);
                            exit;
                        }
                    } else {
                        header('Location: /?message=INVALID_URL');
                        exit;
                    }
                }
                header('Location: /');
                exit;
            });

            // PWA manifest route - includes existing manifest.php
            $r->addRoute('GET', '/manifest.json', function() {
                require __DIR__ . '/views/manifest.php';
            });
        });
    }

    /**
     * Process URL from route parameters - handles all URL normalization cases
     * @param string $rawUrl The raw URL from route
     * @param bool $checkRedirect Whether to check if redirect is needed
     * @return array{valid: bool, url: string, needsRedirect: bool}
     */
    private function processUrlRoute(string $rawUrl, bool $checkRedirect = false): array
    {
        $queryString = '';
        if (!empty($_GET)) {
            $queryParams = [];
            foreach ($_GET as $key => $value) {
                if ($key !== 'url' && $key !== 'text') {
                    $queryParams[$key] = $value;
                }
            }
            if (!empty($queryParams)) {
                $queryString = '?' . http_build_query($queryParams);
            }
        }
        
        $url = $rawUrl;
        
        $hasScheme = (bool) preg_match('#^https?://#', $url);
        
        if ($hasScheme) {
            $url = preg_replace('#^https?://#', '', $url);
        }
        
        $needsDecoding = (strpos($url, '%') !== false && preg_match('/%[0-9A-Fa-f]{2}/', $url));
        
        if ($needsDecoding) {
            $url = urldecode($url);
        }
        
        $url = preg_replace('#/+#', '/', $url);
        
        $url = 'https://' . $url;
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'url' => '', 'needsRedirect' => false];
        }
        
        $sanitizedUrl = $this->sanitizeUrl($url);
        $processedUrl = preg_replace('#^https://#', '', $sanitizedUrl);
        $needsRedirect = false;

        if ($checkRedirect && ($hasScheme || $rawUrl !== $processedUrl)) {
            $needsRedirect = true;
        }
        
        if (!empty($queryString)) {
            $sanitizedUrl .= $queryString;
        }
        
        return [
            'valid' => !empty($sanitizedUrl),
            'url' => $sanitizedUrl,
            'needsRedirect' => $needsRedirect
        ];
    }

    /**
     * Sanitizes and normalizes URLs
     * @param string $url The URL to sanitize and normalize
     * @return string|false The cleaned URL or false if invalid
     */
    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        // Handle AMP URLs
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        // Parse and reconstruct URL to ensure proper structure
        $parts = parse_url($url);
        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return '';
        }
        
        $cleanedUrl = $parts['host'];
        
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        if (isset($parts['query'])) {
            $cleanedUrl .= '?' . $parts['query'];
        }
        
        if (isset($parts['fragment'])) {
            $cleanedUrl .= '#' . $parts['fragment'];
        }
        
        // Remove control characters and sanitize
        $cleanedUrl = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanedUrl);
        $cleanedUrl = filter_var($cleanedUrl, FILTER_SANITIZE_URL);
        
        return $cleanedUrl;
    }

    /**
     * Sets security headers for all responses
     */
    private function setSecurityHeaders()
    {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }

    public function dispatch()
    {
        $this->setSecurityHeaders();
        
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string but keep for processing
        $queryString = '';
        if (false !== $pos = strpos($uri, '?')) {
            $queryString = substr($uri, $pos);
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // Parse query string parameters
        if ($queryString) {
            parse_str(substr($queryString, 1), $_GET);
        }

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                require_once __DIR__ . '/../config.php';
                header('Location: ' . SITE_URL);
                exit;

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                header("HTTP/1.0 405 Method Not Allowed");
                echo '405 Method Not Allowed';
                break;

            case FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                call_user_func($handler, $vars);
                break;
        }
    }
}
