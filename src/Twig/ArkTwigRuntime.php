<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Twig;

use Survos\ArkBundle\Service\ArkUlidCodec;
use Symfony\Component\Uid\Ulid;
use Twig\Attribute\AsTwigFunction;

final class ArkTwigRuntime
{
    public function __construct(
        private readonly ArkUlidCodec $codec,
    ) {}

    #[AsTwigFunction('ark_ulid_name')]
    public function name(Ulid|string $ulid): string
    {
        return $this->codec->name($ulid);
    }

    #[AsTwigFunction('ark_ulid_url')]
    public function url(Ulid|string $ulid): string
    {
        return $this->codec->url($ulid);
    }

    #[AsTwigFunction('ark_ulid_n2t_url')]
    public function n2tUrl(Ulid|string $ulid): string
    {
        return $this->codec->n2tUrl($ulid);
    }

    #[AsTwigFunction('ark_ulid')]
    public function ulid(string $name): string
    {
        return (string) $this->codec->ulid($name);
    }
}
