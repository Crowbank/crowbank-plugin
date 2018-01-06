<?php
require_once "config.php";

class Weather {
	public $retrieval_ts;
	private $raw_data;
	private $url;
	public $data = array();
	
	public function __construct() {
		$this->retrieval_ts = 0;
		$this->url = "http://api.openweathermap.org/data/2.5/forecast?id=" . CITY_CODE . "&mode=json&APPID=" . WEATHER_KEY;
	}
	
	public function refresh($force = FALSE) {
		$now = new DateTime();
		$now_ts = $now->getTimestamp();
		
		if (($now_ts - $this->retrieval_ts > 600) or $force) {
			$json = file_get_contents($this->url);
			$data = json_decode($json, true);
			$this->retrieval_ts = $now_ts;
			$this->raw_data = $data['list'];
			$this->data = array();
			
			foreach ($this->raw_data as $w) {
				$now->setTimestamp($w['dt']);
				$date = new DateTime($now->format('Y-m-d'));
				$date_ts = $date->getTimestamp();
				$time = new DateTime($now->format('H:i:s'));
				
				if (!array_key_exists($date_ts, $this->data)) {
					$this->data[$date_ts] = array();
				}
				$this->data[$date_ts][] = array('date' => $date, 'time' => $time, 'temp' => $w['main']['temp'] - 273.15,
						'headline' => $w['weather'][0]['main'], 'clouds' => $w['clouds']['all'],
						'rain' => (array_key_Exists('rain', $w) and $w['rain']) ? $w['rain']['3h'] / 3.0 : 0.0, 'icon_url' => 'http://openweathermap.org/img/w/' . $w['weather'][0]['icon'] . '.png',
						'description' => $w['weather'][0]['description']);
			}
		}
	}
	
	public function weather_table($date) {
		$this->refresh();
		
		if (!array_key_exists($date->getTimestamp(), $this->data)) {
			return "No Weather Data Available for " . $date->format('d/m/Y') . "<br>";
		}
		
		$ts = $date->getTimestamp();
		$data = $this->data[$ts];
		
		$r = '<table class="table"><tr><td>Time</td>';		
		foreach ($data as $w) {
			$r .= '<td>' . $w['time']->format('H:i') . '</td>';
		}
		$r .= '</tr><tr><td></td>';
		
		foreach ($this->data[$date->getTimestamp()] as $w) {
			$r .= '<td><img src="' . $w['icon_url'] . '" alt="' . $w['description'] . '"></td>';
		}
		$r .= '</tr><tr><td>Temperature</td>';
		
		foreach ($this->data[$date->getTimestamp()] as $w) {
			$r .= '<td>' . number_format($w['temp'], 1) . '</td>';
		}
		$r .= '</tr><tr><td>Rainfall</td>';
		
		foreach ($this->data[$date->getTimestamp()] as $w) {
			$r .= '<td>' . number_format($w['rain'], 1) . '</td>';
		}
		$r .= '</tr><tr><td>Cloudiness</td>';
		
		foreach ($this->data[$date->getTimestamp()] as $w) {
			$r .= '<td>' . $w['clouds'] . '</td>';
		}
		
		$r .= '</tr></table>';
	}
	
	public function weather_table_h($date) {
		$this->refresh();

		if (!array_key_exists($date->getTimestamp(), $this->data)) {
			echo "No Weather Data Available for " . $date->format('d/m/Y') . "<br>";
			return;
		}
				
		echo '<table class="table">
<thead><th>Time</th><th>Temp</th><th>Main</th><th>Description</th><th>Clouds</th><th>Rain</th></thead>
<tbody>';
		
		foreach ($this->data[$date->getTimestamp()] as $w) {
			echo "<tr><td>" . $w['time']->format('H:i') . "</td><td>" . number_format($w['temp'], 1) . "</td><td>";
			echo $w['headline'] . "</td><td>";
			echo '<img src="' . $w['icon_url'] . '" alt="' . $w['description'] . '"></td><td>';
			echo $w['clouds'] . "</td><td>" . number_format($w['rain'], 1) . "<br>";
			echo "</td></tr>";
		}
		echo '</tbody>
</table>';
	}
}
