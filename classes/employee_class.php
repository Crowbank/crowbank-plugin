<?php
class Employee {
	public $no;
	public $forename;
	public $surname;
	public $nickname;
	public $rank;
	public $iscurrent;
	public $email;
	public $facebook;
	public $start_date;
	public $end_date;
	public $order;
	public $mobile;
	public $shared;
	
	public function __construct($row) {
		$this->no = (int) $row['emp_no'];
		$this->forename = $row['emp_forename'];
		$this->surname = $row['emp_surname'];
		$this->nickname = $row['emp_nickname'];
		$this->rank = $row['emp_rank'];
		$this->iscurrent = (int) $row['emp_iscurrent'];
		$this->email = $row['emp_email'];
		$this->mobile = $row['emp_telno_mobile'];
		$this->facebook = $row['emp_facebook'];
		$this->start_date = new DateTime($row['emp_start_date']);
		$this->end_date = new DateTime($row['emp_end_date']);
		$this->order = (int) $row['emp_order'];
		$this->shared = $row['emp_shared'];
	}
}

class Employees {
	public $all = array();
	private $by_nickname = array();
	private $by_email = array();
	public $current = array();
	public $isLoaded = FALSE;
	public $count = 0;
	
	public function __construct() {
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		$sql = "Select emp_no, emp_forename, emp_surname, emp_nickname, emp_rank, emp_iscurrent,
emp_email, emp_facebook, emp_start_date, emp_end_date, emp_order, emp_telno_mobile, emp_shared from my_employee";
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->all = array();
			$this->by_nickname = array();
			$this->current = array();
			$this->count = 0;
		}
		
		petadmin_log('Loading employees');
		
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$employee = new Employee($row);
			$this->count++;
			$this->all[$employee->no] = $employee;
			$this->by_nickname[$employee->nickname] = $employee;
			if ($employee->email)
				$this->by_email[$employee->email] = $employee;
			if ($employee->iscurrent) {
				$this->current[$employee->no] = $employee;
			}
		}
		$this->isLoaded = TRUE;
	}
	
	public function get_by_any($search) {
		$this->load();
		$employee = $this->get_by_no($search);
		if (!$employee)
			$employee = $this->get_by_nickname($search);

		if (!$employee)
			$employee = $this->get_by_email($search);

		return $employee;
	}

	public function get_by_no($emp_no) {
		$this->load();
		if (isset($this->all[$emp_no]))
			return $this->all[$emp_no];

		return NULL;
	}

	public function get_by_nickname($nickname) {
		$this->load();
		
		if (array_key_exists($nickname, $this->by_nickname)) {
			return $this->by_nickname[$nickname];
		}
		return NULL;
	}

	public function get_by_email($email) {
		$this->load();
		if (isset($this->by_email[$email]))
			return $this->by_email[$email];

		return NULL;
	}
}
