<?php

declare(strict_types=1);

namespace alvin0319\WorldGuard;

use JsonSerializable;

final class WorldData implements JsonSerializable{

	public const INTERACT = "interact";
	public const BREAK_BLOCK = "break_block";
	public const PLACE_BLOCK = "place_block";
	public const PVP = "pvp";
	public const KEEP_INVENTORY = "keep_inventory";

	public const DEFAULT_SETTINGS = [
		self::INTERACT => true,
		self::BREAK_BLOCK => true,
		self::PLACE_BLOCK => true,
		self::PVP => false,
		self::KEEP_INVENTORY => true
	];

	protected array $settings;

	public function __construct(array $settings = self::DEFAULT_SETTINGS){
		$this->settings = $settings;
	}

	public function get(string $name) : bool{
		return $this->settings[$name] ?? false;
	}

	public function set(string $name, bool $value) : void{
		$this->settings[$name] = $value;
	}

	public function jsonSerialize() : array{
		return $this->settings;
	}
}