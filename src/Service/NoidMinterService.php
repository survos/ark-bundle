<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Service;

use Noid\Lib\Db;
use Noid\Noid;
use Noid\Storage\DatabaseInterface;

final class NoidMinterService
{
    private ?string $noidHandle = null;

    public function __construct(
        private readonly ?string $naan,
        private readonly string $shoulder,
        private readonly string $template,
        private readonly string $dbType,
        private readonly string $dbPath,
    ) {}

    private function requireNaan(): string
    {
        return $this->requireNaan() ?? throw new \LogicException('survos_ark.naan is not configured. Add "naan: YOUR_NAAN" to config/packages/survos_ark.yaml.');
    }

    public function mint(): string
    {
        $this->initializeMinterIfNeeded();
        $id = Noid::mint($this->open(DatabaseInterface::DB_WRITE), $this->contact());

        return $this->extractNameFromNoidId((string) $id);
    }

    public function buildFullArk(string $name): string
    {
        return sprintf('ark:/%s/%s', $this->requireNaan(), $name);
    }

    public function getNaan(): string
    {
        return $this->requireNaan();
    }

    public function bind(string $name, string $url): void
    {
        $this->initializeMinterIfNeeded();
        Noid::bind(
            $this->open(DatabaseInterface::DB_WRITE),
            $this->contact(),
            '-',
            'set',
            $this->toNoidId($name),
            'where',
            $url,
        );
    }

    public function rebind(string $name, string $url): void
    {
        $this->bind($name, $url);
    }

    public function resolve(string $name): ?string
    {
        $this->initializeMinterIfNeeded();
        $value = Noid::fetch($this->open(DatabaseInterface::DB_RDONLY), 0, $this->toNoidId($name), 'where');
        $resolved = trim((string) $value);

        return $resolved === '' ? null : $resolved;
    }

    public function validate(string $name): bool
    {
        $this->initializeMinterIfNeeded();
        $messages = Noid::validate($this->open(DatabaseInterface::DB_RDONLY), '-', $this->toNoidId($name));
        if ($messages === []) {
            return false;
        }

        $isValid = false;
        foreach ($messages as $message) {
            if (!is_string($message)) {
                continue;
            }
            if (str_starts_with($message, 'iderr:')) {
                return false;
            }
            if (str_starts_with($message, 'id: ')) {
                $isValid = true;
            }
        }

        return $isValid;
    }

    public function extractNoid(string $ark): ?string
    {
        $prefix = sprintf('ark:/%s/', $this->requireNaan());
        if (!str_starts_with($ark, $prefix)) {
            return null;
        }

        return substr($ark, strlen($prefix)) ?: null;
    }

    public function close(): void
    {
        if ($this->noidHandle === null) {
            return;
        }

        Db::dbclose($this->noidHandle, true);
        $this->noidHandle = null;
    }

    private function initializeMinterIfNeeded(): void
    {
        $settings = $this->settings();

        if (!is_dir($this->dbPath)) {
            mkdir($this->dbPath, 0775, true);
        }

        $readme = rtrim($this->dbPath, '/') . '/NOID/README';
        if (is_file($readme)) {
            return;
        }

        Db::dbcreate(
            $settings,
            $this->contact(),
            $this->template,
            'long',
            $this->requireNaan(),
            'Survos',
            $this->shoulder !== '' ? $this->shoulder : 'ark',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return [
            'db_type' => $this->dbType,
            'storage' => [
                $this->dbType => [
                    'data_dir' => $this->dbPath,
                ],
            ],
        ];
    }

    private function open(string $flags): string
    {
        if ($this->noidHandle !== null) {
            return $this->noidHandle;
        }

        $opened = Db::dbopen($this->settings(), $flags);
        $this->noidHandle = (string) $opened;

        return $this->noidHandle;
    }

    private function contact(): string
    {
        return 'ark@survos.org';
    }

    private function toNoidId(string $name): string
    {
        return sprintf('%s/%s', $this->requireNaan(), $name);
    }

    private function extractNameFromNoidId(string $id): string
    {
        $prefix = $this->requireNaan() . '/';
        if (str_starts_with($id, $prefix)) {
            return substr($id, strlen($prefix));
        }

        $slash = strpos($id, '/');

        return $slash === false ? $id : substr($id, $slash + 1);
    }
}
