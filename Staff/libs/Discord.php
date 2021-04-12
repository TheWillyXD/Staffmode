<?php

namespace Staff\libs;

use Staff\Loader;
use pocketmine\Player;
use Staff\libs\Embed;
use Staff\libs\Message;
use Staff\libs\Country;

class Discord {
	protected $data = [];
	
	/** @var string */
	public $webhook;
	/** @var string */
	public $username;
	
	/**
	 * @param String $hook
	 * @param String $title
	 * @param String $message
	 */
	public static function sendToDiscord(String $hook, String $title, String $message){
	    
	    $msg = new Message();
	    $colorval = hexdec("FF0000");
				$msg->setUsername("$title");
				$msg->setAvatarURL("https://img.icons8.com/fluent/344/warning-shield.png");

				$embed = new Embed();
				$embed->setTitle("SoupHCF");
				$embed->setColor($colorval);
				$embed->setDescription("$message"); 
				
				$embed->setFooter("New Report", "https://dunb17ur4ymx4.cloudfront.net/webstore/logos/71985a342a22cf259de3894cd3ca235605613578.jpg");
				$embed->setThumbnail("https://media0.giphy.com/media/xDbS5VgmigHgk/giphy.gif");
				$embed->setImage("https://media0.giphy.com/media/xDbS5VgmigHgk/giphy.gif");
				//$embed->setTimestamp(new \DateTime("now"));
				$msg->addEmbed($embed);
	    
	    
		$discord = curl_init();
		curl_setopt($discord, CURLOPT_URL, $hook);
		//["content" => $message, "username" => $title,"avatar_url" => "https://img.icons8.com/fluent/344/warning-shield.png",$emp]
	
		curl_setopt($discord, CURLOPT_POSTFIELDS, json_encode($msg));
		curl_setopt($discord, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
		curl_setopt($discord, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($discord, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($discord);
        curl_error($discord);
	}
	
	
	
}