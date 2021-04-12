<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

class CmdStaffmode extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("mod", "STAFF MODE", "");
    $this->setPermission("cmd.staff");
	}
	
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
	
	
	if(!$sender instanceof Player){
	return;
	}
	if(!$this->testPermission($sender)){
	return;
	}
	
	$name = $sender->getName();
	
	if(!$this->getPlugin()->isStaff($name)){
	$sender->sendMessage("§l§8[§r§a!§l§8]§r§e "."StaffMode: §aON");
//	$sender->sendMessage("§bStaffModes: ".$this->getPlugin()->listStaff());
	
	$sender->setAllowFlight(true);
	$sender->setFlying(true);
	$sender->removeAllEffects();     
	$sender->setMaxHealth(20);      
	$sender->setHealth(20);
	$sender->setFood(20);
	$this->getPlugin()->Backup($sender);
	$sender->getInventory()->clearAll();
	$sender->getArmorInventory()->clearAll();
	$this->getPlugin()->setKit($sender);
	$this->getPlugin()->setStaff($name);
	$instance = new EffectInstance(Effect::getEffectByName("HASTE"),9999,3,false);
    $sender->addEffect($instance);
    $instanc = new EffectInstance(Effect::getEffectByName("NIGHT_VISION"),9999,3,false);
    $sender->addEffect($instanc);
	}else{
	    $this->getPlugin()->unsetTagg($sender);
	$sender->sendMessage("§l§8[§r§c!§l§8]§r§e "."StaffMode: §4OFF");
	$this->getPlugin()->quitVanish($sender->getName());
	
	$sender->removeAllEffects();     
	$this->getPlugin()->Restore($sender);
	$this->getPlugin()->quitStaff($name);
	}
	return;
	
	}
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
