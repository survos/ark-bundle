<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Service;

use Symfony\Component\Uid\Ulid;

final class ArkUlidCodec
{
    public const LENGTH = 22;

    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz_';
    private const BYTES = 16;

    public function __construct(
        private readonly ?string $naan,
        private readonly ?string $resolverBaseUrl,
        private readonly string $localPath,
    ) {}

    public function name(Ulid|string $ulid): string
    {
        $digits = [0];
        $bytes = ($ulid instanceof Ulid ? $ulid : new Ulid($ulid))->toBinary();

        for ($i = 0; $i < self::BYTES; ++$i) {
            $carry = ord($bytes[$i]);
            foreach ($digits as &$digit) {
                $carry += $digit << 8;
                $digit = $carry % 58;
                $carry = intdiv($carry, 58);
            }
            unset($digit);

            while ($carry > 0) {
                $digits[] = $carry % 58;
                $carry = intdiv($carry, 58);
            }
        }

        $name = '';
        for ($i = count($digits) - 1; $i >= 0; --$i) {
            $name .= self::ALPHABET[$digits[$i]];
        }

        return str_pad($name, self::LENGTH, self::ALPHABET[0], STR_PAD_LEFT);
    }

    public function ark(Ulid|string $ulid): string
    {
        return sprintf('ark:/%s/%s', $this->requireNaan(), $this->name($ulid));
    }

    public function url(Ulid|string $ulid): string
    {
        $path = sprintf('%s/%s/%s', rtrim($this->localPath, '/'), $this->requireNaan(), $this->name($ulid));

        return ($this->resolverBaseUrl === null || $this->resolverBaseUrl === '')
            ? $path
            : rtrim($this->resolverBaseUrl, '/') . $path;
    }

    public function ulid(string $name): Ulid
    {
        if (strlen($name) !== self::LENGTH) {
            throw new \InvalidArgumentException(sprintf('ARK ULID names must be %d characters.', self::LENGTH));
        }

        $alphabet = array_flip(str_split(self::ALPHABET));
        $bytes = [0];

        foreach (str_split($name) as $char) {
            if (!isset($alphabet[$char])) {
                throw new \InvalidArgumentException(sprintf('Invalid ARK ULID character "%s".', $char));
            }

            $carry = $alphabet[$char];
            foreach ($bytes as &$byte) {
                $carry += $byte * 58;
                $byte = $carry & 0xff;
                $carry >>= 8;
            }
            unset($byte);

            while ($carry > 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }

        if (count($bytes) > self::BYTES) {
            throw new \InvalidArgumentException('ARK ULID name is outside the ULID range.');
        }

        $binary = '';
        for ($i = self::BYTES - 1; $i >= 0; --$i) {
            $binary .= chr($bytes[$i] ?? 0);
        }

        return Ulid::fromBinary($binary);
    }

    private function requireNaan(): string
    {
        return $this->naan ?? throw new \LogicException('survos_ark.naan is not configured.');
    }
}
