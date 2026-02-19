<?php

class QrGenerator
{
    // Niveles de corrección de errores
    const LEVEL_L = 'L'; // ~7%
    const LEVEL_M = 'M'; // ~15%
    const LEVEL_Q = 'Q'; // ~25%
    const LEVEL_H = 'H'; // ~30%

    private int    $size;
    private string $level;
    private string $outputDir;

    public function __construct(int $size = 300, string $level = 'M')
    {
        $this->size      = max(100, min(1000, $size));
        $this->level     = in_array($level, ['L','M','Q','H']) ? $level : 'M';
        $this->outputDir = sys_get_temp_dir() . '/qr_codes/';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        // Load phpqrcode library
        $libPath = __DIR__ . '/../lib/phpqrcode/qrlib.php';
        if (!file_exists($libPath)) {
            throw new RuntimeException("Librería phpqrcode no encontrada en: {$libPath}");
        }
        if (!class_exists('QRcode')) {
            require_once $libPath;
        }
    }

    /** QR para texto plano */
    public function fromText(string $text): string
    {
        if (empty(trim($text))) {
            throw new InvalidArgumentException("El texto no puede estar vacío.");
        }
        return $this->generate($text);
    }

    /** QR para URL */
    public function fromUrl(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("La URL no tiene un formato válido.");
        }
        return $this->generate($url);
    }

    /** QR para WiFi */
    public function fromWifi(string $ssid, string $password = '', string $type = 'WPA'): string
    {
        if (empty(trim($ssid))) {
            throw new InvalidArgumentException("El SSID no puede estar vacío.");
        }
        $type    = strtoupper($type);
        $allowed = ['WPA', 'WPA2', 'WEP', 'nopass'];
        if (!in_array($type, $allowed)) {
            throw new InvalidArgumentException("Tipo WiFi no válido. Use: WPA, WPA2, WEP, nopass.");
        }

        // Formato estándar para QR WiFi
        $content = "WIFI:T:{$type};S:{$ssid};P:{$password};;";
        return $this->generate($content);
    }

    /** QR para geolocalización */
    public function fromGeo(float $lat, float $lng): string
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitud inválida. Debe estar entre -90 y 90.");
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitud inválida. Debe estar entre -180 y 180.");
        }

        $content = "geo:{$lat},{$lng}";
        return $this->generate($content);
    }

    /**
     * Genera el QR y devuelve la imagen en base64.
     */
    private function generate(string $content): string
    {
        $filename = $this->outputDir . uniqid('qr_', true) . '.png';

        // Calcular margen (padding) proporcional al tamaño
        $margin = max(1, (int)($this->size / 100));

        // Generar QR: (content, filename, level, size_pixels, margin)
        /** @phpstan-ignore-next-line */
        QRcode::png($content, $filename, $this->level, $this->size / 25, $margin);

        if (!file_exists($filename)) {
            throw new RuntimeException("Error al generar el código QR.");
        }

        $imageData = file_get_contents($filename);
        unlink($filename); // limpiar archivo temporal

        return base64_encode($imageData);
    }

    public function getSize(): int    { return $this->size; }
    public function getLevel(): string { return $this->level; }
}
?>
