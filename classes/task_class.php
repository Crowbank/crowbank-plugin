<?php
class Task {
	public $no;
	public $desc;
	public $start_time;
	public $end_time;
	public $booking;
	public $customer;
	public $pet;
	
	public function __construct($row) {
		global $petadmin;
		$this->no = (int) $row['task_no'];
		$this->desc = $row['task_desc'];
		$this->start_time = new DateTime($row['task_start_time']);
		$this->end_time = new DateTime($row['task_end_time']);

		$bk_no = (int) $row['task_bk_no'];
		if ($bk_no) {
			$this->booking = $petadmin->bookings->get_by_no($bk_no);
		} else {
			$this->booking = NULL;
		}
		
		$cust_no = (int) $row['task_cust_no'];
		if ($cust_no) {
			$this->customer = $petadmin->customers->by_no[$cust_no];
		} else {
			$this->customer = NULL;
		}
		
		$pet_no = (int) $row['task_pet_no'];
		if ($pet_no) {
			$this->pet = $petadmin->pets->get_by_no($pet_no);
		} else {
			$this->pet = NULL;
		}
	}
	
	public function to_row() {
		return '<tr><td>' . $this->start_time->format('H:i') . '</td><td>' . $this->desc . '</td></tr>';
	}
}

class Tasks {
	public $by_no = array();
	public $by_date = array();
	public $isLoaded = FALSE;
	public $count = 0;

	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		
		$sql = 'Select task_no, task_desc, task_start_time, task_end_time, task_bk_no, task_cust_no, task_pet_no from my_task';
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->by_no = array();
			$this->by_short_desc = array();
			$this->count = 0;
		}
		
		crowbank_log('Loading tasks');
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$task = new Task($row);
			$this->count++;
			$this->by_no[$task->no] = $task;
			$date = new DateTime($task->start_time->format('Y-m-d'));
			$timestamp = $date->getTimestamp();
			
			if (!array_key_exists($timestamp, $this->by_date)) {
				$this->by_date[$timestamp] = array();
			}
			$this->by_date[$timestamp][] = $task;
		}
		
		$this->isLoaded = TRUE;
	}
	
	public function task_table($date) {
		$this->load();
		if (!array_key_exists($date->getTimestamp(), $this->by_date)) {
			return "No Events<br>";
		}
		
		$r = '<table class="table"><thead><th>Time</th><th>Event</th></thead>';
		foreach ($this->by_date[$date->getTimestamp()] as $task) {
			$r .= $task->to_row();
		}
		$r .= '</table>';
		return $r;
	}
}