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
	public $payslips = array();
	public $deductions = array();
	public $holiday_remaining = array();
	
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
	
	private function add_deduction($year, $payslip, $deduction) {
		$field = 'ew_' . $deduction;
		if (!array_key_exists($field, $payslip) || $payslip[$field] == 0.0 || in_array($deduction, $this->deductions[$year]))
			return;
		
			$this->deductions[$year][] = $deduction;
	}
	
	public function add_payslip($row) {
		$date = new DateTime($row['ew_date']);
		$year = $date->Format("Y");
		$month = $date->Format("m") + 0;
		if ($month < 4) {
			$year--;
		}
		if (!array_key_exists($year, $this->payslips)) {
			$this->payslips[$year] = array();
			$this->deductions[$year] = array();
			$this->holiday_remaining[$year] = 0.0;
		}
		$this->payslips[$year][$month] = $row;
		$this->holiday_remaining[$year] += $row['ew_holiday_earned'] - $row['ew_holiday'];
		
		foreach(['paye', 'nic', 'pension', 'studentloan'] as $deduction) {
			$this->add_deduction($year, $row, $deduction);
		}
	}
	
	public function get_payslips($year) {
		if (array_key_exists($year, $this->payslips)) {
			return $this->payslips[$year];
		}
		
		return null;
	}

	public function get_deductions($year) {
		if (array_key_exists($year, $this->deductions)) {
			return $this->deductions[$year];
		}
		
		return null;
	}
	
	public function get_holiday_remaining($year) {
		if (array_key_exists($year, $this->holiday_remaining)) {
			return $this->holiday_remaining[$year];
		}
		
		return null;
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
		
		$sql = "Select ew_nickname, ew_date, ew_rate, ew_hours, ew_holiday_earned, ew_holiday, ew_gross, ew_net, ew_paye, ew_nic, ew_studentloan, ew_pension from vwemployeewage";
		
		$result = $petadmin_db->execute($sql);
		
		foreach($result as $row) {
			$employee = $this->by_nickname[$row['ew_nickname']];
			if ($employee) {
				$employee->add_payslip($row);
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
		crowbank_log('Employees->Inside get_by_email');
		$this->load();
		if (isset($this->by_email[$email]))
			return $this->by_email[$email];

		return NULL;
	}
}
