<?php
namespace specter\network;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\SourceInterface;
use pocketmine\player\Player;

class SpecterPlayer extends Player {
    private bool $spec_needRespawn = false;
    private ?Vector3 $forceMovement = null;
    private PlayerNetworkSessionAdapter $sessionAdapter;

    public function __construct(SourceInterface $interface, string $ip, int $port) {
        parent::__construct($interface, $ip, $port);
    }

    public function needsRespawn(): bool {
        return $this->spec_needRespawn;
    }

    public function setNeedsRespawn(bool $value): void {
        $this->spec_needRespawn = $value;
    }
    
    public function getForceMovement(): ?Vector3 {
        return $this->forceMovement;
    }

    public function setForceMovement(?Vector3 $movement): void {
        $this->forceMovement = $movement;
    }
    
    public function getSessionAdapter(): PlayerNetworkSessionAdapter {
        return $this->sessionAdapter;
    }
}
