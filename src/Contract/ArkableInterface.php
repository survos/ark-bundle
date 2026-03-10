<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Contract;

interface ArkableInterface
{
    public function getArk(): ?string;

    public function setArk(string $ark): static;

    public function getArkTarget(): string;

    public function getArkObjectType(): string;
}
