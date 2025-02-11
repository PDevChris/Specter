<?php

namespace specter\network;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\NetworkInterface;
use specter\Specter;

class SpecterInterface implements NetworkInterface {
    private Specter $plugin;
    private array $sessions = [];

    public function __construct(Specter $plugin) {
        $this->plugin = $plugin;
    }

    public function start(): void {}

    public function shutdown(): void {}

    public function openSession(string $name, string $address, int $port): bool {
        if (isset($this->sessions[$name])) {
            return false; // Session already exists
        }

        $this->sessions[$name] = new SpecterPlayer($this, $address, $port);
        return true;
    }

    public function closeSession(NetworkSession $session, string $reason = "Client disconnect."): void {
        foreach ($this->sessions as $name => $specterPlayer) {
            if ($specterPlayer === $session) {
                unset($this->sessions[$name]);
                break;
            }
        }
    }

    public function queueReply(ServerboundPacket $packet, string $name): void {
        if (isset($this->sessions[$name])) {
            $this->sessions[$name]->handleDataPacket($packet);
        }
    }

    public function putPacket(NetworkSession $session, ServerboundPacket $packet, bool $needACK = false, int $reliability = 0): ?int {
        return null; // Specter doesn't send actual packets over the network
    }

    public function setName(string $name): void {}

    public function tick(): void {}

    public function getProtocolVersion(): int {
        return ProtocolInfo::CURRENT_PROTOCOL;
    }

    public function getName(): string {
        return "SpecterInterface";
    }
}
