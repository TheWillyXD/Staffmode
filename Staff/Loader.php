<?php

namespace Staff;


use Staff\Events\{Events as EventsManager };
use Staff\commands\BaseCommand;
use Staff\EventListener;
use Staff\Task\ScoreTask;
use Staff\libs\ScoreAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Staff\libs\FormAPI\SimpleForm;
use Staff\libs\FormAPI\FormAPI;
use Staff\libs\FormAPI\Form;
use Staff\libs\FormAPI\ModalForm;
use Staff\libs\FormAPI\CustomForm;
use Staff\libs\API;
use Staff\command\{CmdStaffChat,CmdStaffmode, CmdStafftools,CmdFrezze, CmdVanish,CmdClear,CmdInfo,CmdPing,CmdCountry,CmdHelpStaff,CmdReport};
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\scheduler\Task;
use SQLite3;
use pocketmine\event\Listener;
use pocketmine\{Server, Player};
use pocketmine\plugin\PluginBase;

use pocketmine\nbt\tag\{CompoundTag, StringTag, IntTag, ListTag};
use pocketmine\tile\{Tile, Chest};
use pocketmine\utils\TextFormat as T;

class Loader extends PluginBase implements Listener {
	
	static $prefix = "§f[§cStaffmode§f]";
	

	
	private static $scoreboard = null;
	private static $instance = null;
	public $pk;
    public static $device = [];
    public static $main;

	public $freeze = [];
	public $vanish = [];
	public $inventory = [];
	public $staff = [];
	public $nametagg = [];
	public $nametaggg = [];
	public $playerList = [];
	private $invhandler;
	private $db;
	private $data = [];
	public $targetPlayer = [];
	
	 public function onEnable(): void {
	     $this->getScheduler()->scheduleRepeatingTask(new ScoreTask($this), 20);
		$this->invhandler = new InventoryHandler($this);
		BaseCommand::registerDefaults($this);
    $this->saveDefaultConfig();
		$this->db = new SQLite3($this->getDataFolder() . "database.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS warns(player TEXT PRIMARY KEY COLLATE NOCASE, amount INTEGER);");
		API::getLoad();
		$this->db->exec("CREATE TABLE IF NOT EXISTS mutes(player TEXT PRIMARY KEY COLLATE NOCASE, expiry INTEGER, mutedby TEXT, reason TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS bans(player TEXT PRIMARY KEY COLLATE NOCASE, expiry INTEGER, bannedby TEXT, reason TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS ipbans(ip TEXT, bannedby TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS pdata(player TEXT PRIMARY KEY COLLATE NOCASE, lastip TEXT, os TEXT, model TEXT);");
		API::getLoadTwo();
		$this->db->exec("CREATE TABLE IF NOT EXISTS offline(player TEXT PRIMARY KEY COLLATE NOCASE, message TEXT);");
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this->getInventoryHandler()), $this);
	$this->getLogger()->info(T::GREEN."Activado!");
	$this->getServer()->getCommandMap()->register("sc", new CmdStaffChat($this));
	
		$this->getServer()->getCommandMap()->register("report", new CmdReport($this));
	$this->getServer()->getCommandMap()->register("clear", new CmdClear($this));
	$this->getServer()->getCommandMap()->register("co", new CmdInfo($this));
	$this->getServer()->getCommandMap()->register("ping", new CmdPing($this));
	$this->getServer()->getCommandMap()->register("helpop", new CmdHelpStaff($this));
	$this->getServer()->getCommandMap()->register("gip", new CmdCountry($this));
	
	
	$this->getServer()->getCommandMap()->register("mod", new CmdStaffmode($this));
	$this->getServer()->getCommandMap()->register("freeze", new CmdFrezze($this));
	$this->getServer()->getCommandMap()->register("vanish", new CmdVanish($this));
	$this->getServer()->getCommandMap()->register("punish", new CmdStafftools($this));
	$this->getEvents();
	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	self::$main = $this;
	
	}
	public function getMain(): self{
        return self::$main;
    }
		public function menuForm(Player $player, string $victim) : void{
		$pdata = $this->db->query("SELECT * FROM pdata WHERE player='$victim';")->fetchArray(SQLITE3_ASSOC);
		if(!$pdata) $pdata = [];
		$warns = ($warn = $this->db->query("SELECT amount FROM warns WHERE player='$victim';")->fetchArray(SQLITE3_ASSOC)) != false ? $warn["amount"] ?? 0 : 0;
		$buttons = [
			"warn" => "Agg Advertencia",
			"unwarn" => "Reducir Advertencia",
			"tempmute" => "Temp Mute",
			"permmute" => "Perm Mute",
			"unmute" => "Remover Mute",
			"ban" => "Ban",
			"tempban" => "Temp Ban",
			"ipban" => "IP Ban",
			"unban" => "Un ban",
			10 => "Back"
		];
		$form = new SimpleForm(function(Player $player, ?int $data) use ($buttons, $victim, $warns, $pdata){
			if($data === null) return;
			switch($data){
				case 0:        //add warn
					$form = new CustomForm(function(Player $player, array $data = null ) use ($buttons, $victim, $warns, $pdata){
						if($data === null){
							return true;

						}


						$warns += 1;
						$st = $this->db->prepare("INSERT OR REPLACE INTO warns (player, amount) VALUES (:victim, :warns);");
						$st->bindParam(":victim", $victim);
						$st->bindParam(":warns", $warns);
						$st->execute();
						$player->sendMessage("§a* §r§aAdvertido con éxito a $victim!, Motivo: $data[0]*");
						$this->inform($victim, "§cTu has sido advertido por " . $player->getName() ." Motivo: $data[0]". "! Ahora tu tienes $warns advertencias!");
						Server::getInstance()->broadcastMessage(self::$prefix." §c$victim §eha sido advertido por §c".$player->getName());

						if($warns === $this->getConfig()->get("max-warns")){
							$expiry = ($this->getConfig()->get("ban-type") === "temp" ? $this->getConfig()->get("ban-length") + time() : -1);
							$readable = $this->timeToString($expiry);
							$name = $player->getName();
							$st = $this->db->prepare("INSERT OR REPLACE INTO bans (player, expiry, bannedby) VALUES (:victim, :expiry, :name);");
							$st->bindParam(":victim", $victim);
							$st->bindParam(":expiry", $expiry);
							$st->bindParam(":name", $name);
							$st->execute();
							$player->sendMessage("§l§a * 
¡Se prohibió con éxito a $victim ya que excedio el máximo de advertencias!");
							$p = $this->getServer()->getPlayer($victim);
							if($p instanceof Player){
								$p->kick("¡Fuiste expulsado automáticamente por $readable, ya que excediste la cantidad máxima de advertencias!", false);
							}
						}
					});
					$form->setTitle("§bRazon para la Advertencia");
					$form->addInput("Razon:","Ingresar razon");
					$player->sendForm($form);



				break;

				case 1:        //reduce warn
					if($warns > 0){
						$warns -= 1;
						$st = $this->db->prepare("INSERT OR REPLACE INTO warns (player, amount) VALUES (:victim, :warns);");
						$st->bindParam(":victim", $victim);
						$st->bindParam(":warns", $warns);
						$st->execute();
						$player->sendMessage("§a§l * §r§aAdvertencia removida con éxito a $victim!");
						Server::getInstance()->broadcastMessage(self::$prefix." §c".$player->getName()."§e le ha reducido una advertencia a §c$victim ");
						$this->inform($victim, "§c¡Tu advertencia fue eliminada por ". $player->getName()."! ¡Ahora tienes $warns advertencias!");
					}else{
						$player->sendMessage("§cEl jugador no tiene advertencias!");
					}
					break;

				case 2:        //temp mute


					/*$form = new CustomForm(function(Player $player, array $data = null ) use ($buttons, $victim, $warns, $pdata){
						if($data === null){
							return true;

						}

					});
					$form->setTitle("§bRazon para el silencio ");
					$form->addInput("Razon:","Ingresar razon");
					$player->sendForm($form);
					*/


					$steps = ["1m" => 60 * 1,"5m" => 60 * 5,"15m" => 60 * 15,"30m" => 60 * 30 ,"1hr" => 3600 * 1,"2hr" => 3600 * 2,"3hr" => 3600 * 3,"5hr" => 3600 * 5,"7hr" => 3600 * 7,"12hr" => 3600 * 12,"15hr" => 3600 * 15, "24hr" => 3600 * 24, "1 dia" => 3600 * 24 * 1, "3 dias" => 3600 * 24 * 3,"5 dias" => 3600 * 24 * 5,"7 dias" => 3600 * 24 * 7,"30 Dias"=> 3600 * 24 * 30];
					$tmform = new CustomForm(function(Player $player, ?array $data) use ($victim, $steps){
						if(empty($data)) return;
						$expiry = time() + $steps[$readable = array_keys($steps)[$data[0]]];
											                    	$reason = $data[1];

						$name = $player->getName();
						$st = $this->db->prepare("INSERT OR REPLACE INTO mutes (player, expiry, mutedby, reason) VALUES (:victim, :expiry, :player, :reaso);");
						$st->bindParam(":victim", $victim);
						$st->bindParam(":expiry", $expiry);
						$st->bindParam(":player", $name);
						$st->bindParam(":reaso",$reason);
						$st->execute();
					 Server::getInstance()->broadcastMessage(self::$prefix." §c$victim §eha sido muteado temporalmente de la network por §c$readable!");
						
						$this->inform($victim, "§cYou were muted by " . $player->getName() . " for $readable!. Motivo: $data[1]");
					});
					$tmform->setTitle("Silenciado temp: $victim");
					$tmform->addStepSlider("Selecciona el tiempo del silencio", array_keys($steps), 0);
					$tmform->addInput("Razon","Ingresar razon");
					$player->sendForm($tmform);
					break;

				case 3:        //perm mute
					$name = $player->getName();
					$this->db->exec("INSERT OR REPLACE INTO mutes (player, expiry, mutedby) VALUES('$victim', -1, '$name');");
					
					$player->sendMessage("§a§l * §r§aSilenciado a $victim permanentemente!");
					Server::getInstance()->broadcastMessage(self::$prefix." §c$victim §eha sido muteado permanente de la network por §c".$player->getName());
					$this->inform($victim, "§cTu fuistes silenciado permanente por " . $player->getName() . "!");
					break;

				case 4:        //unmute
					if($this->isMuted($victim) !== false){
						$this->db->exec("DELETE FROM mutes WHERE player='$victim';");
						$player->sendMessage("§a§l * §r§aRemoviste el silenciado permanente a $victim!");
						$this->inform($victim, "§cTu silenciado fue removido " . $player->getName() . "!");
					}else{
						$player->sendMessage("§cEl jugador $victim no esta silenciado!");
					}
					break;

				case 5:        //perm ban
					$name = $player->getName();
					$this->db->exec("INSERT OR REPLACE INTO bans (player, expiry, bannedby) VALUES ('$victim', -1, '$name');");
					$player->sendMessage("§a§l * §r§aBaneado con exito a $victim!");
					$p = $this->getServer()->getPlayer($victim);
					Server::getInstance()->broadcastMessage(self::$prefix." §c$victim §eha sido baneado permanente de la network por §c".$player->getName());
					if($p instanceof Player){
						$p->kick("Fuistes baneado permanente  " . $player->getName() . "!", false);
					}
					break;

				case 6:        //temp ban
					$steps = ["1m" => 60 * 1,"5m" => 60 * 5,"15m" => 60 * 15,"30m" => 60 * 30 ,"1hr" => 3600 * 1,"2hr" => 3600 * 2,"3hr" => 3600 * 3,"5hr" => 3600 * 5,"7hr" => 3600 * 7,"12hr" => 3600 * 12,"15hr" => 3600 * 15, "24hr" => 3600 * 24, "1 dia" => 3600 * 24 * 1, "3 dias" => 3600 * 24 * 3,"5 dias" => 3600 * 24 * 5,"7 dias" => 3600 * 24 * 7,"30 dias" => 3600 * 24 * 30];
					$tmform = new CustomForm(function(Player $player, ?array $data) use ($victim, $steps){
						if(empty($data)) return;
						$expiry = time() + $steps[$readable = array_keys($steps)[$data[0]]];
						$name = $player->getName();
						$reason = $data[1];
						$st = $this->db->prepare("INSERT OR REPLACE INTO bans (player, expiry, bannedby, reason) VALUES (:victim, :expiry, :name, :reaso);");
						$st->bindParam(":victim", $victim);
						$st->bindParam(":expiry", $expiry);
						$st->bindParam(":name", $name);
						$st->bindParam(":reaso",$reason);
						$st->execute();
						$player->sendMessage("§l§a * Successfully banned $victim for $readable!");
						Server::getInstance()->broadcastMessage(self::$prefix." §c$victim §eha sido baneado temporalmente de la network por §c".$player->getName());
						$p = $this->getServer()->getPlayer($victim);
						if($p instanceof Player){
							$p->kick("Fuistes baneado por" . $player->getName() . " por $readable!. Motivo: $data[1]", false);
						}
					});
					$tmform->setTitle("Ban Temporal: $victim");
					$tmform->addStepSlider("Selecciona el tiempo de ban", array_keys($steps), 0);
					$tmform->addInput("Razon","Ingresar razon");
					$player->sendForm($tmform);
					break;

				case 7:        //ip ban
					$ip = $pdata["lastip"];
					$name = $player->getName();
					$this->db->exec("INSERT OR REPLACE INTO ipbans (ip, bannedby) VALUES ('$ip', '$name');");
					$player->sendMessage("§a§l * §r§aBaneado de ip a $victim !");
					$p = $this->getServer()->getPlayer($victim);
					if($p instanceof Player){
						$p->kick("Fuistes baneado de ip por " . $player->getName() . "!", false);
					}
					break;

				case 8:
					$ip = $pdata["lastip"];
					if(!$this->isIPBanned($ip) && !$this->isBanned($victim)){
						$player->sendMessage("§cEl jugador no esta baneado!");
					}else{
						$this->db->exec("DELETE FROM bans WHERE player='$victim';");
						$this->db->exec("DELETE FROM ipbans WHERE ip='$ip';");
						$player->sendMessage("§a§l * §r§aDesbaneado con exito a $victim!");
					}
					break;
			
				case 9:
					$this->getServer()->dispatchCommand($player, "stafftools");
			}
		});
		$form->setTitle("Informacion de : §4$victim");
		$ip = $pdata["lastip"] ?? "Unknown";
		$os = $pdata["os"] ?? "Unknown";
		$model = $pdata["model"] ?? "Unknown";
		$content = "Jugador $victim's info\n\nDevice OS: $os\n\nModelo de disp: $model\n\nAdvertencias: $warns";
		$form->setContent($content);
		foreach($buttons as $perm => $text){
			if($perm === 9 || $player->hasPermission("st." . $perm)) $form->addButton($text);
		}
		$player->sendForm($form);
	}
	public function inform(string $victim, string $message) : void{
		if(($p = $this->getServer()->getPlayer($victim)) instanceof Player){
			$p->sendMessage($message);
			return;
		}else{
			$st = $this->db->prepare("INSERT INTO offline(player, message) VALUES (:player, :message);");
			$st->bindParam(":player", $victim);
			$st->bindParam(":message", $message);
			$st->execute();
			return;
		}
	}
	
	
	
	public function openTcheckUI($player){
		
		$form = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoUI($player);
		
			
		});
		$form->setTitle("Punish List");
		$form->setContent("Jugadores castigados");
		$banInfo = $this->db->query("SELECT * FROM bans;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton("$banPlayer\n§6Ban", -1, "", $banPlayer);
			$i = $i + 1;
		}
		
		$banInfo = $this->db->query("SELECT * FROM mutes;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton("$banPlayer\n§6muted", -1, "", $banPlayer);
			$i = $i + 1;
		}
		
		
		
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function openTcheckUI2($player){
		
		$form = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoUI2($player);
		
			
		});
		$form->setTitle("Punish List");
		$form->setContent("Jugadores muteados ");
		$banInfo = $this->db->query("SELECT * FROM mutes;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton("$banPlayer\n§6muted", -1, "", $banPlayer);
			$i = $i + 1;
		}
		
		
		
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function openTcheckUI3($player){
		
		$form = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoUI3($player);
		
			
		});
		$form->setTitle("Punish List");
		$form->setContent("Jugadores Baneado de Ip ");
		$banInfo = $this->db->query("SELECT * FROM Ipbans;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['ip'];
			$form->addButton("$banPlayer\n§6IpBan", -1, "", $banPlayer);
			$i = $i + 1;
		}
	
		
		
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function openInfoUI($player){
		
		$form = new SimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
					$banplayer = $this->targetPlayer[$player->getName()];
					$banInfo = $this->db->query("SELECT * FROM bans WHERE player = '$banplayer';");
					$array = $banInfo->fetchArray(SQLITE3_ASSOC);
					if (!empty($array)) {
						$this->db->query("DELETE FROM bans WHERE player = '$banplayer';");
						
					}
					unset($this->targetPlayer[$player->getName()]);
				break;
			}
		});
		$banPlayer = $this->targetPlayer[$player->getName()];
		$warns = ($warn = $this->db->query("SELECT amount FROM warns WHERE player='$banPlayer';")->fetchArray(SQLITE3_ASSOC)) != false ? $warn["amount"] ?? 0 : 0;
		$banInfo = $this->db->query("SELECT * FROM bans WHERE player = '$banPlayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['expiry'];
			$staff = $array['bannedby'];
			$reason = $array['reason'];
			$now = time();
			if($banTime < $now){
				$banplayer = $this->targetPlayer[$player->getName()];
				$banInfo = $this->db->query("SELECT * FROM bans WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				if (!empty($array)) {
				    $tform = new SimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
		}
		switch($data){
		    case 0:
		$banplayer = $this->targetPlayer[$player->getName()];
		$this->db->query("DELETE FROM bans WHERE player = '$banplayer';");
											$player->sendMessage("§a * §r§aDesbaneado con exito!-");								               break;
		}
		});
		
		$tform->setTitle($banPlayer);
		$tform->setContent("Information:\nTipe:<ban/warns($warns)>\nTime: Perm \nReason: $reason \nModerator: $staff\n\n\n");
		
		$tform->addButton("Quitar Ban");
		$tform->sendToPlayer($player);
		return $tform;
				}
				unset($this->targetPlayer[$player->getName()]);
				return true;
			}
			$remainingTime = $banTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		}
		
		
		$text = str_replace(["{day}", "{hour}", "{minute}", "{second}", "{staff}"], [$day, $hour, $minute, $second, $staff],"Information:\nTipe:<ban/warns($warns)>\nTime: {day}d/{hour}h/{minute}m/{second}s\nReason: $reason \nModerator: {staff}\n\n\n");
		$form->setTitle($banPlayer);
		
		   
		$form->setContent($text);
		
		$form->addButton("Quitar Ban");
		$form->sendToPlayer($player);
		return $form;
	}
	
	
	
	public function openInfoUI2($player){
		
		$form = new SimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
					$banplayer = $this->targetPlayer[$player->getName()];
					$banInfo = $this->db->query("SELECT * FROM mutes WHERE player = '$banplayer';");
					$array = $banInfo->fetchArray(SQLITE3_ASSOC);
					if (!empty($array)) {
						$this->db->query("DELETE FROM mutes WHERE player = '$banplayer';");
						
					}
					unset($this->targetPlayer[$player->getName()]);
				break;
			}
		});
		$banPlayer = $this->targetPlayer[$player->getName()];
		$banInfo = $this->db->query("SELECT * FROM mutes WHERE player = '$banPlayer';");
		$warns = ($warn = $this->db->query("SELECT amount FROM warns WHERE player='$banPlayer';")->fetchArray(SQLITE3_ASSOC)) != false ? $warn["amount"] ?? 0 : 0;
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['expiry'];
			$staff = $array['mutedby'];
			$reason = $array['reason'] ?? "Null";
			$now = time();
			if($banTime < $now){
				$banplayer = $this->targetPlayer[$player->getName()];
				$banInfo = $this->db->query("SELECT * FROM mutes WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				if (!empty($array)) {
				    $tform = new SimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
		}
		switch($data){
		    case 0:
		$banplayer = $this->targetPlayer[$player->getName()];
		$this->db->query("DELETE FROM mutes WHERE player = '$banplayer';");
											$player->sendMessage("§a * §r§aDesbaneado con exito!-");								               break;
		}
		});
		
		$tform->setTitle($banPlayer);
		$tform->setContent("Information:\nTipe:<mute/warns($warns)>\nTime: Perm \nReason: $reason \nModerator: $staff\n\n\n");
		
		$tform->addButton("Quitar mute");
		$tform->sendToPlayer($player);
		return $tform;
				}
				unset($this->targetPlayer[$player->getName()]);
				return true;
			}
			$remainingTime = $banTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		}
		
		
		$text = str_replace(["{day}", "{hour}", "{minute}", "{second}", "{staff}"], [$day, $hour, $minute, $second, $staff],"Information:\nTipe:<mute/warns($warns)>\nTime: {day}d/{hour}h/{minute}m/{second}s\nReason: $reason \nModerator: {staff}\n\n\n");
		$form->setTitle($banPlayer);
		
		    $form->setContent($text);
		
		$form->addButton("Quitar mute");
		$form->sendToPlayer($player);
		return $form;
	}

	
	
	
	
	public function getOfflinePunishments(string $victim) : array{
		$res = $this->db->query("SELECT message FROM offline WHERE player='$victim';");
		$m = [];
		while($r = $res->fetchArray(SQLITE3_ASSOC)){
			$m[] = $r["message"];
		}
		$this->db->exec("DELETE FROM offline WHERE player='$victim';");
		return $m;
	}

	/**
	 * @param string $player
	 *
	 * @return false|string
	 */
	public function isBanned(string $player){
		$res = $this->db->query("SELECT * FROM bans WHERE player='$player';");
		while($r = $res->fetchArray(SQLITE3_ASSOC)){
			if($r["expiry"] > time() || $r["expiry"] == -1){
				return $r;
			}else{
				$this->db->exec("DELETE FROM bans WHERE player='$player';");
				return false;
			}
		}
		return false;
	}

	/**
	 * @param string $ip
	 *
	 * @return false|array
	 */
	public function isIPBanned(string $ip){
		$result = $this->db->query("SELECT * FROM ipbans WHERE ip='$ip';");
		while($r = $result->fetchArray(SQLITE3_ASSOC)){
			return $r;
		}
		return false;
	}

	/**
	 * @param string $player
	 *
	 * @return false|array
	 */
	public function isMuted(string $player){
		$res = $this->db->query("SELECT * FROM mutes WHERE player='$player';");
		while($r = $res->fetchArray(SQLITE3_ASSOC)){
			if($r["expiry"] > time() || $r["expiry"] == -1){
				return $r;
			}else{
				$this->db->exec("DELETE FROM mutes WHERE player='$player';");
				return false;
			}
		}
		return false;
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $e){
		$packet = $e->getPacket();
		if($packet instanceof LoginPacket){
			$this->data[$packet->username] = ["os" => $packet->clientData["DeviceOS"], "model" => $packet->clientData["DeviceModel"]];
		}
	}

	public function onJoin(PlayerJoinEvent $e){
		$p = $e->getPlayer();
		if(!empty($m = $this->getOfflinePunishments($p->getName()))){
			$this->getScheduler()->scheduleDelayedTask(new class($p, $m, $this) extends Task{
				private $main;

				public function __construct(Player $p, array $m, Loader $main){
					$this->p = $p;
					$this->m = "§c§l Fuiste castigado mientras estabas fuera: \n  -" . implode("\n  -", $m);
					$this->main = $main;
				}

				public function onRun(int $currentTick){
					if($this->p->isOnline()){
						$this->p->sendMessage($this->m);
						$this->main->informed($this->p);
					}
				}
			}, 30);
		}

		$st = $this->db->prepare("INSERT OR REPLACE INTO pdata(player, lastip, os, model) VALUES(:player, :lastip, :os, :model);");
		$st->bindValue(":player", $p->getName());
		$st->bindValue(":lastip", $p->getAddress());
		$st->bindValue(":os", $this->osToString($this->data[$p->getName()]["os"]));
		$st->bindValue(":model", $this->data[$p->getName()]["model"]);
		$st->execute();
	}

	public function informed(Player $p) : void{
		if(isset($this->data[$p->getName()])) unset($this->data[$p->getName()]);
	}

     public static function getInstance() {
         return self::$instance;
    } 
	public function onLogin(PlayerPreLoginEvent $e){
		if(($r = $this->isBanned($e->getPlayer()->getName())) != false){
			$e->getPlayer()->kick("§r------------------------------------------§r\n§cTu estas baneado en  este server!\n§eBaneado por: §f" . $r["bannedby"] . "\n§eExpira: §f" . $this->timeToString($r["expiry"])."\n§r------------------------------------------§r", false);
		}elseif(($r = $this->isIPBanned($e->getPlayer()->getAddress())) != false){
			$e->getPlayer()->kick("§r------------------------------------------§r\n§cTu estas baneado de ip en este server!\n§eBaneado por: §f" . $r["bannedby"]."\n§r------------------------------------------§r" );
		}
	}

	public function onChat(PlayerChatEvent $e){
		if($e->getMessage()[0] == "/") return;
		if(($r = $this->isMuted($e->getPlayer()->getName())) !== false){
			$e->setCancelled();
			$e->getPlayer()->sendMessage("§cTu estas silenciado! (Por: {$r["mutedby"]}, Expira: {$this->timeToString($r["expiry"])})");
		}
	}

	public function osToString(int $os){
		return [
				1 => "Android",
				2 => "iOS",
				3 => "macOS",
				4 => "FireOS",
				5 => "GearVR",
				6 => "HoloLens",
				7 => "Windows 10",
				8 => "Windows32",
				9 => "Dedicated",
				10 => "TvOS",
				11 => "PS4",
				12 => "NX"
			][$os] ?? "Unknown";
	}

	public function timeToString(int $unixstamp) : string{
		if($unixstamp == -1) return "Never";
		return gmdate("F j, Y, g:i a", $unixstamp);
	}
	public function getInventoryHandler(): InventoryHandler {
		return $this->invhandler;
	}
	/*public static function getScoreboard() : ScoreAPI {
		return Loader::$scoreboard;
	}*/
	public function onLoad(){
	//Loader::$scoreboard = new ScoreAPI($this);
	$this->getLogger()->info(T::YELLOW."Cargando Staffmode");
	}
	public function onDisable(){
	$this->db->close();    
	$this->getLogger()->info(T::RED."Desactivado!");
	}
	
	public function isStaff($name){
	return in_array($name, $this->staff);
	}
	public function listStaff() : string {
	    $px = count($this->staff);
	return intval($px);
	}
	public function listFreeze() : string {
	    $pf = count($this->freeze);
	return intval($pf);
	}
	public function getEvents(){
	return new EventsManager($this);
	}
	
	public function setStaff($name){
	$this->staff[$name] = $name;
	}
	
	public function quitStaff($name){
	if(!$this->isStaff($name)){
	return;
	}
	unset($this->staff[$name]);
	}
	
	public function isFreezed($name){
	return in_array($name, $this->freeze);
	}
	
	public function setFreezed($name){
	$this->freeze[$name] = $name;
	}
	public function setTag(Player $player){
	    $name = $player->getName();
	    $nameTag = $player->getNameTag();
	    $this->nametagg[$name] = $nameTag;
        $player->setNameTag("§8[§bFreeze§8]§r $nameTag");
	}
	public function unsetTag(Player $player){
	    $name = $player->getName();
	    if(array_search($name, $this->nametagg, true)){
	    $nameTag = $this->nametagg[$name];
        $player->setNameTag("$nameTag"); 
	}}
	
	public function setTagg(Player $player){
	    $name = $player->getName();
	    $nameTag = $player->getNameTag();
	    $this->nametaggg[$name] = $nameTag;
        $player->setNameTag("§8[§6V§8]§r $nameTag");
	}
	public function unsetTagg(Player $player){
	    $name = $player->getName();
	    if(array_search($name, $this->nametaggg, true)){
	    $nameTag = $this->nametaggg[$name];
        $player->setNameTag("$nameTag"); 
	}}
	
	public function unFreeze($name){
	if(!$this->isFreezed($name)){
	return;
	}
	unset($this->freeze[$name]);
	}
	
	        /**
     * @param Player $sender
     */
     
     public function playerUI(Player $player){
       $subform = new SimpleForm(function(Player $player, ?string $data){
					if($data == null) return;
					$target = Server::getInstance()->getPlayerExact($data);
					$player->teleport($target);
				});
				$subform->setTitle("§eLista de jugadores");
				foreach($this->getServer()->getOnlinePlayers() as $p){
					$subform->addButton($p->getName(), -1, "", $p->getName());
				}
				$player->sendForm($subform);
				return $subform;
     }
 
	public function playerInfo(Player $target){
	$ip = $target->getAddress();
	$name = $target->getName();
	$ping = $target->getPing();
	$vida = T::RED.str_repeat("|", $target->getHealth()).T::GRAY.str_repeat("|", 20-$target->getHealth());
	return "§l§8[§r§a!§l§8]§r§b ====================== §l§8[§r§a!§l§8]§r§e"."\n".
	"§l§c»§r".T::GOLD."Nick: ".T::GREEN.$name."\n".
	//"§l§c»§r".T::GOLD."Direccion: ".T::GREEN.$ip."\n".
	"§l§c»§r".T::GOLD."Health: ".$vida.T::GREEN." {$target->getHealth()}%\n".
	"§l§c»§r".T::GOLD."Ping: ".T::GREEN.$ping."\n".
	"§l§c»§r".T::GOLD."Mode: ".T::GREEN.$this->getGamemodee($target)."\n".
	"§l§8[§r§a!§l§8]§r§e §b====================== §r§l§8[§r§a!§l§8]§r";
	}
	public function getGamemodee(Player $target) : string {
	    $gm= $target->getGamemode();
	    if ($gm == 0) return "Survival";
	    if ($gm == 1) return "Creative";
	    if ($gm == 2) return "Aventure";
	    if ($gm == 3) return "Spectator";
	}
	public function Backup(Player $player){
	    $contents = $player->getInventory()->getContents();
		$armorInventory = $player->getArmorInventory();

		for($slot = 100; $slot < 104; ++$slot) {
			$item = $armorInventory->getItem($slot - 100);
			if(!$item->isNull()) {
				$contents[$slot] = $item;
			}
		}
	/*$contents = $player->getInventory()->getContents();
	$items = [];
	foreach($contents as $slot => $item){
	$items[$slot] = [$item->getId(), $item->getDamage(), $item->getCount(), $item->getName()];
	}
	*/
	$this->inventory[$player->getName()] = $contents;
	}
	
	public function Restore(Player $player){
	if(!$this->isStaff($player->getName())){
	return;
	}
	/*$api = $this->getScoreboard();
   	$api->remove($player);*/
	$player->setFlying(false);
	$player->removeAllEffects();     
	$player->setMaxHealth(20);      
	$player->setHealth(20);
	$player->setFood(20);
	$contents = $this->inventory[$player->getName()];
	$player->getInventory()->clearAll();
	
	$inventory = $player->getInventory();
		

	$armorInventory = $player->getArmorInventory();
		
		foreach($contents as $slot => $item) {
			if($slot >= 100 && $slot < 104) {
				$armorInventory->setItem($slot - 100, $item, false);
			} else {
				$inventory->setItem($slot, $item, false);
			}
		}

		$inventory->sendContents($player);
		$armorInventory->sendContents($player);
	
	/*
	foreach($cloud as $slot => $item){
	$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2], $item[3]));
	}*/
	unset($this->inventory[$player->getName()]);
	return true;
	}
	public function isVanish($name){
	return in_array($name, $this->vanish);
	}
	
	public function quitVanish($name){
	if(!$this->isVanish($name)){
	$player = $this->getServer()->getPlayer($name);
	$player->setAllowFlight(false);
	return;
	}
	unset($this->vanish[$name]);
	}
	
	public function setVanish($name){
	if($this->isVanish($name)){
	$player = $this->getServer()->getPlayer($name);
	$player->setAllowFlight(true);
	
	return;
	}
	$this->vanish[$name] = $name;
	}
	public function Vanish(Player $player){
	$name = $player->getName();
	if($this->isVanish($name)){
	$online = $this->getServer()->getOnlinePlayers();
	$this->quitVanish($name);
	$this->unsetTagg($player);
	$player->sendMessage("§l§8[§r§c!§l§8]§r§e Vanish: §4OFF");
	foreach($online as $players){
	$players->showPlayer($player);
	}
	return true;
	}else
	{
	$online = $this->getServer()->getOnlinePlayers();
	foreach($online as $players){
	$players->hidePlayer($player);
	}
	$this->setTagg($player);
	$player->sendMessage("§l§8[§r§a!§l§8]§r§e Vanish:§a ON");
	$this->setVanish($name);
	return true;
	}
	
	}
	
	public function setKit(Player $player){
	$player->setFlying(true); 
	
	//Reloj
	$compass = Item::get(347);
	$compass->setDamage(100);
	$com = "§l§k§fii§r§l§1Jugador Aleatorio§k§fii";
	$compass->setCustomName($com);
	
	
	//Azucar 399
	$list = Item::get(353);
	$list->setDamage(100);
	$lis = "§l§k§fii§r§l§eTeletransporte§k§fii";
	$list->setCustomName($lis);
	
	//Brujula   
	$tele = Item::get(345);
	$tele->setDamage(100);
	$telen = "§l§k§fii§r§l§bJugadores§k§fii";
	$tele->setCustomName($telen);
	
	//Nada
	$nada = Item::get(0);

	
	//hielo 352
	$freeze = Item::get(79);
//	$freeze->setDamage(100);
	$fre = "§l§k§fii§r§l§bCongelar§k§fii";
	$freeze->setCustomName($fre);
	
	//Hueso
    $st = Item::get(352);
	$st->setDamage(100);
	$sy = "§l§k§fii§r§l§cReportes§k§fii";
	$st->setCustomName($sy);
	
	
	//libro
	$chest = Item::get(340);
	$chest->setDamage(100);
	$ches = "§l§k§fii§r§l§bInventario§k§fii";
	$chest->setCustomName($ches);
	
	//tinte 347
    $vanish = Item::get(351);
	$vanish->setDamage(8);
	$vanish->setCustomName("§l§k§fii§r§l§aVanish§k§fii");
	
	//Palo
    $info = Item::get(280);
	$info->setDamage(100);
	$inf = "§l§k§fii§r§l§6Informacion§k§fii";
	$info->setCustomName($inf);
	
	
// 	reloj,azucar,Brujula,Libre,hielo,hueso,Libro,tinte,palo
	$player->getInventory()->setContents([$compass,$list,$tele,$nada, $freeze,$st,$chest, $vanish, $info]);
	}
	
	
}