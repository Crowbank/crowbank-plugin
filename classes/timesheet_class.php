<?php
function echo_cell($content, $class, $class2 = '') {
	return '<td class="' . $class . ' ' . $class2 . '">' . $content . '</td>';;
}

class Shift {
	public $week_start;
	public $employee;
	public $status;
	public $published_time;
	public $days = array();
	public $dirty = false;
	
	public function __construct($row) {
		global $petadmin;
		
		$this->week_start = new DateTime($row['ts_weekstart']);
		$nickname = $row['ts_emp_nickname'];
		$this->employee = $petadmin->employees->get_by_nickname($nickname);
		$this->status = $row['ts_status'];
		$this->published_time = new DateTime($row['ts_published_time']);
		
		for($i = 0; $i < 7; $i++) {
			$am = substr($this->status, 2 * $i, 1);
			$pm = substr($this->status, 2 * $i + 1, 1);
			if ($am == '-') {
				$am = ' ';
			}
			if ($pm == '-') {
				$pm = ' ';
			}
			$this->days[$i] = array('am' => $am, 'pm' => $pm);
		}
	}
	
	public function update($i, $am, $pm) {
		$old_am = substr($this->status, 2 * $i, 1);
		$old_pm = substr($this->status, 2 * $i + 1, 1);
		if ($am <> $old_am or $pm <> $old_pm) {
			$this->dirty = true;
			$this->status = substr_replace($this->status, $am . $pm, 2 * $i, 2);
		}
	}
	
	public function flush() {
		global $petadmin_db;
		if (!$this->dirty)
			return;
		
		$now = new DateTime();
		
		$sql = 'Update my_timesheet set ts_status = \'' . $this->status;
		$sql .= '\', ts_published_time = \'' . $now->format('Y-m-d H:i:s') . '\' where ts_emp_nickname=\'';
		$sql .= $this->employee->nickname . '\' and ts_weekstart = \'';
		$sql .= $this->week_start->format('Y-m-d') . '\'';
		
		$petadmin_db->execute($sql);
		
		$msg = new Message('timesheet_update',
				array('weekstart' => $this->week_start->format('Y-m-d'),
						'nickname' => $this->employee->nickname, 'status' => $this->status)
				);
		
		$msg->flush();
	}
}

class Timesheets {
	public $count = 0;

	const RANKCLASS = array(
		'Owner' => 'owner_class',
		'O' => 'office_class',
		'Shift Leader' => 'supervisor_class',
		'Kennel Assistant' => 'assistant_class',
		'Dog Walker' => 'walker_class',
		'Volunteer' => 'volunteer_class',
		'U' => 'unavailable_class',
		'H' => 'vacation_class',
		' ' => ''
	);
	
	public $weekly = array();
	public $modified_times = array();
	public $by_employee = array();
	public $isLoaded = FALSE;
	public $weeks = array();
	
	public function display_employee($employee, $weekstart, $weeks, $post, $overrides) {
		global $petadmin;

		$this->load();

		$nickname = $employee->nickname;
		$rank = $employee->rank;
		$day = new DateInterval('P1D');
		$week = new DateInterval('P7D');
		$d6 = new DateInterval('P6D');
		$modified = $this->modified_times[$weekstart->getTimestamp()];
		$active = true;
		$r = '<div style="overflow-x:auto;"><table class="table timesheet_table' . ($active ? ' tight_table' : '') . '">';

		$w = -1;

		$r .=  '<tr><td></td><td colspan="2" class="date_header">Monday</td>
<td colspan="2" class="date_header">Tuesday</td>
<td colspan="2" class="date_header">Wednesday</td>
<td colspan="2" class="date_header">Thursday</td>
<td colspan="2" class="date_header">Friday</td>
<td colspan="2" class="date_header">Saturday</td>
<td colspan="2" class="date_header">Sunday</td></tr>';

		$r .= '<tr style="border-bottom: solid 1px; border-left: solid 1px; border-right: solid 1px; text-align: center;"><td></td>';

		for ($i=0; $i<7; $i++) {
			$r .= '<td style="border-left: solid 1px;">AM</td><td>PM</td>';
		}

		$r .= '</tr>';
		$weekstart->sub($week);
		
		do {
			$weekstart->add($week);
			$w++;
			$to_be_flushed = false;
			$key = $weekstart->getTimestamp();
			if (!isset($this->weekly[$key])) {
				continue;
			}
			$locked = $this->weeks[$key];
			$shifts = $this->weekly[$key];
			if (!isset($shifts[$nickname])) {
				continue;
			}
			$shift = $shifts[$nickname];

			$date = clone($weekstart);
			$final = clone($date);
			$final->add($d6);

			$r .= '<tr style="text-align: center; border-left: solid 1px; border-right: solid 1px; border-bottom: solid 1px; ">';
			$r .= '<td>';
			if ($locked) {
				$src = CROWBANK_ABSPATH . 'css/lock_blue.png';
				$r .= '<img src="' . $src . '">';
			}
			$r .= '<a href="../weekly-rota/?weekstart=' . $date->format('Y-m-d') . '">' . $date->format('d/m') . ' - ' . $final->format('d/m') . '</a></td>';
			for ($i=0; $i<7; $i++) {
				$am = $shift->days[$i]['am'];
				$pm = $shift->days[$i]['pm'];
				$old_am = $am;
				$old_pm = $pm;
				$index = ($am == 'X') ? $rank : $am;
				$am_class = '';
				$pm_class = '';

				$r .= '<td style="border-left: solid 1px;" class="' . $am_class . '">';
				if ($am <> 'X' or $pm <> 'X') {
					if ($am == 'X' or $locked) {
						$r .= $am;
					} else {
						$name = 's' . $date->format('Ymd') . '_' . $i . '_am';
						if ($post) {
							$am = in_array($name, $overrides) ? '-' : 'U';
							if ($am != $old_am) {
								$shifts->days[$i]['am'] = $am;
								$to_be_flushed = true;
							}
						}
						$r .= '<input class="tgl tgl-flat" id="' . $name . '" name="' . $name . '" type="checkbox"' . ($am == 'U' ? '' : ' checked') . '>';
						$r .= '<label class="tgl-btn" data-tg-off="U" data-tg-on="" for="' . $name . '"></label>';
					}
					$r .= '</td><td class="' . $pm_class . '">';
					if ($pm == 'X' or $locked) {
						$r .= $pm;
					} else {
						$name = 's' . $date->format('Ymd') . '_' . $i . '_pm';
						if ($post) {
							$pm = in_array($name, $overrides) ? '-' : 'U';
							if ($pm != $old_pm) {
								$shifts->days[$i]['pm'] = $pm;
								$to_be_flushed = true;
							}
						}
						$r .= '<input class="tgl tgl-flat" id="' . $name . '" name="' . $name . '" type="checkbox"' . ($pm == 'U' ? '' : ' checked') . '>';
						$r .= '<label class="tgl-btn" data-tg-off="U" data-tg-on="" for="' . $name . '"></label>';
					}
					$r .= '</td>';
				} else {
					$r .= $am . '</td><td class="' . $pm_class . '">';
					$r .= $pm . '</td>';
/*					$date->add($day); */
				}
				$shift->update($i, $am, $pm);
			}
			$r .= '<tr style="border-left, border-right, border-bottom: solid 1px; ">';
			$r .= '</tr>';
			if ($to_be_flushed)
				$shift->flush();
		} while ($w < $weeks);


		$r .= '</table></div>';

		return $r;
	}

	public function display_week($weekstart) {
		global $petadmin;

		$this->load();
		
		$day = new DateInterval('P1D');
		$modified = $this->modified_times[$weekstart->getTimestamp()];

		$inventories = array();
		$date = clone $weekstart;
		for ($i=0; $i<7; $i++) {
			$inventories[$i] = $petadmin->inventory->get($date);
			if (!$inventories[$i]) {
				petadmin_log('No inventory for ' . $date->format('d/m/Y'), 3);
			}
			$date->add($day);
		}
		
		$key = $weekstart->getTimestamp();
		
		if (!array_key_exists($key, $this->weekly)) {
			return "Could not find shifts for " . $weekstart->format('d/m/Y') . "<br>";
		}
		
		$shifts = $this->weekly[$key];
		
		$r = '<div style="overflow-x:auto;"><table class="table timesheet_table">';
		$r .= '<tr>';
		$r .= echo_cell('', '');
		for($i=0; $i<7; $i++) {
			$r .= echo_cell('Dogs', 'dogs_header');
			$r .= echo_cell('Cats', 'cats_header');
		}
		$r .=  '</tr>';
		
		$r .=  '<tr>';
		$r .= echo_cell('', 'topleft_header');

		for($i=0; $i<7; $i++) {
			$inv = $inventories[$i];
			if ($inv) {
				$dogs = $inv->occupied('morning', 'Dog');
				$cats = $inv->occupied('morning', 'Cat');
			} else {
				$dogs = 0;
				$cats = 0;
			}
			$r .= echo_cell($dogs, 'top1_header');
			$r .= echo_cell($cats, 'top2_header');
		}
		$r .= '</tr>';
		
		$r .=  '<tr>';
		$r .= echo_cell('', 'bottomleft_header');

		for($i=0; $i<7; $i++) {
			$inv = $inventories[$i];
			if ($inv) {
				$dogs_in = $inv->pet_inout('in', '', 'Dog');
				$dogs_out = $inv->pet_inout('out', '', 'Dog');
			} else {
				$dogs_in = 0;
				$dogs_out = 0;
			}

			$turnover = "$dogs_in / $dogs_out";
			$r .= '<td colspan="2" class="bottom_header">' . $turnover . '</td>';
		}
		$r .=  '</tr>';
		
		$r .=  '<tr>';
		$r .= echo_cell('', 'topleft_header');
		for($i=0; $i<7; $i++) {
			if (!$inventories[$i]->date) {
				petadmin_log('no date for inventories[' . $i . ']');
			} else {
				$r .=  '<td colspan="2" class="date_header"><a href="daily?date=' . $inventories[$i]->date->format('Y-m-d'). '">' . $inventories[$i]->date->format('d/m/Y') . '</a></td>';
			}
		}
		$r .=  '</tr>';
		
		$r .=  '<tr>';
		$r .= echo_cell('', 'centerleft_header');
		for($i=0; $i<7; $i++) {
			if (!$inventories[$i]->date) {
				petadmin_log('no date for inventories[' . $i . ']');
			} else {
				$r .=  '<td colspan="2" class="day_header">' . $inventories[$i]->date->format('l') . '</td>';
			}
		}
		$r .=  '</tr>';
		
		$r .=  '<tr>';
		$r .= echo_cell('', 'bottomleft_header');
		for($i=0; $i<7; $i++) {
			$r .= echo_cell('AM', 'am_header');
			$r .= echo_cell('PM', 'pm_header');
		}
		$r .=  '</tr>';
		
		$first = TRUE;
		$shifts = $this->weekly[$weekstart->getTimestamp()];
		
		uasort($shifts, function($a, $b) { return $a->employee->order - $b->employee->order; });
				
		foreach($shifts as $nickname => $shift) {
			$r .=  '<tr>';

			$r .= echo_cell('<a href="../employee/?emp=' . $nickname . '">' . $nickname . '</a>', 'name_header');

			$employee = $shift->employee;
			$rank = $employee->rank;
			for ($i = 0; $i < 7; $i++) {
				$am = $shift->days[$i]['am'];
				$pm = $shift->days[$i]['pm'];
				$index = ($am == 'X') ? $rank : $am;
				$am_class = ($am == '' ? '' : self::RANKCLASS[$am == 'X' ? $rank : $am]);
				$pm_class = ($pm == '' ? '' : self::RANKCLASS[$pm == 'X' ? $rank : $pm]);
				
				$r .= echo_cell($am, $first ? 'first_am_cell' : 'am_cell', $am_class);
				$r .= echo_cell($pm, $first ? 'first_pm_cell' : 'pm_cell', $pm_class);
			}
			
			$r .=  '</tr>';
			$first = FALSE;
		}
		$r .=  '</table></div>';
		$r .=  'Last modified ' . $modified->format('d/m/Y H:m:s') . '<br>';

		return $r;
	}
	
	
	public function __construct() {
	}
	
	public function owner_holiday( $date ) {
			
		
	}
	
	public function load($force = FALSE) {
		global $petadmin_db;
		
		$sql = 'Select ts_weekstart, ts_emp_nickname, ts_status, ts_published_time from my_timesheet';
		
		if ($this->isLoaded and ! $force) {
			return;
		}
		
		if ($this->isLoaded) {
			$this->weekly = array();
			$this->by_employee = array();
			$this->modified_times = array();
			$this->weeks = array();
			$this->count = 0;
		}
		
		petadmin_log('Loading timesheets');
		
		$result = $petadmin_db->execute($sql);

		foreach($result as $row) {
			$shift = new Shift($row);
			$this->count++;
			$shift_start = $shift->week_start->getTimestamp();
			
			if (!array_key_exists($shift_start, $this->weekly)) {
				$this->weekly[$shift_start] = array();
				$this->modified_times[$shift_start] = $shift->published_time;
			}
			if (!array_key_exists($shift->employee->nickname, $this->by_employee)) {
				$this->by_employee[$shift->employee->nickname] = array();
			}
			$this->weekly[$shift_start][$shift->employee->nickname] = $shift;
			if ($this->modified_times[$shift_start] < $shift->published_time) {
				$this->modified_times[$shift_start] = $shift->published_time;
			}
			$this->by_employee[$shift->employee->nickname][$shift_start] = $shift;
			if (!isset($this->weeks[$shift_start])) {
				$this->weeks[$shift_start] = false;
			}
			if ($shift->days[0]['am'] == 'L') {
				$this->weeks[$shift_start] = true;	
			}
		}
		
		$this->isLoaded = TRUE;
	}
}