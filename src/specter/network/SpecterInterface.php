<?php

namespace specter\network;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\SetHealthPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\SourceInterface;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use specter\Specter;

class SpecterInterface implements SourceInterface
{
    private \SplObjectStorage $sessions;
    private Specter $specter;
    private array $ackStore;
    private array $replyStore;

    public function __construct(Specter $specter)
    {
        $this->specter = $specter;
        $this->sessions = new \SplObjectStorage();
        $this->ackStore = [];
        $this->replyStore = [];
    }

    public function start(): void {}

    public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true): ?int
    {
        if ($player instanceof SpecterPlayer) {
            switch (get_class($packet)) {
                case ResourcePacksInfoPacket::class:
                    $pk = ResourcePackClientResponsePacket::create(ResourcePackClientResponsePacket::STATUS_COMPLETED);
                    $this->sendPacket($player, $pk);
                    break;
                case TextPacket::class:
                    $this->specter->getLogger()->info(TextFormat::LIGHT_PURPLE . "Message to {$player->getName()}: " . TextFormat::WHITE . $packet->message);
                    break;
                case SetHealthPacket::class:
                    if ($packet->health <= 0 && $this->specter->getConfig()->get("autoRespawn")) {
                        $this->replyStore[$player->getName()][] = RespawnPacket::create();
                        $this->replyStore[$player->getName()][] = PlayerActionPacket::create(PlayerActionPacket::ACTION_RESPAWN, $player->getId());
                    }
                    break;
                case StartGamePacket::class:
                    $this->replyStore[$player->getName()][] = RequestChunkRadiusPacket::create(8);
                    break;
            }
            if ($needACK) {
                $id = count($this->ackStore[$player->getName()] ?? []);
                $this->ackStore[$player->getName()][] = $id;
                return $id;
            }
        }
        return null;
    }

    public function close(Player $player, string $reason = "unknown reason"): void
    {
        $this->sessions->detach($player);
        unset($this->ackStore[$player->getName()], $this->replyStore[$player->getName()]);
    }

    public function setName(string $name): void {}

    public function openSession(string $username, string $address = "SPECTER", int $port = 19133): bool
    {
        if (!isset($this->replyStore[$username])) {
            $player = new SpecterPlayer($this, $address, $port);
            $this->sessions->attach($player, $username);
            $this->ackStore[$username] = [];
            $this->replyStore[$username] = [];
            $this->specter->getServer()->addPlayer($player);

            $pk = LoginPacket::create(
                ProtocolInfo::CURRENT_PROTOCOL,
                UUID::fromData($address, $port, $username)->toString(),
                "xuid here",
                "key here",
                "Specter",
                base64_encode(str_repeat("\x80", 64 * 32 * 4))
            );
            $this->sendPacket($player, $pk);
            return true;
        }
        return false;
    }
}
