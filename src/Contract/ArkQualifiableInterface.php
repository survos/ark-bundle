<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Contract;

interface ArkQualifiableInterface
{
    public function getArkParent(): ArkableInterface;

    public function getArkQualifier(): string;

    public function getArkTarget(): string;
}
