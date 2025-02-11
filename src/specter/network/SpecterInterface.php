<?php
namespace specter\network;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Server;
use specter\Specter;
use specter\network\SpecterPlayer;

class SpecterInterface {
    private Specter $plugin;
    private array $sessions = [];

    public function __construct(Specter $plugin) {
        $this->plugin = $plugin;
    }

    public function openSession(string $name, string $address, int $port): bool {
        $maxPlayers = $this->plugin->getConfig()->get("maxSpecterPlayers", 10);

        if (count($this->sessions) >= $maxPlayers) {
            $this->plugin->getLogger()->warning("Cannot create Specter player: Max limit reached ($maxPlayers).");
            return false;
        }

        $server = Server::getInstance();
        $player = new SpecterPlayer($this, $address, $port);
        $player->setName($name);
        $server->addOnlinePlayer($player);

        $this->sessions[$name] = $player;
        return true;
    }

    public function removeSession(string $name): void {
        if (isset($this->sessions[$name])) {
            unset($this->sessions[$name]);
        }
    }

    public function getSpecterPlayers(): array {
        return $this->sessions;
    }
}
