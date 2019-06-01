<?php
namespace AbilityManager;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class EventListener implements Listener {
    private $plugin;

    public function __construct(AbilityManager $plugin) {
        $this->plugin = $plugin;
    }

    public function onPacketReceived(DataPacketReceiveEvent $ev) {
        $pk = $ev->getPacket();
        $player = $ev->getPlayer();
        if ($pk instanceof SetLocalPlayerAsInitializedPacket) {
            if (in_array($player->getName(), $this->plugin->tutorial->data["player"])) {
                $this->plugin->getScheduler()->scheduleDelayedTask(
                        new class($this->plugin, $ev->getPlayer()) extends Task {
                            public function __construct(AbilityManager $plugin, Player $player) {
                                $this->plugin = $plugin;
                                $this->player = $player;
                            }

                            public function onRun($currentTick) {
                                $this->plugin->Question_1($this->player);
                            }
                        }, 3 * 20);
            }
        }
    }

    public function onJoin(PlayerJoinEvent $ev) {
        $name = $ev->getPlayer()->getName();
        if (!isset($this->plugin->adata[$name])) {
            $this->plugin->adata[$name]["천직"] = "-";
            $this->plugin->adata[$name]["재능"] = "-";
            $this->plugin->adata[$name]["재능치"] = [];
            $this->plugin->adata[$name]["천재능"] = [];
            $this->plugin->adata[$name]["개인천성"] = [];
            $this->plugin->adata[$name]["개인천성"]["모험가의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["지혜의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["근력의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["민첩의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["마력의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["운의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["상인의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["제작의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["채집, 채광의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["멸망의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["전설의 축복"] = 0;
            $this->plugin->adata[$name]["개인천성"]["신의 축복"] = 0;
        }
        /*$this->plugin->getScheduler()->scheduleDelayedTask(
          new class($this->plugin, $ev->getPlayer()) extends Task{
            public function __construct(AbilityManager $plugin, Player $player){
              $this->plugin = $plugin;
              $this->player = $player;
           }
           public function onRun($currentTick){
             $this->plugin->Question_1($this->player);
           }
        }, 220);*/
    }

    public function onQuit(PlayerQuitEvent $ev) {
        if (isset($this->plugin->end[$ev->getPlayer()->getName()])) unset($this->plugin->end[$ev->getPlayer()->getName()]);
    }
}
