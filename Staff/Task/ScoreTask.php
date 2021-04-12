<?php

namespace Staff\Task;

use Staff\Loader;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\level\Level;
use Staff\Events\{Events};
use Staff\commands\BaseCommand;
use Staff\EventListener;
use pocketmine\event\Listener;
use pocketmine\scheduler\Task;
use pocketmine\utils\{Config, TextFormat as TE};
use function in_array;

class ScoreTask extends Task {

    /** @var Loader */
    protected $plugin;

    /**
     * Score Constructor
     * @param Loader $plugin
     */

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }
    public function getPlugin(){
	return $this->plugin;
	}

    public function onRun(int $currentTick){
        foreach(Server::getInstance()->getOnlinePlayers() as $p){
            if($p->spawned){
                if($this->getPlugin()->isVanish($p->getName())){
                    foreach(Server::getInstance()->getOnlinePlayers() as $player){
			            if($player->hasPermission("vanish.see")){
			                $player->showPlayer($p);
		                }else{
			                $player->hidePlayer($p);
			                
		                }
                    }
                }
            }
        }
    }
    
}

