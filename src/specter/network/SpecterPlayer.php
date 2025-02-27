<?php
namespace specter\network;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\SourceInterface;
use pocketmine\Player;

class SpecterPlayer extends Player {
    public $spec_needRespawn = false;
    private $forceMovement;

    /**
     * SpecterPlayer constructor.
     *
     * @param SourceInterface $interface
     * @param string $ip
     * @param int $port
     */
    public function __construct(SourceInterface $interface, string $ip, int $port){
        parent::__construct($interface, $ip, $port);
    }

    /**
     * Gets the force movement of the player.
     *
     * @return Vector3
     */
    public function getForceMovement(): ?Vector3 {
        return $this->forceMovement;
    }

    /**
     * Gets the session adapter for the player.
     *
     * @return PlayerNetworkSessionAdapter|null
     */
    public function getSessionAdapter(): ?PlayerNetworkSessionAdapter {
        return $this->sessionAdapter ?? null;
    }

    /**
     * Set force movement for the player.
     *
     * @param Vector3 $forceMovement
     */
    public function setForceMovement(Vector3 $forceMovement): void {
        $this->forceMovement = $forceMovement;
    }

    /**
     * Sets whether the player needs to respawn.
     *
     * @param bool $spec_needRespawn
     */
    public function setNeedRespawn(bool $spec_needRespawn): void {
        $this->spec_needRespawn = $spec_needRespawn;
    }
}
