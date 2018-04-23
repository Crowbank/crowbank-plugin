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
	
	public function send() {
		$meta = array();
		
		foreach ($this->meta as $kind=>$value) {
			$meta[$kind] = $value;
		}
		
		$msg = array('msg_id' => $this->id,
				'msg_src' => $_SERVER['HTTP_HOST'],
				'msg_type' => $this->type,
				'msg_status' => $this->status,
				'msg_meta' => $meta
		);
		
		$msg_str = json_encode($msg);
		
		$ch = curl_init('http://http://37.19.30.17:81/local_api/message.php');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $msg_str);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($msg_str))
				);
		
		$result = curl_exec($ch);
		
		$log_msg = sprintf('Sent msg #%d, type %s, result: %s', $this->id, $this->type, $result);
		petadmin_log($log_msg);
		
		return $result;
	}
}
