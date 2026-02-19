<?php
require_once __DIR__ . '/../../models/Password.php';
require_once __DIR__ . '/../../models/PasswordFactory.php';
require_once __DIR__ . '/../../models/PasswordValidator.php';

class PasswordResource
{
    private const MIN_LENGTH     = 4;
    private const MAX_LENGTH     = 128;
    private const MIN_COUNT      = 1;
    private const MAX_COUNT      = 50;
    private const DEFAULT_LENGTH = 16;
    private const DEFAULT_COUNT  = 5;

    public function generate(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $length = isset($_GET['length']) ? (int)$_GET['length'] : self::DEFAULT_LENGTH;

        $error = $this->validateLength($length);
        if ($error) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $error]);
            return;
        }

        $data = array_merge($_GET, ['length' => $length]);

        try {
            $passwordObj = PasswordFactory::fromRequest($data, $length);
            $pwd         = $passwordObj->generate();

            http_response_code(200);
            echo json_encode([
                'status'   => 'success',
                'password' => $pwd,
                'length'   => strlen($pwd),
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generateMultiple(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $length = isset($data['length']) ? (int)$data['length'] : self::DEFAULT_LENGTH;
        $count  = isset($data['count'])  ? (int)$data['count']  : self::DEFAULT_COUNT;

        $error = $this->validateLength($length);
        if ($error) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $error]);
            return;
        }

        if ($count < self::MIN_COUNT || $count > self::MAX_COUNT) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => "El parámetro 'count' debe estar entre " . self::MIN_COUNT . " y " . self::MAX_COUNT . ".",
            ]);
            return;
        }

        try {
            $passwordObj = PasswordFactory::fromRequest($data, $length);
            $passwords   = $passwordObj->generateMultiple($count);

            http_response_code(200);
            echo json_encode([
                'status'    => 'success',
                'count'     => count($passwords),
                'length'    => $length,
                'passwords' => $passwords,
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function validate(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => "El campo 'password' es requerido.",
            ]);
            return;
        }

        $password     = (string)$data['password'];
        $requirements = $data['requirements'] ?? [];

        $result = PasswordValidator::validate($password, $requirements);

        http_response_code(200);
        echo json_encode([
            'status'       => 'success',
            'valid'        => $result['valid'],
            'score'        => $result['score'],
            'strength'     => $result['strength'],
            'checks'       => $result['checks'],
            'password_length' => strlen($password),
        ]);
    }

    private function validateLength(int $length): ?string
    {
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return "El parámetro 'length' debe estar entre " . self::MIN_LENGTH . " y " . self::MAX_LENGTH . ".";
        }
        return null;
    }
}
?>