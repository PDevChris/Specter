<?php

declare(strict_types=1);

namespace specter;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerIllegalMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Specter extends PluginBase implements Listener {
    private static Specter $instance;
    private SpecterInterface $interface;

    public function onEnable(): void {
        self::$instance = $this;
        $this->interface = new SpecterInterface($this);
        $this->getServer()->getNetwork()->setInterfaces([$this->interface]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public static function getInstance(): Specter {
        return self::$instance;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command must be used in-game.");
            return true;
        }
        
        switch ($command->getName()) {
            case "specter":
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
                        $this->interface->createBot($name, $sender->getPosition());
                        $sender->sendMessage(TextFormat::GREEN . "Bot $name spawned.");
                        break;
                    
                    case "move":
                        if (count($args) < 4) {
                            $sender->sendMessage(TextFormat::RED . "Usage: /specter move <p> <x> <y> <z>");
                            return true;
                        }
                        [$p, $x, $y, $z] = $args;
                        $this->interface->moveBot($p, new Vector3((float)$x, (float)$y, (float)$z));
                        $sender->sendMessage(TextFormat::GREEN . "Bot $p moved.");
                        break;
                    
                    case "attack":
                        if (count($args) < 2) {
                            $sender->sendMessage(TextFormat::RED . "Usage: /specter attack <attacker> <victim>");
                            return true;
                        }
                        [$attacker, $victim] = $args;
                        $this->interface->botAttack($attacker, $victim);
                        $sender->sendMessage(TextFormat::GREEN . "Bot $attacker attacked $victim.");
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
                        break;
                }
                return true;
        }
        return false;
    }

    public function onPlayerIllegalMove(PlayerIllegalMoveEvent $event): void {
        $player = $event->getPlayer();
        if ($this->interface->isBot($player->getName())) {
            $event->cancel();
        }
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        if ($damager instanceof Player && $this->interface->isBot($damager->getName())) {
            $victim = $event->getEntity();
            $this->interface->botAttack($damager->getName(), $victim->getName());
        }
    }


    /*
        /**
         * @priority MONITOR
         * @param DataPacketReceiveEvent $pk
         *
        public function onDataPacketRecieve(DataPacketReceiveEvent $pk){
            if($pk->getPacket() instanceof RequestChunkRadiusPacket){
                $this->getLogger()->info("RADIUS:" . $pk->getPacket()->radius);
            }
            $this->getLogger()->info("GOT:" . get_class($pk->getPacket()));
        }

        /**
         * @priority MONITOR
         * @param DataPacketSendEvent $pk
         *
        public function onDataPacketSend(DataPacketSendEvent $pk){
            if(!($pk->getPacket() instanceof SetTimePacket)) {
                $this->getLogger()->info("SEND:" . get_class($pk->getPacket()));
            }
        }
    */
    /*
     * @return SpecterInterface
     */
   /* public function getInterface()
    {
        return $this->interface;
    }

    /**
     * @return null|\icontrolu\iControlU
     */
    public function getICU()
    {
        return $this->getServer()->getPluginManager()->getPlugin("iControlU");
    }
}
