<?php
class Availability {
	private $runs = array();
	private $rates = array();
	private $rate_dates = array();
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
			$this->runs = array();
			$this->rates = array();
			$this->rate_dates = array();
		}
			
		$sql = "select ra_date, ra_spec, ra_rt_desc, ra_availability_am, ra_availability_pm from my_availability";
		
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$date = new DateTime($row['ra_date']);
			$species = $row['ra_spec'];
			$rt_desc = $row['ra_rt_desc'];
			$availability_am = (int) $row['ra_availability_am'];
			$availability_pm = (int) $row['ra_availability_pm'];

			if (!array_key_exists($species, $this->runs)) {
				$this->runs[$species] = array();
			}
			if (!array_key_exists($rt_desc, $this->runs[$species])) {
				$this->runs[$species][$rt_desc] = array();
			}
			$this->runs[$species][$rt_desc][$date->getTimestamp()] = ['am' => $availability_am, 'pm' => $availability_pm];
		}

		$sql = "select rate_start_date, rate_category, rate_service, rate_amount from my_rate";
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$date = new DateTime($row['rate_start_date']);
			$ts = $date->getTimestamp();
			$category = $row['rate_category'];
			$service = $row['rate_service'];
			$amount = $row['rate_amount'];

			if (!array_key_exists($ts, $this->rates)) {
				$this->rates[$ts] = array();
			}
			
			if (!array_key_exists($service, $this->rates[$ts])) {
				$this->rates[$ts][$service] = array();
			}
			
			$this->rates[$ts][$service][$category] = $amount;
		}
		
		$sql = "select distinct rate_start_date from my_rate order by rate_start_date desc";
		$result = $petadmin_db->execute($sql);
		foreach ($result as $row) {
			$date = new DateTime($row['rate_start_date']);
			$this->rate_dates[] = $date->getTimestamp();
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
	
	public function check($start_date, $end_date, $start_time, $end_time, $dogs, $cats, $deluxe = 0) {
		$availability = 0;
		$this->load();

		$rate_array = array();
		foreach($this->rate_dates as $r_date) {
			if ($start_ts < $r_date) {
				continue;
			}
			$rate_array = $this->rates[$r_date];
			break;
		}
		
		for($date = clone $start_date; $date <= $end_date; $date->add($this->day)) {
/* 			if ($date == $end_date and $end_time < '13:30') {
				break;
			}
 */			
			$ts = $date->getTimestamp();
			$use_am = ($date > $start_date or $start_time < '13:00');
			$use_pm = ($date < $end_date or $end_time > '13:00');
			
			$dog_run_type = 'Any';
			if ($deluxe) {
				$dog_run_type = 'Deluxe';
			}
			
			if ($dogs > 0 and array_key_exists($ts, $this->runs['Dog']['Any'])) {
				if ($use_am and	$this->runs['Dog'][$dog_run_type][$ts]['am'] > $availability) {
					$availability = $this->runs['Dog'][$dog_run_type][$ts]['am'];
				}
				if ($use_pm and	$this->runs['Dog'][$dog_run_type][$ts]['pm'] > $availability) {
					$availability = $this->runs['Dog'][$dog_run_type][$ts]['pm'];
				}
			}
			
			if ($cats > 0 and array_key_exists($ts, $this->runs['Cat']['Cat'])) {
				if ($use_am and	$this->runs['Cat']['Cat'][$ts]['am'] > $availability) {
					$availability = $this->runs['Cat']['Cat'][$ts]['am'];
				}
				if ($use_pm and	$this->runs['Cat']['Cat'][$ts]['pm'] > $availability) {
					$availability = $this->runs['Cat']['Cat'][$ts]['pm'];
				}
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
			$daily_charge_low = $rate_array['BOARD']['Cat'] + ($cats - 1) * $rate_array['BOARD2']['Cat'];
			$daily_charge_high = $rate_array['BOARD']['Cat']* $cats;
		}
		
		if ($dogs > 0) {
			if ($deluxe) {
				$daily_charge_low += $rate_array['BOARD']['Deluxe']+ ($dogs - 1) * $rate_array['BOARD2']['Deluxe'];
				$daily_charge_high += $rate_array['BOARD']['Deluxe']* $dogs;
			} else {
				$daily_charge_low += $rate_array['BOARD']['Dog small']+ ($dogs - 1) * $rate_array['BOARD2']['Dog small'];
				$daily_charge_high += $rate_array['BOARD']['Dog large']* $dogs;
			}
		}
		
		$low_estimate = $night_count * $daily_charge_low;
		$high_estimate = $night_count * $daily_charge_high;
		
		$fee = 0.00;
		if (array_key_exists('FEE', $rate_array)) {
			$fee = $rate_array['FEE']['Any'];
			
			$low_estimate += $fee;
			$high_estimate += $fee;
		}
			
		return ['availability'=>$availability, 'low'=>$low_estimate, 'high'=>$high_estimate];
	}
}