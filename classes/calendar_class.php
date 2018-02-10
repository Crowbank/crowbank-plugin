<?php
class Calendar {
	
	/**
	 * Constructor
	 */
	public function __construct(){
		$this->naviHref = htmlentities($_SERVER['PHP_SELF']);
	}
	
	/********************* PROPERTY ********************/
	private $dayLabels = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
	private $currentDay = 0;
	private $currentDate = null;
	private $daysInMonth = 0;
	private $naviHref = null;
	
	/********************* PUBLIC **********************/
	
	public $currentYear = 0;
	public $currentMonth = 0;
	/* Optional function which takes a date and returns a string representing the class for the cell for that date */
	public $classFunc = null;
	
	/**
	 * print out the calendar
	 */
	
	public function show() {
		$year = $this->currentYear;
		$month = $this->currentMonth;
		
		$this->daysInMonth = $this->_daysInMonth();
		
		$content='<div id="calendar">'.
				'<div class="box">'.
				$this->_createNavi().
				'</div>'.
				'<div class="box-content">'.
				'<ul class="label">'.$this->_createLabels().'</ul>';
		$content.='<div class="clear"></div>';
		$content.='<ul class="dates">';
				
		$weeksInMonth = $this->_weeksInMonth();
		// Create weeks in a month
		for( $i = 0; $i < $weeksInMonth; $i++ ){
			
			//Create days in a week
			for($j = 1; $j <= 7; $j++) {
				$content .= $this->_showDay($i * 7 + $j);
			}
		}
		
		$content .= '</ul>';
		$content .= '<div class="clear"></div>';
		$content .= '</div>';
		$content .= '</div>';

		return $content;
	}
	
	/********************* PRIVATE **********************/
	/**
	 * create the li element for ul
	 */
	
	private function _showDay($cellNumber){	
		if ($this->currentDay == 0) {
			$firstDayOfTheWeek = date('N', strtotime($this->currentYear . '-' . $this->currentMonth . '-01'));
			if(intval($cellNumber) == intval($firstDayOfTheWeek)) {
				$this->currentDay = 1;
			}
		}
		
		if ( ($this->currentDay != 0) && ($this->currentDay <= $this->daysInMonth)) {
			$this->currentDate = date('Y-m-d', strtotime($this->currentYear . '-' . $this->currentMonth . '-' . ($this->currentDay)));
			$cellContent = $this->currentDay;
			$this->currentDay++;
		} else {
			$this->currentDate = null;
			$cellContent = null;
		}
		
		return '<li id="li-' . $this->currentDate . '" class="' . ($cellNumber % 7 == 1 ? ' start ':($cellNumber % 7 == 0 ? ' end ':' ')) .
		($cellContent == null ? 'mask' : '') . ($this->classFunc == null ? '' : ' ' . $this->classFunc($this->currentDate) . ' ') . '">' . $cellContent . '</li>';
	}
	
	/**
	 * create navigation
	 */
	private function _createNavi(){
		$nextMonth = $this->currentMonth == 12 ? 1 : intval($this->currentMonth) + 1;
		$nextYear = $this->currentMonth == 12 ? intval($this->currentYear) + 1 : $this->currentYear;
		$preMonth = $this->currentMonth == 1 ? 12 : intval($this->currentMonth) - 1;
		$preYear = $this->currentMonth == 1 ? intval($this->currentYear) - 1 : $this->currentYear;
		return
		'<div class="header">'.
		'<a class="prev" href="'.$this->naviHref.'?month='.sprintf('%02d',$preMonth).'&year='.$preYear.'">Prev</a>'.
		'<span class="title">'.date('Y M',strtotime($this->currentYear.'-'.$this->currentMonth.'-1')).'</span>'.
		'<a class="next" href="'.$this->naviHref.'?month='.sprintf("%02d", $nextMonth).'&year='.$nextYear.'">Next</a>'.
		'</div>';
	}
	
	/**
	 * create calendar week labels
	 */
	private function _createLabels(){
		$content = '';
		foreach ($this->dayLabels as $index=>$label) {
			$content.='<li class="'.($label==6?'end title':'start title').' title">'.$label.'</li>';
		}
		
		return $content;
	}
	
	/**
	 * calculate number of weeks in a particular month
	 */
	private function _weeksInMonth(){
		
		// find number of days in this month
		$daysInMonths = $this->_daysInMonth($this->currentMonth, $this->currentYear);
		$numOfweeks = ($daysInMonths % 7 == 0 ? 0 : 1) + intval($daysInMonths / 7);
		$monthEndingDay= date('N', strtotime($year . '-' . $month . '-' . $daysInMonths));
		$monthStartDay = date('N', strtotime($year . '-' . $month . '-01'));
		
		if($monthEndingDay<$monthStartDay){
			$numOfweeks++;
		}
		return $numOfweeks;
	}
	
	/**
	 * calculate number of days in a particular month
	 */
	private function _daysInMonth() {
		return date('t',strtotime($this->currentYear . '-' . $this->currentMonth . '-01'));
	}
}
