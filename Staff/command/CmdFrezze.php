<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use Staff\Events\Events;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdFrezze extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("freeze ", "freeze [player] Frezea a un usuario", "");
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
				$player = $this->getServer()->getPlayer($args[0]);
				 $name = $player->getName();
				if($player == null){
					$sender->sendMessage("§l§8[§r§c!§l§8]§r§e Ese jugador no esta conectad@!.");
	            } else {
	                if(!$this->getPlugin()->isFreezed($player->getName())){
	                $sender->sendMessage("§l§8[§r§a!§l§8]§r ".T::RED.$player->getName()." a sido congelado!");
	                $player->sendMessage("§l§8[§r§c!§l§8]§r ".T::RED."Tu has sido congelado por: ".T::RESET.$sender->getName());
	                $this->getPlugin()->setTag($player);
					$this->getPlugin()->setFreezed($player->getName());
	                } else {
	                $sender->sendMessage("§l§8[§r§a!§l§8]§r ".T::RED.$player->getName()." a sido descongelado!");
	                $player->setImmobile(false);
	                $this->getPlugin()->unsetTag($player);
	                $player->sendMessage("§l§8[§r§!§l§8]§r ".T::YELLOW."Tu has sido descongelado por: ".T::GREEN.$sender->getName());
	                $player->addTitle("§l§7[§4!§7]"."\n"."§aDescongelado"."\n"."§l§7[§4!§7]","",1);    
	                $this->getPlugin()->unFreeze($player->getName());    
	                }
				}
	
	}
	}
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
