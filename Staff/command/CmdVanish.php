<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdVanish extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("vanish", "/vanish   [player] Vanish", "");
    $this->setPermission("cmd.staff");
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
	    
	
	if(!$sender instanceof Player){
         $sender->sendMessage("Run this command in-game.");
         return;
    }
    
    if(!$this->testPermission($sender)){
	return;
	}
    if(isset($args[0])){
	$player = $this->getServer()->getPlayer($args[0]);
	$name = $player->getName();
    if($player == null){
	$sender->sendMessage("§l§8[§r§c!§l§8]§r§e Ese jugador no esta conectad@!.");
	return;
	} else {
	    $this->getPlugin()->Vanish($player);
	return;
	}}
	$this->getPlugin()->Vanish($sender);
}
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
