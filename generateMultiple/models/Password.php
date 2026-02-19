<?php
require_once __DIR__ . '/PasswordGenerator.php';

class Password
{
    private int   $length;
    private array $opts;

    public function __construct(int $length, array $opts = [])
    {
        $this->length = $length;
        $this->opts   = $opts;
    }

    public function generate(): string
    {
        return PasswordGenerator::generate_password($this->length, $this->opts);
    }

    public function generateMultiple(int $count): array
    {
        return PasswordGenerator::generate_passwords($count, $this->length, $this->opts);
    }

    public function getLength(): int { return $this->length; }
    public function getOpts(): array { return $this->opts; }
}
