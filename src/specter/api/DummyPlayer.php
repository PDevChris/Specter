<?php

declare(strict_types=1);

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
            throw new \Exception("Failed to open session.");
        }
    }
    
    public function getPlayer(): ?SpecterPlayer {
        $players = $this->server->getOnlinePlayers();
        foreach ($players as $player) {
            if ($player instanceof SpecterPlayer && strcasecmp($player->getName(), $this->name) === 0) {
                return $player;
            }
        }
        return null;
    }
    
    public function close(): void {
        $player = $this->getPlayer();
        if ($player !== null) {
            $player->disconnect("Client disconnect.");
        }
    }
    
    protected function getSpecter(): Specter {
        $plugin = $this->server->getPluginManager()->getPlugin("Specter");
        if ($plugin instanceof Specter && $plugin->isEnabled()) {
            return $plugin;
        }
        throw new \Exception("Specter is not available.");
    }
}
