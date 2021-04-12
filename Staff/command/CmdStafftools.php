<?php
declare(strict_types=1);
namespace Staff\command;

use Staff\Loader;
use pocketmine\plugin\Plugin;
use Staff\libs\FormAPI\SimpleForm;
use Staff\libs\FormAPI\FormApi;
use Staff\libs\FormAPI\Form;
use Staff\libs\FormAPI\ModalForm;
use Staff\libs\FormAPI\CustomForm;
use pocketmine\{Player, Server};
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, Command, PluginIdentifiableCommand};


class CmdStafftools extends Command implements PluginIdentifiableCommand {	
    
	private $plugin;
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
    parent::__construct("punish", "Open punish menu", "");
   $this->setPermission("cmd.staff");
	}
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	
	public function execute(CommandSender $sender, string $label, array $args){
	
	if(!$sender instanceof Player){
	}
	if(!$this->testPermission($sender)){
	return;
	}
		
	$form = new SimpleForm(function(Player $player, ?int $data){
			if($data === null) return;
			if($data == 0){
				$subform = new SimpleForm(function(Player $player, ?string $data){
					if($data == null) return;
					$this->plugin->menuForm($player, $data);
				});
				$subform->setTitle("§eSelecciona un jugador en Linea");
				foreach($this->getServer()->getOnlinePlayers() as $p){
					$subform->addButton($p->getName(), -1, "", $p->getName());
				}
				$player->sendForm($subform);
			}elseif($data == 1){
				$subform = new CustomForm(function(Player $player, ?array $data){
					if(empty($data)) return;
					$this->plugin->menuForm($player, $data[0]);
				});
				$subform->setTitle("§eBuscar escribiendo");
				$subform->addInput("§fIngresa nombre de jugador", "null");
				$player->sendForm($subform);
			}elseif($data == 2){
			    $Sform = new SimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			switch($data){
				case 0: 
				    $this->getPlugin()->openTcheckUI2($player);
				    break;
			    case 1:
			        	$this->getPlugin()->openTcheckUI($player);
			        break;
			    
			}
			
			
		});

		$Sform->setTitle("Lista de Castigo");
		$Sform->addButton("Muted");
		$Sform->addButton("Bans");
		
		$Sform->sendToPlayer($player);
			    
			}
		});
		$form->setTitle("StaffTools");
		$form->setContent("¿Cómo le gustaría seleccionar  al jugador?");
		$form->addButton("Selecciona un jugadores en Linea");
		
		$form->addButton("Buscar escribiendo");
		$form->addButton("Punish List");
		$sender->sendForm($form);
	}
	public function getPlugin(): Plugin{
      return $this->plugin;
    }
}
