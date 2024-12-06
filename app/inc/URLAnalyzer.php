<?php

/**
 * Classe responsável pela análise e processamento de URLs
 * 
 * Esta classe implementa funcionalidades para:
 * - Análise e limpeza de URLs
 * - Cache de conteúdo
 * - Resolução DNS
 * - Requisições HTTP com múltiplas tentativas
 * - Processamento de conteúdo baseado em regras específicas por domínio
 * - Suporte a Wayback Machine como fallback
 */

require_once 'Rules.php';
require_once 'Cache.php';

use Curl\Curl;

class URLAnalyzer
{
    /**
     * @var array Lista de User Agents disponíveis para requisições
     */
    private $userAgents;

    /**
     * @var array Lista de servidores DNS para resolução
     */
    private $dnsServers;

    /**
     * @var Rules Instância da classe de regras
     */
    private $rules;

    /**
     * @var Cache Instância da classe de cache
     */
    private $cache;

    /**
     * Construtor da classe
     * Inicializa as dependências necessárias
     */
    public function __construct()
    {
        $this->userAgents = USER_AGENTS;
        $this->dnsServers = explode(',', DNS_SERVERS);
        $this->rules = new Rules();
        $this->cache = new Cache();
    }

    /**
     * Verifica se uma URL tem redirecionamentos e retorna a URL final
     * 
     * @param string $url URL para verificar redirecionamentos
     * @return array Array com a URL final e se houve redirecionamento
     */
    public function checkRedirects($url)
    {
        $curl = new Curl();
        $curl->setFollowLocation();
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);
        $curl->get($url);

        if ($curl->error) {
            return [
                'finalUrl' => $url,
                'hasRedirect' => false,
                'httpCode' => $curl->httpStatusCode
            ];
        }

        return [
            'finalUrl' => $curl->effectiveUrl,
            'hasRedirect' => ($curl->effectiveUrl !== $url),
            'httpCode' => $curl->httpStatusCode
        ];
    }

    /**
     * Registra erros no LogOwl
     * 
     * @param string $url URL que gerou o erro
     * @param string $error Mensagem de erro
     */
    private function logError($url, $error = 'GENERIC_ERROR', $message = null)
    {
        if (defined('LOGOWL_ENABLED') && LOGOWL_ENABLED) {
            try {
                $curl = new Curl();
                $curl->setOpt(CURLOPT_TIMEOUT, 5);
                $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
                
                // Prepara o payload do LogOwl
                $payload = [
                    'ticket' => uniqid('marreta_', true),
                    'message' => $error,
                    'path' => $url,
                    'type' => 'exception',
                ];

                if($message) {
                    $logs = [
                        'logs' => [
                            [
                                'type' => 'error',
                                'log' => $message,
                                'timestamp' => time()
                            ]
                        ]
                    ];
                    array_push($payload, $logs);
                }

                // Adiciona o backtrace para melhor contexto
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
                if (!empty($backtrace)) {
                    $caller = $backtrace[1] ?? [];
                    $payload['line'] = $caller['line'] ?? 0;
                    $payload['file'] = $caller['file'] ?? '';
                }

                $curl->setHeader('Content-Type', 'application/json');
                $curl->post('https://api.logowl.io/logging/error', json_encode($payload));
            } catch (Exception $e) {
                // Silenciosamente ignora erros na integração com LogOwl
            }
        }
    }

    /**
     * Método principal para análise de URLs
     * 
     * @param string $url URL a ser analisada
     * @return string Conteúdo processado da URL
     * @throws Exception Em caso de erros durante o processamento
     */
    public function analyze($url)
    {
        // 1. Limpa a URL
        $cleanUrl = $this->cleanUrl($url);
        if (!$cleanUrl) {
            throw new Exception("URL inválida");
        }

        // 2. Verifica cache
        if ($this->cache->exists($cleanUrl)) {
            return $this->cache->get($cleanUrl);
        }

        // 3. Verifica domínios bloqueados
        $host = parse_url($cleanUrl, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, BLOCKED_DOMAINS)) {
            $error = 'BLOCKED_DOMAINS';
            $this->logError($cleanUrl, $error);
            throw new Exception($error);
        }

        // 4. Tenta buscar conteúdo diretamente
        try {
            $content = $this->fetchContent($cleanUrl);
            if (!empty($content)) {
                $processedContent = $this->processContent($content, $host, $cleanUrl);
                $this->cache->set($cleanUrl, $processedContent);
                return $processedContent;
            }
        } catch (Exception $e) {
            $this->logError($cleanUrl, "FETCH_DEFAULT" . $e->getMessage());
        }

        // 5. Tenta buscar do Wayback Machine como fallback
        try {
            $content = $this->fetchFromWaybackMachine($cleanUrl);
            if (!empty($content)) {
                $processedContent = $this->processContent($content, $host, $cleanUrl);
                $this->cache->set($cleanUrl, $processedContent);
                return $processedContent;
            }
        } catch (Exception $e) {
            $this->logError($cleanUrl, "FETCH_WAYBACK" . $e->getMessage());
        }

        throw new Exception("Não foi possível obter o conteúdo da URL");
    }

    /**
     * Tenta obter o conteúdo da URL do Internet Archive's Wayback Machine
     * 
     * @param string $url URL original
     * @return string|null Conteúdo do arquivo
     * @throws Exception Em caso de erro na requisição
     */
    private function fetchFromWaybackMachine($url)
    {
        $cleanUrl = preg_replace('#^https?://#', '', $url);
        $availabilityUrl = "https://archive.org/wayback/available?url=" . urlencode($cleanUrl);
        
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);

        $curl->get($availabilityUrl);

        if ($curl->error || $curl->httpStatusCode !== 200) {
            throw new Exception("Erro ao verificar disponibilidade no Wayback Machine");
        }

        $data = $curl->response;
        if (!isset($data->archived_snapshots->closest->url)) {
            throw new Exception("Nenhum snapshot encontrado no Wayback Machine");
        }

        $archiveUrl = $data->archived_snapshots->closest->url;
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);

        $curl->get($archiveUrl);

        if ($curl->error || $curl->httpStatusCode !== 200 || empty($curl->response)) {
            throw new Exception("Erro ao obter conteúdo do Wayback Machine");
        }

        $content = $curl->response;
        
        // Remove o toolbar do Wayback Machine e URLs de cache
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);
        
        return $content;
    }

    /**
     * Realiza requisição HTTP usando Curl Class
     * 
     * @param string $url URL para requisição
     * @return string Conteúdo da página
     * @throws Exception Em caso de erro na requisição
     */
    private function fetchContent($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);
        $domainRules = $this->getDomainRules($host);

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_MAXREDIRS, 2);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_DNS_SERVERS, implode(',', $this->dnsServers));
        
        // Define User Agent
        $userAgent = isset($domainRules['userAgent']) 
            ? $domainRules['userAgent'] 
            : $this->userAgents[array_rand($this->userAgents)];
        $curl->setUserAgent($userAgent);

        // Headers padrão
        $headers = [
            'Host' => $host,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ];

        // Adiciona headers específicos do domínio
        if (isset($domainRules['headers'])) {
            $headers = array_merge($headers, $domainRules['headers']);
        }
        $curl->setHeaders($headers);

        // Adiciona cookies específicos do domínio
        if (isset($domainRules['cookies'])) {
            $cookies = [];
            foreach ($domainRules['cookies'] as $name => $value) {
                if ($value !== null) {
                    $cookies[] = $name . '=' . $value;
                }
            }
            if (!empty($cookies)) {
                $curl->setHeader('Cookie', implode('; ', $cookies));
            }
        }

        // Adiciona referer se especificado
        if (isset($domainRules['referer'])) {
            $curl->setHeader('Referer', $domainRules['referer']);
        }

        $curl->get($url);

        if ($curl->error || $curl->httpStatusCode !== 200) {
            throw new Exception("Erro HTTP " . $curl->httpStatusCode . ": " . $curl->errorMessage);
        }

        if (empty($curl->response)) {
            throw new Exception("Resposta vazia do servidor");
        }

        return $curl->response;
    }

    /**
     * Limpa e normaliza uma URL
     * 
     * @param string $url URL para limpar
     * @return string|false URL limpa e normalizada ou false se inválida
     */
    private function cleanUrl($url)
    {
        $url = trim($url);

        // Verifica se a URL é válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Detecta e converte URLs AMP
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        // Separa a URL em suas partes componentes
        $parts = parse_url($url);
        
        // Reconstrói a URL base
        $cleanedUrl = $parts['scheme'] . '://' . $parts['host'];
        
        // Adiciona o caminho se existir
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        return $cleanedUrl;
    }

    /**
     * Obtém regras específicas para um domínio
     * 
     * @param string $domain Domínio para buscar regras
     * @return array|null Regras do domínio ou null se não encontrar
     */
    private function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }

    /**
     * Remove classes específicas de um elemento
     * 
     * @param DOMElement $element Elemento DOM
     * @param array $classesToRemove Classes a serem removidas
     */
    private function removeClassNames($element, $classesToRemove)
    {
        if (!$element->hasAttribute('class')) {
            return;
        }

        $classes = explode(' ', $element->getAttribute('class'));
        $newClasses = array_filter($classes, function ($class) use ($classesToRemove) {
            return !in_array(trim($class), $classesToRemove);
        });

        if (empty($newClasses)) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $newClasses));
        }
    }

    /**
     * Corrige URLs relativas em um documento DOM
     * 
     * @param DOMDocument $dom Documento DOM
     * @param DOMXPath $xpath Objeto XPath
     * @param string $baseUrl URL base para correção
     */
    private function fixRelativeUrls($dom, $xpath, $baseUrl)
    {
        $parsedBase = parse_url($baseUrl);
        $baseHost = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        $elements = $xpath->query("//*[@src]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $src = $element->getAttribute('src');
                    if (strpos($src, 'base64') !== false) {
                        continue;
                    }
                    if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                        $src = ltrim($src, '/');
                        $element->setAttribute('src', $baseHost . '/' . $src);
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@href]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $href = $element->getAttribute('href');
                    if (strpos($href, 'mailto:') === 0 || 
                        strpos($href, 'tel:') === 0 || 
                        strpos($href, 'javascript:') === 0 || 
                        strpos($href, '#') === 0) {
                        continue;
                    }
                    if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0) {
                        $href = ltrim($href, '/');
                        $element->setAttribute('href', $baseHost . '/' . $href);
                    }
                }
            }
        }
    }

    /**
     * Processa o conteúdo HTML aplicando regras do domínio
     * 
     * @param string $content Conteúdo HTML
     * @param string $host Nome do host
     * @param string $url URL completa
     * @return string Conteúdo processado
     */
    private function processContent($content, $host, $url)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Processa tags canônicas
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            // Remove todas as tags canônicas existentes
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }
        // Adiciona nova tag canônica com a URL original
        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }

        // Sempre aplica a correção de URLs relativas
        $this->fixRelativeUrls($dom, $xpath, $url);

        $domainRules = $this->getDomainRules($host);
        if (isset($domainRules['customStyle'])) {
            $styleElement = $dom->createElement('style');
            $styleElement->appendChild($dom->createTextNode($domainRules['customStyle']));
            $dom->getElementsByTagName('head')[0]->appendChild($styleElement);
        }

        if (isset($domainRules['customCode'])) {
            $scriptElement = $dom->createElement('script');
            $scriptElement->setAttribute('type', 'text/javascript');
            $scriptElement->appendChild($dom->createTextNode($domainRules['customCode']));
            $dom->getElementsByTagName('body')[0]->appendChild($scriptElement);
        }

        if (isset($domainRules['classAttrRemove'])) {
            foreach ($domainRules['classAttrRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false) {
                    foreach ($elements as $element) {
                        $this->removeClassNames($element, [$class]);
                    }
                }
            }
        }

        if (isset($domainRules['idElementRemove'])) {
            foreach ($domainRules['idElementRemove'] as $id) {
                $elements = $xpath->query("//*[@id='$id']");
                if ($elements !== false) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                }
            }
        }

        if (isset($domainRules['classElementRemove'])) {
            foreach ($domainRules['classElementRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                }
            }
        }

        if (isset($domainRules['scriptTagRemove'])) {
            foreach ($domainRules['scriptTagRemove'] as $script) {
                // Busca por tags script com src ou conteúdo contendo o script
                $scriptElements = $xpath->query("//script[contains(@src, '$script')] | //script[contains(text(), '$script')]");
                if ($scriptElements !== false) {
                    foreach ($scriptElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                }

                // Busca por tags link que são scripts
                $linkElements = $xpath->query("//link[@as='script' and contains(@href, '$script') and @type='application/javascript']");
                if ($linkElements !== false) {
                    foreach ($linkElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@style]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $style = $element->getAttribute('style');
                    $style = preg_replace('/(max-height|height|overflow|position|display|visibility)\s*:\s*[^;]+;?/', '', $style);
                    $element->setAttribute('style', $style);
                }
            }
        }

        // Adiciona CTA Marreta 
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $marretaDiv = $dom->createElement('div');
            $marretaDiv->setAttribute('style', 'z-index: 99999; position: fixed; top: 0; right: 4px; background: rgb(37,99,235); color: #fff; font-size: 13px; line-height: 1em; padding: 6px; margin: 0px; overflow: hidden; border-bottom-left-radius: 3px; border-bottom-right-radius: 3px; font-family: Tahoma, sans-serif;');
            $marretaHtml = $dom->createDocumentFragment();
            $marretaHtml->appendXML('Chapéu de paywall é <a href="'.SITE_URL.'" style="color: #fff; text-decoration: underline; font-weight: bold;" target="_blank">Marreta</a>!');
            $marretaDiv->appendChild($marretaHtml);
            $body->appendChild($marretaDiv);
        }

        return $dom->saveHTML();
    }
}
