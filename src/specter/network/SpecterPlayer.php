<?php

namespace specter\network;

use pocketmine\math\Vector3;
use pocketmine\network\SourceInterface;
use pocketmine\player\Player;

class SpecterPlayer extends Player {
    public bool $spec_needRespawn = false;
    private ?Vector3 $forceMovement = null;

    public function __construct(SourceInterface $interface, string $ip, int $port) {
        parent::__construct($interface, $ip, $port);
    }

    public function getForceMovement(): ?Vector3 {
        return $this->forceMovement;
    }
}
