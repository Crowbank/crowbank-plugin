<?php
class Inventory {
	public $date;
	public $items = array();
	const FIELDS = array(
		'inv_dog_revenue' => 'dog_revenue',
		'inv_cat_revenue' => 'cat_revenue',
		'inv_visit_am' => 'visit_am',
		'inv_visit_pm' => 'visit_pm',
		'inv_dog_stay' => 'dog_stay',
		'inv_cat_stay' => 'cat_stay',
		'inv_dog_in_am' => 'dog_in_am',
		'inv_dog_out_am' => 'dog_out_am',
		'inv_dog_in_pm' => 'dog_in_pm',
		'inv_dog_out_pm' => 'dog_out_pm',
		'inv_cat_in_am' => 'cat_in_am',
		'inv_cat_out_am' => 'cat_out_am',
		'inv_cat_in_pm' => 'cat_in_pm',
		'inv_cat_out_pm' => 'cat_out_pm',
		'inv_dog_day' => 'dog_day'
	);
	
	public function __construct($row){
		$this->date = new DateTime($row['inv_date']);
		
		foreach (self::FIELDS as $key => $item) {
			$this->items[$item] = $row[$key];
		}		
	}
	
	public function pet_inout($direction, $time, $species) {
		$key = strtolower($species) . '_' . $direction . '_' . $time;
		
		if ($time == '') {
			return $this->pet_inout($direction, 'am', $species) + $this->pet_inout($direction, 'pm', $species);
		}
		
		return array_key_exists($key, $this->items) ? $this->items[$key] : 0;
	}
	
	public function occupied($time, $species) {
		if ($time == 'morning') {
			return ($species == 'Dog') ? $this->items['dog_stay'] + $this->items['dog_out_am'] + $this->items['dog_out_pm'] :
				$this->items['cat_stay'] + $this->items['cat_out_am'] + $this->items['cat_out_pm'];
		} else if ($time == 'am') {
			return ($species == 'Dog') ? $this->items['dog_stay'] + $this->items['dog_out_am'] +
			$this->items['dog_out_pm'] + $this->items['dog_in_am'] + $this->items['dog_day']:
			$this->items['cat_stay'] + $this->items['cat_out_am'] + $this->items['cat_out_pm'] + $this->items['cat_in_am'];
		} else if ($time == 'noon') {
			return ($species == 'Dog') ? $this->items['dog_stay'] + $this->items['dog_out_pm'] +
			$this->items['dog_in_am'] + $this->items['dog_day']:
			$this->items['cat_stay'] + $this->items['cat_out_pm'] + $this->items['cat_in_am'];
		} else if ($time == 'pm') {
			return ($species == 'Dog') ? $this->items['dog_stay'] + $this->items['dog_out_pm'] +
			$this->items['dog_in_am'] + $this->items['dog_in_pm'] + $this->items['dog_day']:
			$this->items['cat_stay'] + $this->items['cat_out_pm'] + $this->items['cat_in_am'] + $this->items['cat_in_pm'];
			} else if ($time == 'evening') {
				return ($species == 'Dog') ? $this->items['dog_stay'] + $this->items['dog_in_am'] +	$this->items['dog_in_pm'] :
				$this->items['cat_stay'] + $this->items['cat_in_am'] + $this->items['cat_in_pm'];
		}
	}
}

class Inventories {
	public $by_date = array();
	public $isLoaded = FALSE;
	public $count = 0;
	
	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		$sql = 'Select inv_date, inv_dog_revenue, inv_cat_revenue, inv_dog_stay, inv_cat_stay, inv_visit_am, inv_visit_pm,
inv_dog_in_am,  inv_dog_in_pm, inv_dog_out_am,  inv_dog_out_pm,
inv_cat_in_am,  inv_cat_in_pm, inv_cat_out_am,  inv_cat_out_pm, inv_dog_day
from my_inventory';
		
		if ($this->isLoaded and !$force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_date = array();
			$this->count = 0;
		}
		
		petadmin_log('Loading inventories');
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$inventory = new Inventory($row);
			$this->count++;
			$this->by_date[$inventory->date->getTimestamp()] = $inventory;
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function get($date) {
		$this->load();	

		if (array_key_exists($date->getTimestamp(), $this->by_date)) {
			return $this->by_date[$date->getTimestamp()];
		}
		
		return NULL;
	}
}