<?php
class Availability {
	private $runs = array();
	private $day;
	public $isLoaded;

	public function __construct() {
		$this->isLoaded = FALSE;
		$this->day = new DateInterval('P1D');
	}
	
	public function load($force = false) {
		global $petadmin_db;
		if ($this->isLoaded and !$force) {
			return;
		}
		
		if ($this->isLoaded) {
			$runs = array();
		}
		
		petadmin_log('Loading availability');
		
		$sql = "select ra_date, ra_spec, ra_rt_desc, ra_availability from my_availability";
		
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$date = new DateTime($row['ra_date']);
			$species = $row['ra_spec'];
			$rt_desc = $row['ra_rt_desc'];
			$availability = (int) $row['ra_availability'];
			
			if (!array_key_exists($species, $this->runs)) {
				$this->runs[$species] = array();
			}
			if (!array_key_exists($rt_desc, $this->runs[$species])) {
				$this->runs[$species][$rt_desc] = array();
			}
			$this->runs[$species][$rt_desc][$date->getTimestamp()] = $availability;
		}
		
		$this->isLoaded = true;
	}
	
	public function availability($date, $species, $runtype) {
	    $a = 0;
	    $this->load();
	    
	    $ts = $date->getTimestamp();
	    if (isset($this->runs[$species][$runtype][$ts]))
	        $a = $this->runs[$species][$runtype][$ts];
	    
	    return $a;
	}
	
	public function check($start_date, $end_date, $end_time, $dogs, $cats) {
		$availability = 0;
		$this->load();

		for($date = clone $start_date; $date <= $end_date; $date->add($this->day)) {
			if ($date == $end_date and $end_time < '13:30') {
				break;
			}
			$ts = $date->getTimestamp();
			if ($dogs > 0 and array_key_exists($ts, $this->runs['Dog']['Any']) and $this->runs['Dog']['Any'][$ts] > $availability) {
				$availability = $this->runs['Dog']['Any'][$ts];
			}
			if ($cats > 0 and array_key_exists($ts, $this->runs['Cat']['Cat']) and $this->runs['Cat']['Cat'][$ts] > $availability) {
				$availability = $this->runs['Cat']['Cat'][$ts];
			}
		}
		
		$daily_charge_low = 0.0;
		$daily_charge_high = 0.0;
		$night_count = $end_date->diff($start_date)->days;
		
		echo "Night Count between " . $start_date->format('d/m/Y') . " and " . $end_date->format("d/m/Y") . " is $night_count<br>";
		
		if ($end_time > '13:30') {
			$night_count += 1;
		}
		
		if ($cats > 0) {
			$daily_charge_low = 10.50 + ($cats - 1) * 8.40;
			$daily_charge_high = 10.50 * $cats;
		}
		
		if ($dogs > 0) {
			$daily_charge_low += 15.50 + ($dogs - 1) * 12.40;
			$daily_charge_high += 17.50 * $dogs;
		}
		
		$low_estimate = $night_count * $daily_charge_low;
		$high_estimate = $night_count * $daily_charge_high;
		
		return ['availability'=>$availability, 'low'=>$low_estimate, 'high'=>$high_estimate];
	}
}