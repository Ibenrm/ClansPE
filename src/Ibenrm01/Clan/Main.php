<?php

namespace Ibenrm01\Clan;

use pocketmine\{
    Server, Player
};
use pocketmine\plugin\{
    Plugin, PluginBase
};
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\level\{
    Level, Position
};
use onebone\economyapi\EconomyAPI;
use pocketmine\command\ConsoleCommandSender;

class Main extends PluginBase implements Listener {

    const MSG = "§l§eCLANS §7// §r";

    //DATABASE CLANS
    public $data;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        new Config($this->getDataFolder()."database.yml", Config::YAML);
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null){
            $this->getLogger()->error("You do not have EconomyAPI, please install in poggit!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        sleep(1);
        $this->data = yaml_parse(file_get_contents($this->getDataFolder()."database.yml"));
        $this->Mtop = new Config($this->getDataFolder()."topmember.yml", Config::YAML);
        $this->Ktop = new Config($this->getDataFolder()."topkdr.yml", Config::YAML);
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
            $this->getServer()->getCommandMap()->register("clans", new Commands\ClanCommand($this));
        }
        //REGISTER
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
            $this->getServer()->getPluginManager()->registerEvents(new Event\EventListener($this), $this);
        }
    }

    public function onDisable(){
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->data["players"][$pl->getName()]["invited"])){
                unset($this->data["players"][$pl->getName()]["invited"]);
            } elseif(isset($this->data["players"][$pl->getName()]["acceptPromote"])){
                unset($this->data["players"][$pl->getName()]["acceptPromote"]);
            } elseif(isset($this->data["players"][$pl->getName()]["acceptLeaders"])){
                unset($this->data["players"][$pl->getName()]["acceptLeaders"]);
            }
        endforeach;
            if(isset($this->data["players"])){
                foreach(array_keys($this->data["players"]) as $list) :
                    $nametag = $this->data["players"][$list]["nametag"];
                    if(isset($this->data["players"][$nametag]["clan"])){
                        if(!isset($this->data["clan"][$this->data["players"][$nametag]["clan"]])){
                            $this->data["offline"][$nametag]["clan"] = $this->data["players"][$nametag]["clan"];
                        }
                    }
                endforeach;
                    file_put_contents($this->getDataFolder()."database.yml", yaml_emit($this->data));
            } else {
                file_put_contents($this->getDataFolder()."database.yml", yaml_emit($this->data));
            }
    }

    /**
     * @param Player $player
     * @param string $label
     */
    public function createClan(Player $player, string $label){
        if(!$player->hasPermission("create.clan")){
            $player->sendMessage(self::MSG."§cYou do not have permissions!");
            return;
        }
        if(isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou already have clans");
            return;
        }
        if(isset($this->data["clan"][$label])){
            $player->sendMessage(self::MSG."§cCLANS §d".$label."§c Already created");
            return;
        }
        date_default_timezone_set($this->getConfig()->get("time-zone"));
        $this->data["clan"][$label]["date"] = date($this->getConfig()->get("date-time"));
        $this->data["clan"][$label]["owner"] = $player->getName();
        $this->data["clan"][$label]["members"] = 1;
        $this->data["clan"][$label]["kdr"] = 0;
        $this->data["clan"][$label]["bank"] = 0;
        $this->data["players"][$player->getName()]["clan"] = $label;
        $this->data["players"][$player->getName()]["nametag"] = $player->getName();
        $player->sendMessage(self::MSG."§aCreated Clans §d".$label." §ain §b".date("Y-m-d:H:i:s"));
        //TOP
        $this->Mtop->setNested($label, 1);
        $this->Ktop->setNested($label, 0);
        $this->Mtop->save();
        $this->Ktop->save();
        return;
    }

    /**
     * @param Player $player
     * @param string $label
     * @param $target
     */
    public function inviteClan(Player $player, string $label, $target){
        if(!isset($this->data["clan"][$label])){
            $player->sendMessage(self::MSG."§cClan §d".$label."§c not found");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou do not have clans");
            return;
        }
        if(isset($this->data["players"][$target->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cTarget §d".$target->getName()."§c already join clan");
            return;
        }
        if(!$player->hasPermission("access.clan")){
            $player->sendMessage(self::MSG."§cYou do not have permissions");
            return;
        }
        if($this->data["players"][$player->getName()]["clan"] != $label){
            $player->sendMessage(self::MSG."§cYou input clan is invalid");
            return;
        }
        $this->data["players"][$target->getName()]["invited"] = $label;
        $player->sendMessage(self::MSG."§aSuccess invited §d".$target->getName());
        $target->sendMessage(self::MSG."§aYou are invited to clan §d".$label);
        return;
    }

    /**
     * @param Player $player
     * @param string $label
     */
    public function deleteClan(Player $player, string $label){
        if(!isset($this->data["clan"][$label])){
            $player->sendMessage(self::MSG."§cClan §d".$label."§c not found");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou do not have clans");
            return;
        }
        if($label != $this->data["players"][$player->getName()]["clan"]){
            $player->sendMessage(self::MSG."§cYou do not have permissions, delete clan §d".$label);
            return;
        }
        if($player->getName() != $this->data["clan"][$label]["owner"]){
            $player->sendMessage(self::MSG."§cYou are not clan owner");
            return;
        }
        unset($this->data["clan"][$label]);
        $this->Mtop->removeNested($label);
        $this->Ktop->removeNested($label);
        $this->Mtop->save();
        $this->Ktop->save();
        foreach(array_keys($this->data["players"]) as $list) :
            if(isset($this->data["players"][$list]["clan"])){
                if($this->data["players"][$list]["clan"] == $label){
                    $nametag = $this->data["players"][$list]["nametag"];
                    unset($this->data["players"][$nametag]);
                    $target = $this->getServer()->getPlayer($nametag);
                    if($target instanceof Player){
                        if($target->hasPermission("access.clan")){
                            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'unsetuperm "'.$player->getName().'" access.clan');
                        }
                        $target->sendMessage(self::MSG."§aClans §d".$label."§b has been delete");
                    } else {
                        $this->data["offline"][$nametag]["clan"] = $label;
                    }
                }
            }
        endforeach;
            return;
    }

    /**
     * @param Player $player
     * @param string $label
     * @param $target
     */
    public function kickClan(Player $player, string $label, $target){
        if(!isset($this->data["clan"][$label])){
            $player->sendMessage(self::MSG."§cClan §d".$label."§c not found");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou do not have clans");
            return;
        }
        if(!$player->hasPermission("access.clan")){
            $player->sendMessage(self::MSG."§cYou do not have permissions!");
            return;
        }
        if(!isset($this->data["players"][$target->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c do not have clan");
            return;
        }
        if($this->data["players"][$target->getName()]["clan"] != $this->data["players"][$player->getName()]["clan"]){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c another member clan");
            return;
        }
        if($target->hasPermission("access.clan")){
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'unsetuperm "'.$target->getName().'" access.clan');
        }
        if($target->getName() == $this->data["clan"][$label]["owner"]){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c is owner");
            return;
        }
        $this->data["clan"][$label]["members"] -= 1;
        $this->Mtop->setNested($label, $this->Mtop->getAll()[$label] - 1);
        $this->Mtop->save();
        unset($this->data["players"][$target->getName()]);
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->data["players"][$pl->getName()]["clan"])){
                if($this->data["players"][$pl->getName()]["clan"] == $label){
                    $pl->sendMessage(self::MSG."§aPlayer §d".$target->getName()."§a kick clan §d".$label);
                }
            }
        endforeach;
            return;
    }

    /**
     * @param Player $player
     */
    public function quitClan(Player $player){
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou do not have clans");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["clan"][$clan])){
            unset($this->data["players"][$player->getName()]["clan"]);
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c not found");
            return;
        }
        if($player->hasPermission("access.clan")){
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'unsetuperm "'.$player->getName().'" access.clan');
        }
        if($player->getName() == $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cYou is owner, can't quit, §bPlease /clan delete (name-clan)");
            return;
        }
        $this->data["clan"][$clan]["members"] = $this->data["clan"][$clan]["members"] - 1;
        unset($this->data["players"][$player->getName()]);
        $this->Mtop->setNested($clan, $this->Mtop->getAll()[$clan] - 1);
        $this->Mtop->save();
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->data["players"][$pl->getName()]["clan"])){
                if($this->data["players"][$pl->getName()]["clan"] == $clan){
                    $pl->sendMessage(self::MSG."§aPlayer §d".$player->getName()."§a Quit Clans §d".$clan);
                }
            }
        endforeach;
            return;
    }

    /**
     * @param Player $player
     */
    public function acceptInvited(Player $player){
        if(!isset($this->data["players"][$player->getName()]["invited"])){
            $player->sendMessage(self::MSG."§cYou do not have a clan invited");
            return;
        }
        if(isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cYou already join clans");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["invited"];
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan has been §ddelete");
            unset($this->data["players"][$player->getName()]["invited"]);
            return;
        }
        $this->data["clan"][$clan]["members"] += 1;
        $this->data["players"][$player->getName()]["clan"] = $clan;
        $this->data["players"][$player->getName()]["nametag"] = $player->getName();
        unset($this->data["players"][$player->getName()]["invited"]);
        $this->Mtop->setNested($clan, $this->Mtop->getAll()[$clan] + 1);
        $this->Mtop->save();
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->data["players"][$pl->getName()]["clan"])){
                if($this->data["players"][$pl->getName()]["clan"] == $clan){
                    $pl->sendMessage(self::MSG."§aPlayer §d".$player->getName()."§a Joined Clans §d".$clan);
                }
            }
        endforeach;
            return;
    }

    /**
     * @param Player $player
     * @param $target
     */
    public function clanLeaders(Player $player, $target){
        if(!isset($this->data["players"][$target->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c do not have clans!");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."You do not have clans!");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c Already DELETE");
            return;
        }
        if($player->getName() != $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cYou do not have permission!");
            return;
        }
        if($this->data["players"][$target->getName()]["clan"] != $clan){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c another member clan");
            return;
        }
        if($target->getName() == $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c already Owner clans §d".$clan);
            return;
        }
        $this->data["players"][$target->getName()]["acceptLeaders"] = $clan;
        $player->sendMessage(self::MSG."§aSuccess invited leader to §d".$target->getName());
        $target->sendMessage(self::MSG."§aYou invited to leader from §d".$player->getName()."§a clans §d".$clan);
        $target->sendMessage(self::MSG."§b/clans accept, §afor accept leaders");
        return;
    }

    /**
     * @param Player $player
     * @param $target
     */
    public function clanPromote(Player $player, $target){
        if(!isset($this->data["players"][$target->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c do not have clans!");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."You do not have clans!");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c Already DELETE");
            return;
        }
        if($player->getName() != $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cYou do not have permission!");
            return;
        }
        if($this->data["players"][$target->getName()]["clan"] != $clan){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c another member clan");
            return;
        }
        if($target->getName() == $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c already Owner clans §d".$clan);
            return;
        }
        if($target->getName() == $player->getName()){
            $player->sendMessage(self::MSG."§cYou do not have promote self");
            return;
        }
        $this->data["players"][$target->getName()]["acceptPromote"] = $clan;
        $player->sendMessage(self::MSG."§aSuccess invited co-owner to §d".$target->getName());
        $target->sendMessage(self::MSG."§aYou invited to co-owner from §d".$player->getName()."§a clans §d".$clan);
        $target->sendMessage(self::MSG."§b/clans accept, §afor accept co-owner");
        return;
    }

    /**
     * @param Player $player
     * @param $target
     */
    public function clanDemote(Player $player, $target){
        if(!isset($this->data["players"][$target->getName()]["clan"])){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c do not have clans!");
            return;
        }
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            $player->sendMessage(self::MSG."You do not have clans!");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c Already DELETE");
            return;
        }
        if($player->getName() != $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cYou do not have permission!");
            return;
        }
        if($this->data["players"][$target->getName()]["clan"] != $clan){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c another member clan");
            return;
        }
        if($target->getName() == $this->data["clan"][$clan]["owner"]){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c is Owner clans §d".$clan);
            return;
        }
        if(!$target->hasPermission("access.clan")){
            $player->sendMessage(self::MSG."§cPlayer §d".$target->getName()."§c is already members");
            return;
        }
        if($target->getName() == $player->getName()){
            $player->sendMessage(self::MSG."§cYou do not have demote self");
            return;
        }
        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'unsetuperm "'.$player->getName().'" access.clan');
        $player->sendMessage(self::MSG."§aSuccessfully demote a §d".$target->getName());
        $target->sendMessage(self::MSG."§aYou demote co-owner, from §d".$player->getName());
        return;  
    }

    /**
     * @param player $player
     */
    public function acceptLeaders(Player $player){
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            unset($this->data["players"][$player->getName()]);
            $player->sendMessage(self::MSG."§cYou do not have clans!");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["players"][$player->getName()]["acceptLeaders"])){
            $player->sendMessage(self::MSG."§cYou do not have invited");
            return;
        }
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c has been DELETE");
            return;
        }
        unset($this->data["players"][$player->getName()]["acceptLeaders"]);
        $this->data["clan"][$clan]["owner"] = $player->getName();
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->plugin->data["players"][$pl->getName()]["clan"])){
                if($this->plugin->data["players"][$pl->getName()]["clan"] == $clan){
                    $pl->sendMessage(self::MSG."§aPlayer §d".$player->getName()."§a is new owner / leaders");
                }
            }
        endforeach;
            return;
    }

    /**
     * @param player $player
     */
    public function acceptPromote(Player $player){
        if(!isset($this->data["players"][$player->getName()]["clan"])){
            unset($this->data["players"][$player->getName()]);
            $player->sendMessage(self::MSG."§cYou do not have clans!");
            return;
        }
        $clan = $this->data["players"][$player->getName()]["clan"];
        if(!isset($this->data["players"][$player->getName()]["acceptPromote"])){
            $player->sendMessage(self::MSG."§cYou do not have invited");
            return;
        }
        if(!isset($this->data["clan"][$clan])){
            $player->sendMessage(self::MSG."§cClan §d".$clan."§c has been DELETE");
            return;
        }
        unset($this->data["players"][$player->getName()]["acceptPromote"]);
        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), 'setuperm "'.$player->getName().'" access.clan');
        foreach($this->getServer()->getOnlinePlayers() as $pl) :
            if(isset($this->plugin->data["players"][$pl->getName()]["clan"])){
                if($this->plugin->data["players"][$pl->getName()]["clan"] == $clan){
                    $pl->sendMessage(self::MSG."§aPlayer §d".$player->getName()."§a is new co-owner");
                }
            }
        endforeach;
            return;
    }
}