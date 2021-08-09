<?php

declare(strict_types=1);
namespace Ibenrm01\Clan\Commands;

use pocketmine\command\{
    Command,
    PluginCommand,
    CommandSender
};
use pocketmine\Player;
use Ibenrm01\Clan\Main;
use onebone\economyapi\EconomyAPI;

/**
 * Class ClanCommand
 * @package Ibenrn01\Clan\Commands
 */
class ClanCommand extends PluginCommand {

    const MSG = "§l§eCLANS §7// §r";

    /**
    * ClanCommand constructor.
    * @param Main $plugin
    */
    public function __construct(Main $plugin){
        parent::__construct('clans', $plugin);
        $this->setAliases(['clan']);
        $this->setDescription('Clans Command');
        $this->plugin = $plugin;
    }

    /**
     * @param string $message
     * @param array $keys
     * 
     * @return string
     */
    public function replace(string $message, array $keys): string{
        foreach($keys as $word => $value){
            $message = str_replace("{".strtolower($word)."}", $value, $message);
        }
        return $message;
    }

    public function statsMe(Player $player){
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            if($data === null or $data >= 0){
                $player->sendMessage(self::MSG."§aThanks for open menu!");
            }
        });
        if(isset($this->plugin->data["players"][$player->getName()]["clan"])){
            $clan = $this->plugin->data["players"][$player->getName()]["clan"];
            $swallet = $this->plugin->Mtop->getAll();
            $c = count($swallet);
            arsort($swallet);
            $i = 1;
            $top = [];
            foreach($swallet as $name => $amount){
                if($name == $clan){
                    $top["member"] = $i;
                    break;
                }
                ++$i;
            }
            $swallet = $this->plugin->Ktop->getAll();
            $c = count($swallet);
            arsort($swallet);
            $i = 1;
            foreach($swallet as $name => $amount){
                if($name == $clan){
                    $top["kdr"] = $i;
                    break;
                }
                ++$i;
            }
            $form->setTitle($this->replace($this->plugin->getConfig()->get("title"), [
                "clan" => $clan
            ]));
            $form->setContent($this->replace($this->plugin->getConfig()->get("content"), [
                "username" => $player->getName(),
                "clan" => $clan,
                "member" => $this->plugin->data["clan"][$clan]["members"],
                "kdr" => $this->plugin->data["clan"][$clan]["kdr"],
                "date" => $this->plugin->data["clan"][$clan]["date"],
                "mtop" => $top["member"],
                "ktop" => $top["kdr"],
                "bank" => $this->plugin->data["clan"][$clan]["bank"]
            ]));
            foreach(array_keys($this->plugin->data["players"]) as $list) :
                if(isset($this->plugin->data["players"][$list]["clan"])){
                    if($this->plugin->data["players"][$list]["clan"] == $clan){
                        $target = $this->plugin->getServer()->getPlayer($this->plugin->data["players"][$list]["nametag"]);
                        if($target instanceof Player){
                            if($target->getName() == $this->plugin->data["clan"][$clan]["owner"]){
                                $form->addButton("§a".$target->getName()."\n§bRank: §dOWNER");
                            } elseif($target->hasPermission("access.clan")){
                                $form->addButton("§a".$target->getName()."\n§bRank: §dCO-OWNER");;
                            } else {
                                $form->addButton("§a".$target->getName()."\n§bRank: §dMEMBER");
                            }
                        } else {
                            $form->addButton("§c".$this->plugin->data["players"][$list]["nametag"]."\n§bSTATUS: §cOFFLINE");
                        }
                    }
                }
            endforeach;
                $form->addButton("§cEXIT");
                $form->sendToPlayer($player);
        } else {
            $form->setTitle($this->replace($this->plugin->getConfig()->get("title"), [
                "clan" => "NULL"
            ]));
            $form->setContent($this->replace($this->plugin->getConfig()->get("content"), [
                "username" => $player->getName(),
                "date" => "NULL",
                "clan" => "NULL",
                "member" => "NULL",
                "kdr" => "NULL",
                "mtop" => "NULL",
                "ktop" => "NULL",
                "bank" => "NNULL"
            ]));
            $form->addButton("§cEXIT");
            $form->sendToPlayer($player);
        }
    }

    /**
     * @param CommandSender $sender
     * @param string $label
     * @param array $args
     * 
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(self::MSG."§cPlease use this command in-game");
            return true;
        }
        if(isset($args[0])){
            switch($args[0]){
                case "stats":
                    $this->statsMe($sender);
                    return true;
                break;
                case "create":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans create (name-clan)");
                        return true;
                    }
                    $this->plugin->createClan($sender, $args[1]);
                    return true;
                break;
                case "delete":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans delete (name-clan)");
                        return true;
                    }
                    $this->plugin->deleteClan($sender, $args[1]);
                    return true;
                break;
                case "kick":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans kick (name-clan) (player-name)");
                        return true;
                    }
                    if(!isset($args[2])){
                        $sender->sendMessage(self::MSG."§b/clans kick (name-clan) (player-name)");
                        return true;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[2]);
                    if(!$target instanceof Player){
                        $sender->sendMessage(self::MSG."§cPlayer §d".$args[2]."§c not found");
                        return true;
                    }
                    $this->plugin->kickClan($sender, $args[1], $target);
                    return true;
                break;
                case "invite":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans invite (name-clan) (player-name)");
                        return true;
                    }
                    if(!isset($args[2])){
                        $sender->sendMessage(self::MSG."§b/clans invite (name-clan) (player-name)");
                        return true;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[2]);
                    if(!$target instanceof Player){
                        $sender->sendMessage(self::MSG."§cPlayer §d".$args[2]."§c not found");
                        return true;
                    }
                    $this->plugin->inviteClan($sender, $args[1], $target);
                    return true;
                break;
                case "accept":
                    if(isset($this->plugin->data["players"][$sender->getName()]["invited"])){
                        $this->plugin->acceptInvited($sender);
                        return true;
                    } elseif(isset($this->plugin->data["players"][$sender->getName()]["acceptLeaders"])){
                        $this->plugin->acceptLeaders($sender);
                        return true;
                    } elseif(isset($this->plugin->data["players"][$sender->getName()]["acceptPromote"])){
                        $this->plugin->acceptPromote($sender);
                        return true;
                    } else {
                        $sender->sendMessage(self::MSG."§cYou do not have invited!");
                        return true;
                    }
                break;
                case "quit":
                    $this->plugin->quitClan($sender);
                    return true;
                break;
                case "top":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans top (kdr/member)");
                        return true;
                    }
                    switch($args[1]){
                        case "kdr":
                            $this->Ktop($sender);
                            return true;
                        break;
                        case "member":
                            $this->Mtop($sender);
                            return true;
                        break;
                    }
                break;
                case "chat":
                    if(count($args) < 2){
                        $sender->sendMessage(self::MSG."§b/clans chat (text)");
                        return true;
                    }
                    if(!isset($this->plugin->data["players"][$sender->getName()]["clan"])){
                        $sender->sendMessage(self::MSG."§cYou do not have clans!");
                        return true;
                    }
                    if(!isset($this->plugin->data["clan"][$this->plugin->data["players"][$sender->getName()]["clan"]])){
                        $sender->sendMessage(self::MSG."§cClan Already DELETE");
                        return true;
                    }
                    foreach($this->plugin->getServer()->getOnlinePlayers() as $pl) :
                        if(isset($this->plugin->data["players"][$pl->getName()]["clan"])){
                            if($this->plugin->data["players"][$pl->getName()]["clan"] == $this->plugin->data["players"][$sender->getName()]["clan"]){
                                $pl->sendMessage("§l§e".$this->plugin->data["players"][$sender->getName()]["clan"]."§r§b ".$sender->getName()."§7 >> §a".trim(implode(" ", $args)));
                            }
                        }
                    endforeach;
                        return true;
                break;
                case "leader":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans leader (player-name)");
                        return true;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[1]);
                    if(!$target instanceof Player){
                        $sender->sendMessage(self::MSG."§cPlayer §d".$args[1]."§c not found");
                        return true;
                    }
                    $this->plugin->clanLeaders($sender, $target);
                    return true;
                break;
                case "demote":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans demote (player-name)");
                        return true;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[1]);
                    if(!$target instanceof Player){
                        $sender->sendMessage(self::MSG."§cPlayer §d".$args[1]."§c not found");
                        return true;
                    }
                    $this->plugin->clanDemote($sender, $target);
                    return true;
                break;
                case "promote":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans promote (player-name)");
                        return true;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[1]);
                    if(!$target instanceof Player){
                        $sender->sendMessage(self::MSG."§cPlayer §d".$args[1]."§c not found");
                        return true;
                    }
                    $this->plugin->clanPromote($sender, $target);
                    return true;
                break;
                case "bank":
                    if(count($args) < 3){
                        $sender->sendMessage(self::MSG."§b/clans bank (send/take) (amount)");
                        return true;
                    }
                    switch($args[1]){
                        case "send":
                            if(!is_numeric($args[2])){
                                $sender->sendMessage(self::MSG."§camount is number format");
                                return true;
                            }
                            $this->clanBank($sender, "send", $args[2]);
                            return true;
                        break;
                        case "take":
                            if(!is_numeric($args[2])){
                                $sender->sendMessage(self::MSG."§camount is number format");
                                return true;
                            }
                            $this->clanBank($sender, "take", $args[2]);
                            return true;
                        break;
                    }
                break;
                case "list":
                    if(!isset($args[1])){
                        $sender->sendMessage(self::MSG."§b/clans list (name-clan)");
                        return true;
                    }
                    $this->listClan($sender, $args[1]);
                    return true;
                break;
                case "help":
                    $sender->sendMessage("§7===== §l§eCLANS COMMAND §r§7=====\n§f- /clan help §7|| §bList a command clans\n§f- /clan stats §7|| §bCheck stats clan\n§f- /clan top (kdr/member) §7|| §bCheck top clan kdr / member\n§f- /clan create (name-clan) §7|| §bCreate a clan\n§f- /clan bank (send/take) (amount) §7|| §bBank Clans!\n§f- /clan list (name-clan) §7|| §bList a player in clan\n§f- /clan delete (name-clan) §7|| §bDelete a clan\n§f- /clan invite (name-clan) (player-name) §7|| §bInvited a player to clan\n§f- /clan accept §7|| §bAccept all type invited\n§f- /clan quit §7|| §bQuit clan\n§f- /clan kick (name-clan) (player-name) §7|| §bKick a member from clan\n§f- /clan demote (player-name) §7|| §bDemote to player from co-owner\n§f- /clan promote (player-name) §7|| §bPromote player to co-owner\n§f- /clan leader §7|| §bPromote player to owner");
                    return true;
                break;
            }
            return true;
        } else {
            $sender->sendMessage("§7===== §l§eCLANS COMMAND §r§7=====\n§f- /clan help §7|| §bList a command clans\n§f- /clan stats §7|| §bCheck stats clan\n§f- /clan top (kdr/member) §7|| §bCheck top clan kdr / member\n§f- /clan create (name-clan) §7|| §bCreate a clan\n§f- /clan bank (send/take) (amount) §7|| §bBank Clans!\n§f- /clan list (name-clan) §7|| §bList a player in clan\n§f- /clan delete (name-clan) §7|| §bDelete a clan\n§f- /clan invite (name-clan) (player-name) §7|| §bInvited a player to clan\n§f- /clan accept §7|| §bAccept all type invited\n§f- /clan quit §7|| §bQuit clan\n§f- /clan kick (name-clan) (player-name) §7|| §bKick a member from clan\n§f- /clan demote (player-name) §7|| §bDemote to player from co-owner\n§f- /clan promote (player-name) §7|| §bPromote player to co-owner\n§f- /clan leader §7|| §bPromote player to owner");
            return true;
        }
    }

    /**
     * @param Player $player
     * @param string $type
     */
    public function listClan(Player $player, string $type){
        if(!isset($this->plugin->data["clan"][$type])){
            $player->sendMessage(self::MSG."§cClan §d".$type."§c not found");
            return;
        }
        $player->sendMessage("§7===== §l§eLIST CLAN §d".$type." §r§7=====");
        foreach(array_keys($this->plugin->data["players"]) as $list) :
            if(isset($this->plugin->data["players"][$list]["clan"])){
                if($this->plugin->data["players"][$list]["clan"] == $type){
                    $target = $this->plugin->getServer()->getPlayer($this->plugin->data["players"][$list]["nametag"]);
                    if($target instanceof Player){
                        $player->sendMessage("§f- §b".$target->getName()."§d STATUS: §aONLINE");
                    } else {
                        $player->sendMessage("§f- §b".$this->plugin->data["players"][$list]["nametag"]." §dSTATUS: §cOFFLINE");
                    }
                }
            }
        endforeach;
            $player->sendMessage("§7===== §eEND LIST CLAN §7=====");
    }

    /**
     * @param int $number
     */
    public function shortNumber(int $number){
        if($number >= 0 && $number < 60){
            return $number." SECONDS";
        } elseif($number >= 60 && $number < 3600){
            $int = $number / 60;
            return round($int)." MINUTES";
        } elseif($number >= 3600 && $number < 84600){
            $int = $number / 3600;
            return round($int)." HOURS";
        } elseif($number >= 84600){
            $int = $number / 84600;
            return round($int)." DAYS";
        }
    }

    /**
     * @param Player $player
     * @param string $type
     * @param $int
     */
    public function clanBank(Player $player, string $type, $int){
        if($int > 0){
            if($type == "send"){
                if(!isset($this->plugin->data["players"][$player->getName()]["clan"])){
                    $player->sendMessage(self::MSG."§cYou do not have clans!");
                    return;
                }
                if(EconomyAPI::getInstance()->myMoney($player) < $int){
                    $player->sendMessage(self::MSG."§cYou money not enough");
                    return;
                }
                if(!isset($this->plugin->data["players"][$player->getName()]["send"])){
                    if($int >= $this->plugin->getConfig()->get("bank-per-day")){
                        $player->sendMessage(self::MSG."§cMaximum send bank per day §d".$this->plugin->getConfig()->get("bank-per-day"));
                        return;
                    }
                    $this->plugin->data["players"][$player->getName()]["timer"] = time() + $this->plugin->getConfig()->get("timer-bank") * 3600;
                    EconomyAPI::getInstance()->reduceMoney($player, $int);
                    $this->plugin->data["players"][$player->getName()]["send"] = $int;
                    $this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] += $int;
                    $player->sendMessage(self::MSG."§aSuccess send §d".$int."§a to bank clans!");
                    return;
                }
                $jumlah = $this->plugin->data["players"][$player->getName()]["send"] + $int;
                if($jumlah > $this->plugin->getConfig()->get("bank-per-day")){
                    $need = $this->plugin->getConfig()->get("bank-per-day") - $this->plugin->data["players"][$player->getName()]["send"];
                    $player->sendMessage(self::MSG."§cYou can send money to bank clans is §d".$need);
                    if(time() < $this->plugin->data["players"][$player->getName()]["timer"]){
                        $cooldown = $this->plugin->data["players"][$player->getName()]["timer"] - time();
                        $player->sendmessage(self::MSG."§cCooldown send/take money to bank clans is §d".$this->shortNumber($cooldown));
                        return;
                    } else {
                        unset($this->plugin->data["players"][$player->getName()]["timer"]);
                        if(isset($this->plugin->data["players"][$player->getName()]["take"])){
                            unset($this->plugin->data["players"][$player->getName()]["take"]);
                        }
                        if(isset($this->plugin->data["players"][$player->getName()]["send"])){
                            unset($this->plugin->data["players"][$player->getName()]["send"]);
                        }
                        $this->clanBank($player, $type, $int);
                        return;
                    }
                }
                $this->plugin->data["players"][$player->getName()]["timer"] = time() + $this->plugin->getConfig()->get("timer-bank") * 3600;
                EconomyAPI::getInstance()->reduceMoney($player, $int);
                $this->plugin->data["players"][$player->getName()]["send"] += $int;
                $this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] += $int;
                $player->sendMessage(self::MSG."§aSuccess send §d".$int."§a to bank clans!");
                return;
            } elseif($type == "take"){
                if(!isset($this->plugin->data["players"][$player->getName()]["clan"])){
                    $player->sendMessage(self::MSG."§cYou do not have clans!");
                    return;
                }
                if(EconomyAPI::getInstance()->myMoney($player) < $int){
                    $player->sendMessage(self::MSG."§cYou money not enough");
                    return;
                }
                if(!isset($this->plugin->data["players"][$player->getName()]["take"])){
                    if($int >= $this->plugin->getConfig()->get("bank-per-day")){
                        $player->sendMessage(self::MSG."§cMaximum take bank per day §d".$this->plugin->getConfig()->get("bank-per-day"));
                        return;
                    }
                    if($this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] >= $int){
                        $this->plugin->data["players"][$player->getName()]["timer"] = time() + $this->plugin->getConfig()->get("timer-bank") * 3600;
                        EconomyAPI::getInstance()->reduceMoney($player, $int);
                        $this->plugin->data["players"][$player->getName()]["take"] = $int;
                        $this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] -= $int;
                        $player->sendMessage(self::MSG."§aSuccess take §d".$int."§a to bank clans!");
                        return;
                    } else {
                        $player->sendMessage(self::MSG."§cBank clans §d".$type."§c not enough take §d".$int." §cmoney");
                        return;
                    }
                }
                $jumlah = $this->plugin->data["players"][$player->getName()]["take"] + $int;
                if($jumlah > $this->plugin->getConfig()->get("bank-per-day")){
                    $need = $this->plugin->getConfig()->get("bank-per-day") - $this->plugin->data["players"][$player->getName()]["take"];
                    $player->sendMessage(self::MSG."§cYou can take money to bank clans is §d".$need);
                    if(time() < $this->plugin->data["players"][$player->getName()]["timer"]){
                        $cooldown = $this->plugin->data["players"][$player->getName()]["timer"] - time();
                        $player->sendmessage(self::MSG."§cCooldown send/take money to bank clans is §d".$this->shortNumber($cooldown));
                        return;
                    } else {
                        unset($this->plugin->data["players"][$player->getName()]["timer"]);
                        if(isset($this->plugin->data["players"][$player->getName()]["take"])){
                            unset($this->plugin->data["players"][$player->getName()]["take"]);
                        }
                        if(isset($this->plugin->data["players"][$player->getName()]["send"])){
                            unset($this->plugin->data["players"][$player->getName()]["send"]);
                        }
                        $this->clanBank($player, $type, $int);
                        return;
                    }
                }
                if($this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] >= $int){
                    $this->plugin->data["players"][$player->getName()]["timer"] = time() + $this->plugin->getConfig()->get("timer-bank") * 3600;
                    EconomyAPI::getInstance()->addMoney($player, $int);
                    $this->plugin->data["players"][$player->getName()]["take"] += $int;
                    $this->plugin->data["clan"][$this->plugin->data["players"][$player->getName()]["clan"]]["bank"] -= $int;
                    $player->sendMessage(self::MSG."§aSuccess take §d".$int."§a to bank clans!");
                    return;
                } else {
                    $player->sendMessage(self::MSG."§cBank clans §d".$type."§c not enough take §d".$int." §cmoney");
                    return;
                }
            }
        } else {
            $player->sendMessage(self::MSG."§cMinimum amount is 1");
            return;
        }
    }

    /**
     * @param Player $player
     */
    public function Ktop(Player $player){
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            if($data === null or $data >= 0){
                $player->sendMessage(self::MSG."§aThanks for open menu!");
            }
        });
        $form->setTitle("§l§eTOP KDR");
        $swallet = $this->plugin->Ktop->getAll();
        $c = count($swallet);
        $pesan = "";
        $top = "§7       ===== §l§eTOP KDR CLAN §r§7=====";
        arsort($swallet);
        $i = 1;
        foreach($swallet as $name => $amount) :
            $pesan .= "§b".$i.", §7".$name." §d= §a".$amount."\n";
            if($i > 9){
                break;
            }
            ++$i;
        endforeach;
            $form->setContent($top."\n".$pesan."\n       §7==========================");
            $form->addButton("§cEXIT");
            $form->sendToPlayer($player);
    }

    /**
     * @param Player $player
     */
    public function Mtop(Player $player){
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            if($data === null or $data >= 0){
                $player->sendMessage(self::MSG."§aThanks for open menu!");
            }
        });
        $form->setTitle("§l§eTOP MEMBER");
        $swallet = $this->plugin->Mtop->getAll();
        $c = count($swallet);
        $pesan = "";
        $top = "     §7===== §l§eTOP MEMBER CLAN §r§7=====";
        arsort($swallet);
        $i = 1;
        foreach($swallet as $name => $amount) :
            $pesan .= "§b".$i.", §7".$name." §d= §a".$amount."\n";
            if($i > 9){
                break;
            }
            ++$i;
        endforeach;
            $form->setContent($top."\n".$pesan."\n       §7==========================");
            $form->addButton("§cEXIT");
            $form->sendToPlayer($player);
    }
}
