<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as TE;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdPing extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("ping", "/ping  [player]", "");
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {

	if(!$sender instanceof Player){
	return;
	}
	if(isset($args[0])){
			$jug = $sender->getServer()->getPlayer($args[0]);
			if($jug != null){
				unset($args[0]);
				$sender->sendMessage(TE::GRAY."Ping of the player ".TE::AQUA.$jug->getName().TE::GRAY." is of ".TE::AQUA.$jug->getPing().TE::GRAY." ms..!");
			}else{
				$sender->sendMessage(TE::RED."The player you are entering is not connected!");
			}
		}else{
			$sender->sendMessage(TE::GRAY."You ping player ".TE::AQUA.$sender->getName().TE::GRAY." is of ".TE::AQUA.$sender->getPing().TE::GRAY." ms..!");
		}

	                
  }
				
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}