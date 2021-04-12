<?php

namespace Staff\Events;

use Staff\Loader;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\{Server, Player};
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as T;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\tile\Chest;
use pocketmine\event\player\{PlayerMoveEvent, PlayerInteractEvent, PlayerItemHeldEvent, PlayerDropItemEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent};
use pocketmine\event\inventory\{InventoryCloseEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};

class Events implements Listener{
	
	private $plugin;
	
	
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
	$this->getServer()->getPluginManager()->registerEvents($this, $this->getPlugin());
	}
	
	public function getPlugin(){
	return $this->plugin;
	}
	
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	public function onDeath(PlayerDeathEvent $event){
	$player = $event->getPlayer();
	if($this->getPlugin()->isStaff($player->getName())){
	$event->setDrops([]);
	}
	}
	
	public function onRespawn(PlayerRespawnEvent $event){
	$player = $event->getPlayer();
	if($this->getPlugin()->isStaff($player->getName()) and $player->spawned){
	$this->getPlugin()->setKit($player);
	}
	}
	    /**
     * @param PlayerExhaustEvent $event
     */
    public function onHunger(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        if($player instanceof Player){
            if($this->getPlugin()->isStaff($player->getName())){
                $event->setCancelled(true);
            }
            if($this->getPlugin()->isFreezed($player->getName())){
                
                $event->setCancelled(true);
            }    
        }
    }
    
    public function onBreak(BlockBreakEvent $ev){
		$player = $ev->getPlayer();
		if($this->getPlugin()->isFreezed($player->getName())){
			
				$ev->setCancelled();
			
		}
	}
	
    public function onPlace(BlockPlaceEvent $ev){
		$player = $ev->getPlayer();
		if($this->getPlugin()->isFreezed($player->getName())){
				$ev->setCancelled();
		}
		
	     
	}
	public function onPlacee(BlockPlaceEvent $ev){
	    $player = $ev->getPlayer();
	  if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
		$item = $player->getInventory()->getItemInHand();
	if($item->getId() == 79){
	    $ev->setCancelled(true);
	    }
		
	}
    /**
     * @param EntityDamageEvent $event
     */
    public function oonDamage(EntityDamageEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if($this->getPlugin()->isStaff($player->getName())){
               $event->setCancelled(true);
            }
            if($this->getPlugin()->isFreezed($player->getName())){
                $event->setCancelled(true);
            }    
        }
    }
	public function onQuit(PlayerQuitEvent $event){
	$player = $event->getPlayer();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$this->getPlugin()->Restore($player);	$this->getPlugin()->unsetTagg($player);
	$this->getPlugin()->quitVanish($player->getName());
	$this->getPlugin()->quitStaff($player->getName());
	return true;
	}
	
	public function dropItem(PlayerDropItemEvent $event){
	    $player = $event->getPlayer();
	    
	    /*if($event->isCancelled()){
	Nnnreturn;
	}*/
	
	$item = $event->getItem();
	if($item->getDamage() == 100){
	$event->setCancelled(true);
	}

    //if($player instanceof Player){
    if($this->getPlugin()->isFreezed($player->getName())){
                $event->setCancelled(true);
        }  
    if($this->getPlugin()->isStaff($player->getName())){
         if($item->getId() == 79){
                $event->setCancelled(true);
         }
         if($item->getId() == 351 and $item->getDamage() == 10 and $item->getCustomName("§l§k§fii§r§l§aVanish§k§fii")){
             $event->setCancelled(true);
             
         }
         if($item->getId() == 351 and $item->getDamage() == 8 and $item->getCustomName("§l§k§fii§r§l§aVanish§k§fii")){
             $event->setCancelled(true);
             
         }
	return;
	}
    //}
	}
	
	public function onMove(PlayerMoveEvent $event){
	$player = $event->getPlayer();
	if($this->getPlugin()->isFreezed($player->getName())){
	    $player->setImmobile(true);
	    $event->isCancelled();
	    $player->addTitle("§l§7[§4!§7]"."\n"."§cCongelado"."\n"."§l§7[§4!§7]");
	}
	}
	
	public function onHeld(PlayerItemHeldEvent $event){
	$player = $event->getPlayer();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$item = $player->getInventory()->getItemInHand();
	if($item->getId() == 351 and $item->getDamage() == 10){
	$vanish = "";
	if($this->getPlugin()->isVanish($player->getName())){
	$vanish = "§l§8[§r§a!§l§8]§r ".T::GREEN."Estas en modo vanish!"." §l§8[§r§a!§l§8]§r";
	}
    if($item->getId() == 351 and $item->getDamage() == 8){
        if($this->getPlugin()->isVanish($player->getName())){
	$vanish = "§l§8[§r§c!§l§8]§r ".T::RED."No estas en modo vanish!"." §l§8[§r§c!§l§8]§r";
        }
	}
	$player->sendTip($vanish);
	}
	}
	
	public function onBlockBreak(BlockBreakEvent $event) : void {
        $direction = $event->getPlayer()->getDirectionVector()->multiply(4);
        if(!$this->getPlugin()->isStaff($event->getPlayer()->getName())){
	return;
	}
    if($event->getPlayer()->getInventory()->getItemInHand()->getId() == 345 and $event->getPlayer()->getInventory()->getItemInHand()->getDamage() == 100){
            $event->getPlayer()->teleport(Position::fromObject($event->getPlayer()->add($direction->getX(), $direction->getY(), $direction->getZ()), $event->getPlayer()->getLevel()));
            $event->setCancelled(true);
        }
    }
    
    
    
	public function onInteract(PlayerInteractEvent $event){
	if($event->isCancelled()){
	return;
	}
	$player = $event->getPlayer();
	$block = $event->getBlock();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$item = $player->getInventory()->getItemInHand();
	if($item->getId() == 351 and $item->getDamage() == 8){	
	    $graydye = Item::get(ItemIds::DYE, 10, 1)->setCustomName("§l§k§fii§r§l§aVanish§k§fii");
        $player->getInventory()->setItem(7, $graydye);
	    $this->getPlugin()->Vanish($player);
	    
	}
	if($item->getId() == 351 and $item->getDamage() == 10){
	    $dye = Item::get(ItemIds::DYE, 8, 1)->setCustomName("§l§k§fii§r§l§aVanish§k§fii");
        $player->getInventory()->setItem(7, $dye);
	    $this->getPlugin()->Vanish($player);
	    
	}
	if($item->getDamage() == 100){
	switch($item->getId()){	
	case 353:
    if ($player->getDirection() == 2) {
                    $player->setMotion($player->getMotion()->add(-7, 0));
                }
                if ($player->getDirection() == 0) {
                    $player->setMotion($player->getMotion()->add(7, 0));
                }
                if ($player->getDirection() == 1) {
                    $player->setMotion($player->getMotion()->add(0, 0, 7));
                }
                if ($player->getDirection() == 3) {
                    $player->setMotion($player->getMotion()->add(0, 0, -7));
                }
	return true;
	break;
	case 345:
	  $this->plugin->playerUI($player);
	  return true;
	  break;
	case 347:
	$this->playerTeleport($player);
	return true;
	break;
	}
	}
	}
	
	public function setSpeed(Player $player, int $x, int $y, int $z){
	$player->teleport(new Vector3($x, $y+1, $z));
	//$player->setMotion(new Vector3($x, $y, $z));
	}
	
	public function playerTeleport(Player $player){
	$test = array_rand($this->getServer()->getOnlinePlayers());
                     $playerTotp = $this->getServer()->getOnlinePlayers()[$test];
                            if($playerTotp !== $player){
                                $player->teleport($playerTotp);
                                $player->sendMessage( '§l§8[§r§a!§l§8]§r§e Teletransportar a:§f ' . $playerTotp->getName());
                        }
	}
	public function onAttack(EntityDamageByEntityEvent $event) : void {
		$damager = $event->getDamager();
		$entity = $event->getEntity();

		if($damager instanceof Player) {
			if($this->getPlugin()->isFreezed($damager->getName())) {
					$event->setCancelled(true);
			}
		}
	}
	
	public function onDamage(EntityDamageEvent $event){
	if($event instanceof EntityDamageByEntityEvent){
	$target = $event->getEntity();
	if(!$target instanceof Player){
	return;
	}
	$player = $event->getDamager();
	if(!$player instanceof Player){
	return;
	}
		
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	
	$hand = $player->getInventory()->getItemInHand();
	$event->setCancelled(true);
	
	switch($hand->getId()){
	case 79:
	if($this->getPlugin()->isFreezed($target->getName())){
	$this->getPlugin()->unFreeze($target->getName());
	$target->setImmobile(false);
	$player->sendMessage("§l§8[§r§a!§l§8]§r "."§c{$target->getName()} a sido descongelado!");
	$this->getPlugin()->unsetTag($target);
	$target->sendMessage("§l§8[§r§a!§l§8]§r ".T::YELLOW."Tu has sido descongelado por: ".T::GREEN.$player->getName());
	$target->addTitle("§l§7[§4!§7]"."\n"."§aDescongelado"."\n"."§l§7[§4!§7]");
	return true;
	}else{
	if(!$this->getPlugin()->isFreezed($target->getName())){
	$this->getPlugin()->setFreezed($target->getName());
	$player->sendMessage("§l§8[§r§c!§l§8]§r ".T::GREEN."{$target->getName()} a sido congelado!");
	$this->getPlugin()->setTag($target);
	$target->sendMessage("§l§8[§r§c!§l§8]§r ".T::GOLD."Tu has sido congelado por: ".T::GREEN.$player->getName());
	return true;
	} 
	}
	break;

	}
	
	
	if($hand->getDamage() == 100){
	
	switch($hand->getId()){
		case 280:
	$msg = $this->getPlugin()->playerInfo($target);
	$player->sendMessage($msg);
	return true;
	break;
	case 352:
	if($target instanceof Player){
	$target1 = $target->getName();    
    $this->plugin->menuForm($player, $target1);
	}        
	break;    
	    
	case 340:
	if($target instanceof Player){
	$target1 = $target->getName();    
	$this->getServer()->dispatchCommand($player, "invsee $target1");
	}    
	return true;
	break;
	}
	}
	}
	}
	

}