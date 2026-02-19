<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ShortUrl.php';
require_once __DIR__ . '/../../models/UrlShortenerService.php';


class UrlResource
{
    private $db;
    private ShortUrl $shortUrl;

    public function __construct()
    {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->shortUrl = new ShortUrl($this->db);
    }

    public function shorten(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['url'])) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => "El campo 'url' es requerido.",
            ]);
            return;
        }

        $originalUrl = trim($data['url']);

        $validation = UrlShortenerService::validateUrl($originalUrl);
        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => $validation['message'],
            ]);
            return;
        }

        $expiresAt = null;
        if (!empty($data['expires_at'])) {
            $ts = strtotime($data['expires_at']);
            if (!$ts || $ts <= time()) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => "La fecha de expiración debe ser una fecha futura válida.",
                ]);
                return;
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
        }

        
        $maxUses = null;
        if (isset($data['max_uses'])) {
            $maxUses = (int)$data['max_uses'];
            if ($maxUses < 1) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => "El campo 'max_uses' debe ser un entero >= 1.",
                ]);
                return;
            }
        }

        $codeLength = isset($data['code_length']) ? (int)$data['code_length'] : 6;
        $codeLength = max(5, min(20, $codeLength));

        do {
            $code = UrlShortenerService::generateCode($codeLength);
        } while ($this->shortUrl->codeExists($code));

        $this->shortUrl->original_url = $originalUrl;
        $this->shortUrl->short_code   = $code;
        $this->shortUrl->creator_ip   = UrlShortenerService::getClientIp();
        $this->shortUrl->max_uses     = $maxUses;
        $this->shortUrl->expires_at   = $expiresAt;

        if (!$this->shortUrl->create()) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'No se pudo guardar la URL. Intenta de nuevo.',
            ]);
            return;
        }

        $baseUrl  = $this->getBaseUrl();
        $shortUrl = $baseUrl . '/api/v1/redirect/' . $code;

        http_response_code(201);
        echo json_encode([
            'status'       => 'success',
            'short_url'    => $shortUrl,
            'short_code'   => $code,
            'original_url' => $originalUrl,
            'expires_at'   => $expiresAt,
            'max_uses'     => $maxUses,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function redirect(string $code): void
    {
        if (!$this->shortUrl->findByCode($code)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => "El código '{$code}' no existe.",
            ]);
            return;
        }

        if (!$this->shortUrl->isActive()) {
            http_response_code(410); 
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Esta URL corta ha expirado o alcanzó su límite de usos.',
            ]);
            return;
        }

        $ip        = UrlShortenerService::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $this->shortUrl->registerVisit($ip, $userAgent);

        http_response_code(302);
        header('Location: ' . $this->shortUrl->original_url);
    }

    public function stats(string $code): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $stats = $this->shortUrl->getStats($code);

        if (!$stats) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => "El código '{$code}' no existe.",
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data'   => $stats,
        ]);
    }


    private function getBaseUrl(): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
?>
