<?php

namespace Ibenrm01\Clan\Event;

use pocketmine\{
    Server, Player
};
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\level\{
    Level, Position
};
use pocketmine\event\player\{
    PlayerDeathEvent, PlayerJoinEvent, PlayerQuitEvent
};
use pocketmine\event\entity\{
    EntityDamageEvent, EntityDamageByEntityEvent
};
use onebone\economyapi\EconomyAPI;
use Ibenrm01\Clan\Main;

class EventListener implements Listener {

    private $plugin;

    const MSG = "§l§eCLANS §7// §r";

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event){
        $pl = $event->getPlayer();
        if(isset($this->plugin->data["players"][$pl->getName()]["invited"])){
            unset($this->plugin->data["players"][$pl->getName()]["invited"]);
        } elseif(isset($this->plugin->data["players"][$pl->getName()]["acceptPromote"])){
            unset($this->plugin->data["players"][$pl->getName()]["acceptPromote"]);
        } elseif(isset($this->plugin->data["players"][$pl->getName()]["acceptLeaders"])){
            unset($this->plugin->data["players"][$pl->getName()]["acceptLeaders"]);
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if(isset($this->plugin->data["offline"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§aClans §d".$this->plugin->data["offline"][$player->getName()]["clan"]."§a has been delete");
            unset($this->plugin->data["players"][$player->getName()]);
            unset($this->plugin->data["offline"][$player->getName()]);
            if($player->hasPermission("access.clan")){
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'unsetuperm "'.$target->getName().'" access.clan');
                return;
            }
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onDeath(PlayerDeathEvent $event) {
        if($event->getPlayer()->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
            if($event->getPlayer()->getLastDamageCause()->getDamager() instanceof Player) {
                $damager = $event->getPlayer()->getLastDamageCause()->getDamager();
                if(isset($this->plugin->data["players"][$damager->getName()]["clan"])){
                    $clan = $this->plugin->data["players"][$damager->getName()]["clan"];
                    EconomyAPI::getInstance()->addMoney($damager, $this->plugin->getConfig()->get("getmoney"));
                    $this->plugin->data["clan"][$clan]["kdr"] += $this->plugin->getConfig()->get("getkdr");
                    $this->plugin->Ktop->setNested($clan, $this->plugin->Ktop->getAll()[$clan] + $this->plugin->getConfig()->get("getkdr"));
                    $this->plugin->Ktop->save();
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if($event->getCause() === EntityDamageEvent::CAUSE_FALL) return;
        if($event instanceof EntityDamageByEntityEvent){
            $damager = $event->getDamager();
            if($entity instanceof Player && $damager instanceof Player) {
                if(!isset($this->plugin->data["players"][$damager->getName()]["clan"])){
                    return;
                }
                if(!isset($this->plugin->data["players"][$entity->getName()]["clan"])){
                    return;
                }
                if($this->plugin->data["players"][$entity->getName()]["clan"] == $this->plugin->data["players"][$damager->getName()]["clan"]){
                    $event->setCancelled();
                    $damager->sendMessage(self::MSG."§cYou can't hit member clan");
                    return;
                }
            }
        }
    }
}
