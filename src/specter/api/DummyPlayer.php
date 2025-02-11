<?php

namespace specter\api;

use pocketmine\Server;
use specter\network\SpecterPlayer;
use specter\Specter;

class DummyPlayer {
    private Server $server;
    private string $name;

    public function __construct(string $name, string $address = "SPECTER", int $port = 19133, ?Server $server = null) {
        $this->name = $name;
        $this->server = $server ?? Server::getInstance();

        if (!$this->getSpecter()->getInterface()->openSession($name, $address, $port)) {
            throw new \RuntimeException("Failed to open session for player: $name.");
        }
    }

    /**
     * Get the SpecterPlayer instance if available.
     * @return SpecterPlayer|null
     */
    public function getPlayer(): ?SpecterPlayer {
        $player = $this->server->getPlayerExact($this->name);
        return ($player instanceof SpecterPlayer) ? $player : null;
    }

    /**
     * Close the dummy player session.
     */
    public function close(): void {
        $player = $this->getPlayer();
        if ($player !== null) {
            $player->close("", "Client disconnect.");
        }
    }

    /**
     * Get the active Specter plugin instance.
     * @return Specter
     * @throws \RuntimeException
     */
    protected function getSpecter(): Specter {
        $plugin = $this->server->getPluginManager()->getPlugin("Specter");

        if ($plugin instanceof Specter && $plugin->isEnabled()) {
            return $plugin;
        }

        throw new \RuntimeException("Specter plugin is not available or disabled.");
    }
}
