<?php

namespace specter;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerIllegalMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use specter\network\SpecterInterface;
use specter\network\SpecterPlayer;

class Specter extends PluginBase implements Listener {
    private SpecterInterface $interface;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->interface = new SpecterInterface($this);
        $this->getServer()->getNetwork()->registerInterface($this->interface);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!isset($args[0])) return false;

        switch ($args[0]) {
            case 'spawn':
            case 'new':
            case 'add':
            case 's':
                if (!isset($args[1])) return false;
                if ($this->interface->openSession($args[1], $args[2] ?? "SPECTER", (int)($args[3] ?? 19133))) {
                    $sender->sendMessage("Session started.");
                } else {
                    $sender->sendMessage("Failed to open session.");
                }
                return true;

            case 'kick':
            case 'quit':
            case 'close':
            case 'q':
                if (!isset($args[1])) {
                    $sender->sendMessage("Usage: /specter quit <player>");
                    return true;
                }
                $player = $this->getServer()->getPlayerExact($args[1]);
                if ($player instanceof SpecterPlayer) {
                    $player->disconnect("Client disconnect.");
                } else {
                    $sender->sendMessage("That player isn't managed by Specter.");
                }
                return true;

            case 'move':
            case 'm':
            case 'teleport':
            case 'tp':
                if (!isset($args[4])) {
                    $sender->sendMessage("Usage: /specter move <player> <x> <y> <z>");
                    return true;
                }
                $player = $this->getServer()->getPlayerExact($args[1]);
                if ($player instanceof SpecterPlayer) {
                    $pk = MovePlayerPacket::create(
                        $player->getId(),
                        new Vector3((float)$args[2], (float)$args[3] + $player->getEyeHeight(), (float)$args[4]),
                        0, 0, 0, 0, 0, 0, null
                    );
                    $this->interface->queueReply($pk, $player->getName());
                } else {
                    $sender->sendMessage("That player isn't managed by Specter.");
                }
                return true;

            case 'respawn':
            case 'r':
                if (!isset($args[1])) {
                    $sender->sendMessage("Usage: /specter respawn <player>");
                    return true;
                }
                $player = $this->getServer()->getPlayerExact($args[1]);
                if ($player instanceof SpecterPlayer) {
                    if (!$player->needsRespawn()) {
                        $this->interface->queueReply(new RespawnPacket(), $player->getName());
                        $respawnPK = PlayerActionPacket::create($player->getId(), PlayerActionPacket::ACTION_RESPAWN, 0, 0, 0);
                        $this->interface->queueReply($respawnPK, $player->getName());
                    } else {
                        $sender->sendMessage("{$player->getName()} doesn't need respawning.");
                    }
                } else {
                    $sender->sendMessage("That player isn't a Specter player.");
                }
                return true;
        }
        return false;
    }

    public function onIllegalMove(PlayerIllegalMoveEvent $event): void {
        if ($event->getPlayer() instanceof SpecterPlayer && $this->getConfig()->get('allowIllegalMoves', false)) {
            $event->cancel();
        }
    }
}
