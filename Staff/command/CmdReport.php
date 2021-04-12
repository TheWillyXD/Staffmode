<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\libs\Discord;
use Staff\libs\Country;



use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as TE;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};

class CmdReport extends Command implements PluginIdentifiableCommand{
	
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("report", "/report [player] [reason] Report user", "");
    
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	/**
	 * @param CommandSender $sender
	 * @param String $cmd
	 * @param Array $args
	 * @return bool|mixed
	 */
	public function execute(CommandSender $sender, String $cmd, Array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(TE::RED."Use this command in the game!");
			return;
		}
		if(!isset($args[0])||!isset($args[1])){
			$sender->sendMessage(TE::RED."Usage: /report [string: target] [string: reason]");
			return;
		}
		$player = $this->plugin->getServer()->getPlayer($args[0]);
		$date = date("d/m/Y H:i:s");
		unset($args[0]);
		$reason = implode(" ", $args);
		$text = str_replace(["@everyone", "@here"], ["eveyone","here"],"$reason");
		
		
				
		if($player != null){
			$this->sendReport($player, $sender, $text);
			Discord::sendToDiscord("https://discordapp.com/api/webhooks/800421559600414740/DeEgx3nWIkAj2juG3ruJI2Yio8rqm9s35hJkowve5QzmvJhKmFEHayEHnZX8yd8xF8JF", "Report System SoupNetwork",
			"=========================="."\n".
			"Accused: ".$player->getName()."\n".
			"Accuser: ".$sender->getName()."\n".
			"Connection: ".$player->getPing()."\n".
			"Reason: ".$text."\n".
			"Date: "."$date"."\n".
			"=========================="."\n\n");
		}else{
			$sender->sendMessage(TE::RED."The player you are looking for is not connected!");
		}
		return true;
	}
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
    
    
	/**
	 * @param Player $player
	 * @param Player $sender
	 * @param String $args
	 */
	
	private function sendReport($player, $sender, $args){
		$sender->sendMessage(TE::GRAY."You reported to the player ".TE::DARK_RED.$player->getName());
		foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
			if($pl->hasPermission("report.command.use")){
				$pl->sendMessage(
				TE::GRAY."=========================="."\n".
				TE::YELLOW."Accused: ".TE::WHITE.$player->getName()."\n".
				TE::YELLOW."Accuser: ".TE::WHITE.$sender->getName()."\n".
				TE::YELLOW."Reason: ".TE::WHITE.$args."\n".
				TE::YELLOW."Connection: ".TE::WHITE.$player->getPing()."\n".
				TE::YELLOW."World: ".TE::WHITE.$player->getLevel()->getFolderName()."\n".
				TE::GRAY."=========================="
				);
			}
		}
	}
}
