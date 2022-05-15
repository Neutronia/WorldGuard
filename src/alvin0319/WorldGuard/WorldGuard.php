<?php

declare(strict_types=1);

namespace alvin0319\WorldGuard;

use alvin0319\WorldGuard\command\WorldGuardCommand;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use RuntimeException;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;

final class WorldGuard extends PluginBase implements Listener{
	use SingletonTrait;

	public static string $prefix = "§l§6NT §f> §r§7";

	/** @var WorldData[] */
	protected array $worlds = [];

	public static function getInstance() : WorldGuard{
		return self::$instance;
	}

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!is_dir($this->getDataFolder() . "worlds/") && !mkdir($concurrentDirectory = $this->getDataFolder() . "worlds/", 0777, true) && !is_dir($concurrentDirectory)){
			throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
		}

		@mkdir($this->getDataFolder() . "worlds/islands/", 0777, true);

		$this->getServer()->getCommandMap()->register("worldguard", new WorldGuardCommand());
	}

	public function onDisable() : void{
		foreach($this->worlds as $name => $world){
			Filesystem::safeFilePutContents($this->getDataFolder() . "worlds/{$name}.json", json_encode($world->jsonSerialize(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
		}
		$this->worlds = [];
	}

	public function loadWorldData(World $world) : WorldData{
		$data = WorldData::DEFAULT_SETTINGS;
		if(file_exists($file = $this->getDataFolder() . "worlds/{$world->getFolderName()}.json")){
			$data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
		}
		return $this->worlds[$world->getFolderName()] = new WorldData($data);
	}

	public function unloadWorldData(World $world) : void{
		if(isset($this->worlds[$world->getFolderName()])){
			$data = $this->worlds[$world->getFolderName()]->jsonSerialize();
			file_put_contents($this->getDataFolder() . "worlds/{$world->getFolderName()}.json", json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
		}
	}

	public function getWorldData(World $world) : WorldData{
		return $this->worlds[$world->getFolderName()] ?? $this->loadWorldData($world);
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$world = $event->getWorld();
		$this->loadWorldData($world);
	}

	public function onDeath(PlayerDeathEvent $event) : void{
		$worldData = $this->getWorldData($event->getPlayer()->getWorld());
		$event->setKeepInventory($worldData->get(WorldData::KEEP_INVENTORY));
	}

	public function onWorldUnload(WorldUnloadEvent $event) : void{
		$world = $event->getWorld();
		$this->unloadWorldData($world);
	}

	/**
	 * @param PlayerInteractEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();

		$worldData = $this->getWorldData($player->getWorld());

		if($worldData->get(WorldData::INTERACT)){
			return;
		}
		if($player->hasPermission("worldguard.bypass")){
			return;
		}
		$event->cancel();
	}

	/**
	 * @param BlockPlaceEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();

		$worldData = $this->getWorldData($player->getWorld());

		if($worldData->get(WorldData::PLACE_BLOCK)){
			return;
		}
		if($player->hasPermission("worldguard.bypass")){
			return;
		}
		$event->cancel();
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();

		$worldData = $this->getWorldData($player->getWorld());

		if($worldData->get(WorldData::BREAK_BLOCK)){
			return;
		}
		if($player->hasPermission("worldguard.bypass")){
			return;
		}
		$event->cancel();
	}

	/**
	 * @param EntityDamageEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if(!$entity instanceof Player){
			return;
		}
		$worldData = $this->getWorldData($entity->getWorld());
		$cause = $event->getCause();
		if($cause === EntityDamageEvent::CAUSE_FALL){
			if($worldData->get(WorldData::FALL_DAMAGE)){
				return;
			}
		}elseif($cause === EntityDamageEvent::CAUSE_DROWNING){
			if($worldData->get(WorldData::DROWNING_DAMAGE)){
				return;
			}
		}elseif($cause === EntityDamageEvent::CAUSE_LAVA){
			if($worldData->get(WorldData::LAVA_DAMAGE)){
				return;
			}
		}elseif($cause === EntityDamageEvent::CAUSE_SUFFOCATION){
			if($worldData->get(WorldData::SUFFOCATION_DAMAGE)){
				return;
			}
		}else{
			if(!$event instanceof EntityDamageByEntityEvent){
				return;
			}
			$player = $event->getDamager();
			if(!$player instanceof Player){
				return;
			}

			if($worldData->get(WorldData::PVP)){
				return;
			}
		}
		$event->cancel();
	}

	/**
	 * @param PlayerExhaustEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerHunger(PlayerExhaustEvent $event) : void{
		$player = $event->getPlayer();
		if(!$player instanceof Player){
			return;
		}
		$worldData = $this->getWorldData($player->getWorld());
		if(!$worldData->get(WorldData::HUNGER)){
			return;
		}
		if($player->hasPermission("worldguard.bypass")){
			return;
		}
		$event->cancel();
	}

	/**
	 * @param PlayerDeathEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getEntity();
		if(!$player instanceof Player){
			return;
		}
		$worldData = $this->getWorldData($player->getWorld());
		$event->setKeepInventory($worldData->get(WorldData::KEEP_INVENTORY));
	}
}