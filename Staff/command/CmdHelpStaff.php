<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as TE;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdHelpStaff extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("helpop", "/helpop [what you need]", "");
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {

	if(!$sender instanceof Player){
	return;
	}
    if(!isset($args[0])){
            $sender->sendMessage(TE::RED."Usage: /request <what you need> or /helpop <what you need>");
            return;
        }
        $reason = implode(" ", $args);
        $sender->sendMessage(TE::GREEN."Request help correctly, wait for the staffs!");
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			if($player->hasPermission("cmd.staff")){
                $player->sendMessage(TE::BOLD.TE::DARK_PURPLE.$sender->getName().TE::RESET.TE::AQUA." is requesting help for the reason: ".TE::LIGHT_PURPLE.$reason);
            }
        }
	
	                
  }
				
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}