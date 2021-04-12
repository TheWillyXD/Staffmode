<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\libs\Country;


use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as TE;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdCountry extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("gip", "/gip [player]", "");
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
	
	if(!isset($args[0])){
		$sender->sendMessage(TE::RED."Usage: /gip [string: target]");
			return;
	}
		
    $player = $this->plugin->getServer()->getPlayer($args[0]);
	if($player != null){
			$sender->sendMessage(TE::GRAY."The players ".TE::LIGHT_PURPLE.$player->getName().TE::GRAY." is playing from the country of ".TE::LIGHT_PURPLE.Country::getCountry($player));
	}else{
	$sender->sendMessage(TE::RED."The player you are logged in is not connected!");
	}
	                
  }
				
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
