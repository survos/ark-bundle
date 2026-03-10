<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Doctrine;

use Doctrine\ORM\Mapping as ORM;

trait ArkableTrait
{
    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $ark = null;

    public function getArk(): ?string
    {
        return $this->ark;
    }

    public function setArk(string $ark): static
    {
        $this->ark = $ark;
        return $this;
    }
}
