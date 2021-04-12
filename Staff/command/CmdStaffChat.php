<?php //By TheWillyXD4502
declare(strict_types=1);

namespace Staff\Command;

use Staff\Loader;
use pocketmine\plugin\Plugin;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdStaffChat extends Command implements PluginIdentifiableCommand {
    
    private $plugin;
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("sc", "/sc [mensaje] STAFF CHAT", "");
    $this->setPermission("cmd.staff");
	}
	
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
	
	if(!$sender instanceof Player){
	return;
	}
	if(!$this->testPermission($sender)){
	return;
	}
	if(isset($args[0])){
	    $players = $this->plugin->getServer()->getOnlinePlayers();
       foreach($players as $pl) {
           if($pl->hasPermission("Staff.chat")){
              if($pl->getName()){
                 $plx = $sender->getName();
                 $pl->sendMessage("§l§6StaffChat§r §f".$plx."§8§l » §f§o".$args[0]);
                 $volume = mt_rand();
			     $pl->getLevel()->broadcastLevelEvent($pl, LevelEventPacket::EVENT_SOUND_ORB, (int) $volume);
              }
           }
       }
	}else{
	    $sender->sendMessage("Escribe algo por favor .-.");
	    
	}
	}
    
}


