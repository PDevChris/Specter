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
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use specter\Specter;
use pocketmine\network\SourceInterface;

class SpecterInterface implements SourceInterface
{
    /** @var  \SplObjectStorage<SpecterPlayer> */
    private $sessions;
    /** @var  Specter */
    private $specter;
    /** @var  array */
    private $ackStore;
    /** @var  array */
    private $replyStore;

    public function __construct(Specter $specter)
    {
        $this->specter = $specter;
        $this->sessions = new \SplObjectStorage();
        $this->ackStore = [];
        $this->replyStore = [];
    }

    public function start(): void
    {
        //NOOP
    }

    /**
     * Sends a DataPacket to the interface, returns a unique identifier for the packet if $needACK is true.
     *
     * @param Player $player
     * @param DataPacket $packet
     * @param bool $needACK
     * @param bool $immediate
     *
     * @return int|null
     */
    public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true): ?int
    {
        if ($player instanceof SpecterPlayer) {
            switch (get_class($packet)) {
                case ResourcePacksInfoPacket::class:
                    $pk = new ResourcePackClientResponsePacket();
                    $pk->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
                    $this->sendPacket($player, $pk);
                    break;
                case TextPacket::class:
                    /** @var TextPacket $packet */
                    $type = "Unknown";
                    switch ($packet->type) {
                        case TextPacket::TYPE_CHAT:
                            $type = "Chat";
                            break;
                        case TextPacket::TYPE_RAW:
                            $type = "Message";
                            break;
                        case TextPacket::TYPE_POPUP:
                            $type = "Popup";
                            break;
                        case TextPacket::TYPE_TIP:
                            $type = "Tip";
                            break;
                        case TextPacket::TYPE_TRANSLATION:
                            $type = "Translation (with params: " . implode(", ", $packet->parameters) . ")";
                            break;
                    }
                    $this->specter->getLogger()->info(TextFormat::LIGHT_PURPLE . "$type to {$player->getName()}: " . TextFormat::WHITE . $packet->message);
                    break;
                case SetHealthPacket::class:
                    /** @var SetHealthPacket $packet */
                    if ($packet->health <= 0) {
                        if ($this->specter->getConfig()->get("autoRespawn")) {
                            $pk = new RespawnPacket();
                            $this->replyStore[$player->getName()][] = $pk;
                            $respawnPK = new PlayerActionPacket();
                            $respawnPK->action = PlayerActionPacket::ACTION_RESPAWN;
                            $respawnPK->entityRuntimeId = $player->getId();
                            $this->replyStore[$player->getName()][] = $respawnPK;
                        }
                    } else {
                        $player->spec_needRespawn = true;
                    }
                    break;
                case StartGamePacket::class:
                    $pk = new RequestChunkRadiusPacket();
                    $pk->radius = 8;
                    $this->replyStore[$player->getName()][] = $pk;
                    break;
                case PlayStatusPacket::class:
                    /** @var PlayStatusPacket $packet */
                    switch ($packet->status) {
                        case PlayStatusPacket::PLAYER_SPAWN:
                            // Custom logic for player spawn if needed.
                            break;
                    }
                    break;
                case MovePlayerPacket::class:
                    /** @var MovePlayerPacket $packet */
                    $eid = $packet->entityRuntimeId;
                    if ($eid === $player->getId() && $player->isAlive() && $player->spawned === true && $player->getForceMovement() !== null) {
                        $packet->mode = MovePlayerPacket::MODE_NORMAL;
                        $packet->yaw += 25; // Fix for yaw adjustments
                        $this->replyStore[$player->getName()][] = $packet;
                    }
                    break;
                case BatchPacket::class:
                    /** @var BatchPacket $packet */
                    $packet->offset = 1;
                    $packet->decode();

                    foreach ($packet->getPackets() as $buf) {
                        $pk = PacketPool::getPacketById(ord($buf[0]));
                        if (!$pk->canBeBatched()) {
                            throw new \InvalidArgumentException("Received invalid " . get_class($pk) . " inside BatchPacket");
                        }

                        $pk->setBuffer($buf, 1);
                        $this->putPacket($player, $pk, false, $immediate);
                    }
                    break;
                case SetTitlePacket::class:
                    /** @var SetTitlePacket $packet */
                    $this->specter->getLogger()->info(TextFormat::LIGHT_PURPLE . "Title to {$player->getName()}: " . TextFormat::WHITE . $packet->text);
                    break;
            }

            if ($needACK) {
                $id = count($this->ackStore[$player->getName()]);
                $this->ackStore[$player->getName()][] = $id;
                $this->specter->getLogger()->info("Created ACK.");
                return $id;
            }
        }
        return null;
    }

    /**
     * Terminates the connection.
     *
     * @param Player $player
     * @param string $reason
     */
    public function close(Player $player, string $reason = "unknown reason"): void
    {
        $this->sessions->detach($player);
        unset($this->ackStore[$player->getName()]);
        unset($this->replyStore[$player->getName()]);
    }

    public function openSession($username, $address = "SPECTER", $port = 19133): bool
    {
        if (!isset($this->replyStore[$username])) {
            $player = new SpecterPlayer($this, $address, $port);
            $this->sessions->attach($player, $username);
            $this->ackStore[$username] = [];
            $this->replyStore[$username] = [];
            $this->specter->getServer()->addPlayer($player);

            $pk = new LoginPacket();
            $pk->username = $username;
            $pk->protocol = ProtocolInfo::CURRENT_PROTOCOL;
            $pk->clientUUID = UUID::fromData($address, $port, $username)->toString();
            $pk->clientId = 1;
            $pk->xuid = "xuid here";
            $pk->identityPublicKey = "key here";
            $pk->clientData["SkinResourcePatch"] = base64_encode('{"geometry": {"default": "geometry.humanoid.custom"}}');
            $pk->clientData["SkinId"] = "Specter";
            $pk->clientData["SkinData"] = base64_encode("");
            $this->putPacket($player, $pk, false);
            return true;
        }
        return false;
    }

    public function handleSession(SpecterPlayer $player, string $data): void
    {
        // Handle incoming session data
        // Decode the packet and process it
    }
}
