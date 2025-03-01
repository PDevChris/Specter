<?php

namespace specter;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use specter\network\SpecterImplementation;
use specter\network\SpecterInterface; 
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Specter extends PluginBase implements Listener {
    private static Specter $instance;
    private SpecterInterface $interface;
    private SpecterImplementation $specterImplementation;


    public static function getInstance(): Specter {
        return self::$instance;
    }

    public function onEnable(): void {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function __construct(
    pocketmine\plugin\PluginLoader $loader, 
    pocketmine\Server $server, 
    pocketmine\plugin\PluginDescription $description, 
    string $dataFolder, 
    string $file, 
    pocketmine\plugin\ResourceProvider $resourceProvider
) {
    parent::__construct($loader, $server, $description, $dataFolder, $file, $resourceProvider);
}

   //  $this->specterImplementation = new SpecterImplementation();


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command must be used in-game.");
            return true;
        }

        if ($command->getName() === "specter") {
            if (empty($args)) {
                $sender->sendMessage(TextFormat::YELLOW . "Usage: /specter <spawn|move|attack|chat|respawn>");
                return true;
            }

            $subCommand = array_shift($args);
            switch ($subCommand) {
                case "spawn":
                    if (count($args) < 1) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /specter spawn <name>");
                        return true;
                    }
                    $name = array_shift($args);
                    $this->specterImplementation->createBot($name, $player->getWorld(), $player->getPosition());
                    break;

                case "move":
                    if (count($args) < 4) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /specter move <p> <x> <y> <z>");
                        return true;
                    }
                    [$p, $x, $y, $z] = $args;
                    $this->interface->moveBot($p, new Vector3((float)$x, (float)$y, (float)$z));
                    break;

                case "attack":
                    if (count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /specter attack <attacker> <victim>");
                        return true;
                    }
                    [$attacker, $victim] = $args;
                    $this->interface->botAttack($attacker, $victim);
                    break;

                case "chat":
                    if (count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /specter chat <p> <message>");
                        return true;
                    }
                    $p = array_shift($args);
                    $message = implode(" ", $args);
                    $this->interface->botChat($p, $message);
                    break;

                case "respawn":
                    if (count($args) < 1) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /specter respawn <player>");
                        return true;
                    }
                    $playerName = array_shift($args);
                    $this->interface->respawnBot($playerName);
                    break;

                default:
                    $sender->sendMessage(TextFormat::RED . "Unknown subcommand.");
                    return true;
            }
            return true;
        }
        return false;
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        if ($this->interface->isBot($event->getPlayer()->getName())) {
            $event->cancel(true);
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $damager = $event->getDamager();
        if ($damager instanceof Player && $this->interface->isBot($damager->getName())) {
            $this->interface->botAttack($damager->getName(), $event->getEntity()->getName());
        }
    }
}
