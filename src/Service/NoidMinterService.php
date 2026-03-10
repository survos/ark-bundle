<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Service;

final class NoidMinterService
{
    private string $bindingsFile;
    private string $counterFile;

    public function __construct(
        private readonly string $naan,
        private readonly string $shoulder,
        private readonly string $template,
        private readonly string $dbType,
        private readonly string $dbPath,
    ) {
        $suffix = $this->dbType . '-' . substr(md5($this->template), 0, 8);
        $this->bindingsFile = rtrim($this->dbPath, '/') . '/bindings-' . $suffix . '.json';
        $this->counterFile = rtrim($this->dbPath, '/') . '/counter-' . $suffix . '.txt';
    }

    public function mint(): string
    {
        $counter = $this->nextCounter();

        return $this->shoulder . str_pad(base_convert((string) $counter, 10, 36), 8, '0', STR_PAD_LEFT);
    }

    public function buildFullArk(string $name): string
    {
        return sprintf('ark:/%s/%s', $this->naan, $name);
    }

    public function getNaan(): string
    {
        return $this->naan;
    }

    public function bind(string $name, string $url): void
    {
        $bindings = $this->loadBindings();
        $bindings[$name] = $url;
        $this->saveBindings($bindings);
    }

    public function rebind(string $name, string $url): void
    {
        $this->bind($name, $url);
    }

    public function resolve(string $name): ?string
    {
        $bindings = $this->loadBindings();

        return $bindings[$name] ?? null;
    }

    public function validate(string $name): bool
    {
        return $name !== '' && (bool) preg_match('/^[A-Za-z0-9._~:-]+$/', $name);
    }

    public function extractNoid(string $ark): ?string
    {
        $prefix = sprintf('ark:/%s/', $this->naan);
        if (!str_starts_with($ark, $prefix)) {
            return null;
        }

        return substr($ark, strlen($prefix)) ?: null;
    }

    public function close(): void
    {
    }

    private function nextCounter(): int
    {
        $this->ensureStorage();

        $current = 0;
        if (is_file($this->counterFile)) {
            $raw = file_get_contents($this->counterFile);
            if ($raw !== false) {
                $current = (int) trim($raw);
            }
        }

        $next = $current + 1;
        file_put_contents($this->counterFile, (string) $next);

        return $next;
    }

    /**
     * @return array<string, string>
     */
    private function loadBindings(): array
    {
        $this->ensureStorage();

        if (!is_file($this->bindingsFile)) {
            return [];
        }

        $raw = file_get_contents($this->bindingsFile);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_filter($decoded, static fn (mixed $v): bool => is_string($v)) : [];
    }

    /**
     * @param array<string, string> $bindings
     */
    private function saveBindings(array $bindings): void
    {
        $this->ensureStorage();
        file_put_contents($this->bindingsFile, (string) json_encode($bindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function ensureStorage(): void
    {
        if (!is_dir($this->dbPath)) {
            mkdir($this->dbPath, 0775, true);
        }
    }
}
