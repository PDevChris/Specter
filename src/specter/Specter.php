<?php
namespace specter;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use specter\network\SpecterInterface;
use specter\network\SpecterPlayer;

class Specter extends PluginBase {
    private SpecterInterface $interface;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->interface = new SpecterInterface($this);
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private Specter $plugin;
            public function __construct(Specter $plugin) {
                $this->plugin = $plugin;
            }
            public function onRun(): void {
                $timeout = $this->plugin->getConfig()->get("autoRemoveTimeout", 300);
                $time = time();

                foreach ($this->plugin->getInterface()->getSpecterPlayers() as $name => $player) {
                    if ($player instanceof SpecterPlayer && ($time - $player->getLastPlayed()) >= $timeout) {
                        $player->disconnect("Removed due to inactivity.");
                        $this->plugin->getInterface()->removeSession($name);
                        $this->plugin->getLogger()->info("Specter player '$name' was removed due to inactivity.");
                    }
                }
            }
        }, 20 * 60); // Runs every minute
    }

    public function getInterface(): SpecterInterface {
        return $this->interface;
    }
}
