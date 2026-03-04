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
            $r->addRoute(['GET','POST'], '/', function() {
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
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
                    $url = $this->sanitizeUrl($_POST['url']);
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: ' . SITE_URL . '/p/' . $url);
                        exit;
                    } else {
                        $messageData = \Inc\Language::getMessage('INVALID_URL');
                        $message = $messageData['message'];
                        $message_type = $messageData['type'];
                    }
                }
                
                // Initialize cache for counting
                $cache = new \Inc\Cache();
                $cache_folder = $cache->getCacheFileCount();
                
                require __DIR__ . '/views/home.php';
            });

            // API route - uses URLProcessor in API mode
            $r->addRoute('GET', '/api/{url:.+}', function($vars) {
                $processor = new URLProcessor($this->sanitizeUrl($vars['url']), true);
                $processor->process();
            });

            // API route without parameters - redirects to root
            $r->addRoute('GET', '/api[/]', function() {
                header('Location: /');
                exit;
            });

            // Processing route - uses URLProcessor in web mode
            $r->addRoute('GET', '/p/{url:.+}', function($vars) {
                $url = urldecode($vars['url']);
                $processor = new URLProcessor($this->sanitizeUrl($url), false);
                $processor->process();
            });
            
            // Processing route with query parameter or without parameters
            $r->addRoute('GET', '/p[/]', function() {
                if (isset($_GET['url']) || isset($_GET['text'])) {
                    $url = isset($_GET['url']) ? $this->sanitizeUrl($_GET['url']) : '';
                    $text = isset($_GET['text']) ? $this->sanitizeUrl($_GET['text']) : '';
                    
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . $url);
                        exit;
                    } elseif (filter_var($text, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . $text);
                        exit;
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
        
        $cleanedUrl = $parts['scheme'] . '://' . $parts['host'];
        
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        // Remove control characters and sanitize
        $cleanedUrl = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanedUrl);
        $cleanedUrl = filter_var($cleanedUrl, FILTER_SANITIZE_URL);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($cleanedUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
