<?php
class Message {
	public $id;
	public $type;
	public $status;
	public $meta = array();

	public function __construct($type, $dict = null) {
		$this->type = $type;
		$this->status = 'temp';
		
		if ($dict) {
			foreach($dict as $key=>$value) {
				$this->meta[$key] = $value;
			}
		}
	}

	public function __isset($name) {
		return isset($this->meta[$name]);
	}

	public function __get($name){
		if (isset($this->meta[$name])) 
			return $this->meta[$name];

		return null;
	}

	public function __set($name, $value){
		$this->meta[$name] = $value;
	}

	public function flush() {
		global $wpdb;
		
		$server = $_SERVER['HTTP_HOST'];

		$wpdb->insert('crwbnk_messages', ['msg_type' => $this->type, 'msg_status' => $this->status, 'msg_source' => $server]);
		$this->id = $wpdb->insert_id;
		
		foreach ($this->meta as $kind=>$value) {
			$wpdb->insert('crwbnk_msgmeta', ['msg_id' => $this->id, 'meta_kind' => $kind, 'meta_value' => $value]);
		}
		
		$this->status = 'open';
		$wpdb->update('crwbnk_messages', ['msg_status' => $this->status], ['msg_id' => $this->id]);
	}
}
