<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as TE;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdClear extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("clear", "/clear [player] Clear Inventory For STAFF", "");
    $this->setPermission("cmd.staff");
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {

	if(!$sender instanceof Player){
	return;
	}
	if(!$this->testPermission($sender)){
	return;
	}

	if(isset($args[0])){
        	$player = $this->plugin->getServer()->getPlayer($args[0]);
        	if($player != null){
        		$player->getInventory()->clearAll();
        		$player->getArmorInventory()->clearAll();
				$sender->removeAllEffects();
				$sender->sendMessage(TE::GRAY."You successfully emptied the inventory of: ".TE::AQUA.$player->getName());
        	}else{
        		$sender->sendMessage(TE::RED."The player you are looking for is not connected!");
        	}
        }else{
			$sender->getArmorInventory()->clearAll();
			$sender->getInventory()->clearAll();
			$sender->removeAllEffects();
			$sender->sendMessage(TE::GRAY."You successfully cleaned your inventory");
		}
	                
  }
				
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
