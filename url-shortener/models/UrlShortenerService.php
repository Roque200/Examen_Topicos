<?php
class UrlShortenerService
{
    private const MIN_CODE_LENGTH = 5;
    private const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

 
    public static function generateCode(int $length = 6): string
    {
        $chars  = self::CHARS;
        $max    = strlen($chars) - 1;
        $code   = '';
        for ($i = 0; $i < max($length, self::MIN_CODE_LENGTH); $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

  
    public static function validateUrl(string $url, string $baseUrl = ''): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'message' => 'La URL no tiene un formato válido.'];
        }


        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return ['valid' => false, 'message' => 'Solo se permiten URLs con esquema http o https.'];
        }

        if ($baseUrl) {
            $urlHost  = parse_url($url, PHP_URL_HOST);
            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            if ($urlHost === $baseHost) {
                return ['valid' => false, 'message' => 'No se puede acortar una URL del propio servicio (evitar bucles).'];
            }
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'])) {
            return ['valid' => false, 'message' => 'No se permiten URLs que apunten a localhost o IPs privadas.'];
        }

        return ['valid' => true, 'message' => 'URL válida.'];
    }

    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
?>
