<?php
class base_class {
	public $file = "config.txt";
	public $access_token = "abc";
	public $expires_in = 7000;
	public $creat_time = 0;
	public $obj;
	public $errcode = 0;
	public $errmsg = "ok";

	public function __construct (){
	}
	
	public function __destruct() {
	}

	public function get_thisobj_value($objkey,$default = ""){
		if(array_key_exists($objkey,$this->obj)){
			return $this->obj[$objkey];
		}
		return $default;
	}

	public function url_query($query_var){
		$ts = file_get_contents($query_var);
		$this->obj = json_decode($ts,true);
		$this->errcode = $this->get_thisobj_value("errcode",0);
		$this->errmsg = $this->get_thisobj_value("errmsg","");
	}

	public function read_config(){
		if(!file_exists($this->file)){
			file_put_contents($this->file,
				$this->creat_time.'	'.$this->access_token,
				LOCK_EX);
		}
		$temp_array = explode("\t",file_get_contents($this->file));
		$this->creat_time = $temp_array[0];
		$this->access_token = $temp_array[1];
	}
}