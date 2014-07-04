<?php
/*********************************************************************************
Plugin loadmaplist.php v2
Lists the maplists in the MatchSettings folder in a manialink
Loads the clicked maplist

@author aca
some code taken and adapted from plugin.rasp_jukebox.php

*********************************************************************************/


Aseco::addChatCommand('/listml', 'list maplists');
Aseco::registerEvent('onPlayerManialinkPageAnswer', 'event_lml');

//triggered, when someone clicks in the displayed maplist-manialink
function event_lml($aseco, $answer){
	$login = $answer[1];
	$action = $answer[2];
	
	if(substr($action, -4, 4) == ".txt"){//only if a matchsettings-file is clicked
		$rtn = $aseco->client->query('LoadMatchSettings', "MatchSettings/$action");
		if(!$rtn){
			trigger_error('[' . $aseco->client->getErrorCode() . '] ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
			$aseco->console($aseco->client->getErrorMessage());
			$aseco->client->query('ChatSendServerMessageToLogin', $aseco->client->getErrorMessage(), $login);
		}
		else{
			$aseco->console("$action loaded by $login");
			$aseco->client->query('ChatSendServerMessage', "MatchSettings $action loaded by $login");
		}
	}
	elseif(substr($action, -1, 1) == '/'){//if a directory is clicked
		$player = $aseco->server->players->getPlayer($login);
		$command = array();
		$command['author'] = $player;
		$command['params'] = $action;
		chat_listml($aseco, $command);
	}
}


//returns all files in directory MatchSettings as an array 
function lml_fetchMatchsettings($aseco, $dir){
	$msArray = array();
	$mapsDir = $aseco->server->mapdir;
	
	//specified a subfolder
	$msDir = $mapsDir.'MatchSettings/'.$dir;
	
	$msDirHandle = opendir($msDir);
	while($file = readdir($msDirHandle)){
		if($file != '.'){
			if(substr($file, -4, 4) != ".txt"){//is it a directory
				$file = $file."/";
			}
			//only show directory up, when not on toplevel
			if(!($dir == '' && $file == '../')){
				array_push($msArray, $file);
			}
			
		}
	}
	closedir($msDirHandle);
	
	//sort the maplists alphabetically
	sort($msArray);
	return $msArray;
}



//called, when "/listml" ist typed in chat by a (Master)Admin
function chat_listml($aseco, $command) {

	$player = $command['author'];
	
	if($aseco->isMasterAdmin($player) || $aseco->isAdmin($player)){
	
		$folder = '';
		$params = $command['params'];
		
		
		
		if($params != ''){
			$folder = $params;
			if(substr($folder, -3, 3) == '../'){//when directory-up
				$pathArray = explode('/', $folder);
				$folder = '';
				$count = count($pathArray);
				for($i = 0; $i < $count-3; $i++){
					$folder .= $pathArray[$i].'/';
				}
			}
		}
		
		$msFileArray = lml_fetchMatchsettings($aseco, $folder);
		
		$head = "Maplists in MatchSettings/$folder";
		$page = array();
		if ($aseco->settings['clickable_lists'])
			$page[] = array('$i$oId', '$i$oName $f00(click to load)');
		else
			$page[] = array('Id', 'Name');

		$tid = 1;
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(1.10, 0.1, 0.6), array('Icons128x128_1', 'Load', 0.02));
		
		foreach ($msFileArray as $item) {
			$page[] = array(str_pad($tid, 2, '0', STR_PAD_LEFT), array($item, $folder.$item));//action
			$tid++;
			
			if (++$lines > 14) {
				$player->msgs[] = $page;
				$lines = 0;
				$page = array();
					if ($aseco->settings['clickable_lists'])
						$page[] = array('$i$oId', '$i$oName $f00(click to load)');
					else
						$page[] = array('Id', 'Name');
			}
		}
		if (count($page) > 1) {
			$player->msgs[] = $page;
		}
		// display
		display_manialink_multi($player);
	}
} 


?>
