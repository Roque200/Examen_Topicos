<?php
require_once __DIR__ . '/../../models/QrGenerator.php';

/**
 * QrResource
 * Controlador REST para la API generadora de QR.
 *
 * Endpoints:
 *   POST /api/v1/qr/text    -> QR de texto plano
 *   POST /api/v1/qr/url     -> QR de URL
 *   POST /api/v1/qr/wifi    -> QR de red WiFi
 *   POST /api/v1/qr/geo     -> QR de geolocalización
 */
class QrResource
{
    private const MIN_SIZE     = 100;
    private const MAX_SIZE     = 1000;
    private const DEFAULT_SIZE = 300;

    // ------------------------------------------------------------------
    // POST /api/v1/qr/text
    // ------------------------------------------------------------------
    public function text(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "El campo 'content' es requerido."]);
            return;
        }

        try {
            $qr     = $this->makeGenerator($data);
            $base64 = $qr->fromText($data['content']);
            $this->sendSuccess($base64, $qr, 'text', $data['content']);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/v1/qr/url
    // ------------------------------------------------------------------
    public function url(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['url'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "El campo 'url' es requerido."]);
            return;
        }

        try {
            $qr     = $this->makeGenerator($data);
            $base64 = $qr->fromUrl($data['url']);
            $this->sendSuccess($base64, $qr, 'url', $data['url']);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/v1/qr/wifi
    // ------------------------------------------------------------------
    public function wifi(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['ssid'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "El campo 'ssid' es requerido."]);
            return;
        }

        try {
            $qr       = $this->makeGenerator($data);
            $password = $data['password'] ?? '';
            $type     = $data['type']     ?? 'WPA';
            $base64   = $qr->fromWifi($data['ssid'], $password, $type);
            $this->sendSuccess($base64, $qr, 'wifi', "SSID: {$data['ssid']}");
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // POST /api/v1/qr/geo
    // ------------------------------------------------------------------
    public function geo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['lat']) || !isset($data['lng'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Los campos 'lat' y 'lng' son requeridos."]);
            return;
        }

        try {
            $qr     = $this->makeGenerator($data);
            $base64 = $qr->fromGeo((float)$data['lat'], (float)$data['lng']);
            $this->sendSuccess($base64, $qr, 'geo', "lat:{$data['lat']}, lng:{$data['lng']}");
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function makeGenerator(array $data): QrGenerator
    {
        $size  = isset($data['size'])  ? (int)$data['size']  : self::DEFAULT_SIZE;
        $level = isset($data['level']) ? strtoupper($data['level']) : 'M';

        if ($size < self::MIN_SIZE || $size > self::MAX_SIZE) {
            throw new InvalidArgumentException(
                "El tamaño debe estar entre " . self::MIN_SIZE . " y " . self::MAX_SIZE . " píxeles."
            );
        }

        return new QrGenerator($size, $level);
    }

    private function sendSuccess(string $base64, QrGenerator $qr, string $type, string $content): void
    {
        http_response_code(200);
        echo json_encode([
            'status'     => 'success',
            'type'       => $type,
            'content'    => $content,
            'size'       => $qr->getSize(),
            'level'      => $qr->getLevel(),
            'image_base64' => $base64,
            'data_uri'   => 'data:image/png;base64,' . $base64,
        ]);
    }
}
?>
