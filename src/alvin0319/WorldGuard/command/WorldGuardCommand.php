<?php

declare(strict_types=1);

namespace alvin0319\WorldGuard\command;

use alvin0319\WorldGuard\WorldData;
use alvin0319\WorldGuard\WorldGuard;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use function count;

final class WorldGuardCommand extends Command{

	public function __construct(){
		parent::__construct("worldguard");
		$this->setDescription("Manage world protection");
		$this->setPermission("worldguard.command");
		$this->setUsage("/worldguard <pvp|interact|place|break>");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(WorldGuard::$prefix . "You must run this command on in-game.");
			return false;
		}
		$worldData = WorldGuard::getInstance()->getWorldData($sender->getWorld());
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}
		switch($args[0]){
			case "pvp":
				$name = WorldData::PVP;
				break;
			case "interact":
				$name = WorldData::INTERACT;
				break;
			case "place":
				$name = WorldData::PLACE_BLOCK;
				break;
			case "break":
				$name = WorldData::BREAK_BLOCK;
				break;
			case "keepinventory":
				$name = WorldData::KEEP_INVENTORY;
				break;
			case "hunger":
				$name = WorldData::HUNGER;
				break;
			case "fall":
				$name = WorldData::FALL_DAMAGE;
				break;
			default:
				$sender->sendMessage(WorldGuard::$prefix . "Unknown setting.");
				return false;
		}
		$curValue = $worldData->get($name);
		$worldData->set($name, !$curValue);
		$sender->sendMessage(WorldGuard::$prefix . "Changed $args[0] on " . $sender->getWorld()->getFolderName() . " to " . (!$curValue ? "allow" : "disallow"));
		return true;
	}
}