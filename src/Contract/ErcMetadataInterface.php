<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Contract;

interface ErcMetadataInterface extends ArkableInterface
{
    public function getErcWho(): string;

    public function getErcWhat(): string;

    public function getErcWhen(): string;
}
