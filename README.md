# wechatAPI

----------


# 目录结构说明： #

- obj (该文件夹里面包含一个基础类文件，不用关注)
- v.php （初始验证接口文件，只使用一次。）
- index.php（接口文件）
- config.txt（配置文件，注意不要修改。）

# 使用方法： #

第一步：把该项目拷贝到服务器的根目录

第二步：登陆 微信公众号后台，设置开发接口，将index.php开头处的代码。

	// include("v.php");
	// exit;

修改为：

	include("v.php");
	exit;

验证服务器通过之后，再将代码修改回来。

	// include("v.php");
	// exit;

第三步：配置此处参数。

	define('APPID', "xxx");
	define('APPSECRET', "xxx");

配置完毕，可以使用了。

主核心代码解释：

下面代码不要修改，仅为基础配置代码。

	// 获取TOKEN
	class access_token extends base_class {

		public function __construct(){
		}
	
		public function get_access_token(){
			$this->url_query(
				'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.
				APPID.
				'&secret='.APPSECRET);
	
			$this->creat_time = time();
			$this->access_token = $this->get_thisobj_value("access_token","");
			$this->expires_in = $this->get_thisobj_value("expires_in",7000);
			$this->errcode = $this->get_thisobj_value("errcode",0);
			$this->errmsg = $this->get_thisobj_value("errmsg","");
		}
	
		public function Refresh_access_token(){
			$this->read_config();
	
			if((time() - $this->creat_time) > $this->expires_in){
				$this->get_access_token();
				file_put_contents($this->file,
					$this->creat_time.'	'.$this->access_token,
					LOCK_EX);
				$this->creat_time = $this->creat_time;
				$this->access_token = $this->access_token;
			}
		}
	}



下面代码不要修改，仅为基础配置代码。

	// 获取微信服务器地址
	class _get_weixin_ip extends access_token {
		public $ip_array = array();
	
		public function get_ip(){
			$this->url_query('https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$this->access_token);
			$this->ip_array = $this->get_thisobj_value("ip_list",array());
		}
	}


下面这块代码为接收微信发来的关键词消息处理的代码。

	// 回复消息
	class re_msg extends access_token {
		public $db = "";
		public $result = "";
		public $fromUsername;
		public $toUsername;
		public $CreateTime;
		public $MsgType;
		public $Content;
		public $MsgId;
		public $time;
		public $Event;
		public $Location_X = 0;
		public $Location_Y = 0;
		public $Scale = 0;
		public $Label = "";
		
		public function __construct() {
			$this->db = new db_class();
		}
	
		public function filter_keyword($Str){
			return mysql_real_escape_string(preg_replace("![\][xX]([A-Fa-f0-9]{1,3})!", "",substr($Str,0,1000)));
		}
	
		public function get_msg($postStr){
			//用SimpleXML解析POST过来的XML数据
			$postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
			$this->fromUsername = $postObj->FromUserName; //获取用户（OpenID）
			$this->toUsername = $postObj->ToUserName; //获取公众号
			$this->CreateTime = $postObj->CreateTime; //创建时间
			$this->MsgType = $postObj->MsgType; //消息格式
			$this->time = time(); //获取当前时间戳
	
			// 下面这段代码为接收到的消息处理代码。
			switch($this->MsgType){
				case "text":
					$this->Content = $this->filter_keyword(trim($postObj->Content)); //获取消息内容
					$this->MsgId = trim($postObj->MsgId); //获取消息ID
					break;
				case "location":
					$this->Location_X = $postObj->Location_X;
					$this->Location_Y = $postObj->Location_Y;
					$this->Scale = $postObj->Scale;
					$this->Label = $postObj->Label;
					break;
				case "event":
					$this->Event = $postObj->Event;
					if($this->Event == "CLICK"){
						$this->Content = $postObj->EventKey;
					}
					break;
				default:
				
			}
			// $this->result = str_replace(">",")",str_replace("<","(",$GLOBALS["HTTP_RAW_POST_DATA"]));
		}
	
		public function retext($contentStr){
			//返回消息模板
			$textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[%s]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>0</FuncFlag>
			</xml>";
			$msgType = "text"; //消息类型
			//格式化消息模板
			$resultStr = sprintf($textTpl,
			$this->fromUsername,
			$this->toUsername,
			$this->time,
			$msgType,
			$contentStr);
			return $resultStr; //输出结果
		}
	
		public function action(){
			//---------- 返 回 数 据 ---------- //
			switch($this->MsgType){
				case "text":
					echo $this->retext($this->Content);
					break;
				case "image":
					echo $this->retext('暂时无法分析图片');
					break;
				case "location":
					echo $this->retext('已经收到你的地址：'.$this->Label.$this->Location_X.$this->Location_Y);
					break;
				case "event":
					if($this->Event == "subscribe"){
						echo $this->retext('hi');
					}
					else if($this->Event == "unsubscribe"){
						echo $this->retext('欢迎下次光临');
					}
					else if($this->Event == "CLICK"){
						echo $this->retext($this->Content);
					}
					else{
						echo $this->retext('hi');
					}
					break;
				default:
					echo $this->retext('未知消息');
			}
			
		}
	}


初始化类，开始执行接收微信发来的消息。并进行处理。

	$config_array = new re_msg();
	$config_array->get_msg($GLOBALS["HTTP_RAW_POST_DATA"]);
	$config_array->action();