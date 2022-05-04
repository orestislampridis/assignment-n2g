<?php

namespace App\Entity;

use App\Repository\RoutingKeyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoutingKeyRepository::class)
 */
class RoutingKey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $routing_key;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoutingKey(): ?string
    {
        return $this->routing_key;
    }

    public function setRoutingKey(string $routing_key): self
    {
        $this->routing_key = $routing_key;

        return $this;
    }
}
