<?php
/**
 * 移动点对点CMPP协议接口
 * PHP version 5
 * @category    Lib
 * @author      lcq
 * @version     v1
 */

/**
 * 命令或响应类型 Command_Id定义
 */
define('CMPP_CONNECT', 0x00000001); // 请求连接  
define('CMPP_CONNECT_RESP', 0x80000001); // 请求连接应答  
define('CMPP_TERMINATE', 0x00000002); // 终止连接  
define('CMPP_TERMINATE_RESP', 0x80000002); // 终止连接应答  
define('CMPP_SUBMIT', 0x00000004); // 提交短信  
define('CMPP_SUBMIT_RESP', 0x80000004); // 提交短信应答  
define('CMPP_DELIVER', 0x00000005); // 短信下发  
define('CMPP_DELIVER_RESP', 0x80000005); // 下发短信应答  
define('CMPP_QUERY', 0x00000006); // 发送短信状态查询  
define('CMPP_QUERY_RESP', 0x80000006); // 发送短信状态查询应答  
define('CMPP_CANCEL', 0x00000007); // 删除短信  
define('CMPP_CANCEL_RESP', 0x80000007); // 删除短信应答  
define('CMPP_ACTIVE_TEST', 0x00000008); // 激活测试  
define('CMPP_ACTIVE_TEST_RESP', 0x80000008); // 激活测试应答  
define('CMPP_FWD', 0x00000009); // 消息前转  
define('CMPP_FWD_RESP', 0x80000009); // 消息前转应答  
define('CMPP_MT_ROUTE', 0x00000010); // MT路由请求  
define('CMPP_MT_ROUTE_RESP', 0x80000010); // MT路由请求应答  
define('CMPP_MO_ROUTE', 0x00000011); // MO路由请求  
define('CMPP_MO_ROUTE_RESP', 0x80000011); // MO路由请求应答  
define('CMPP_GET_ROUTE', 0x00000012); // 获取路由请求  
define('CMPP_GET_ROUTE_RESP', 0x80000012); // 获取路由请求应答  
define('CMPP_MT_ROUTE_UPDATE', 0x00000013); // MT路由更新  
define('CMPP_MT_ROUTE_UPDATE_RESP', 0x80000013); // MT路由更新应答  
define('CMPP_MO_ROUTE_UPDATE', 0x00000014); // MO路由更新  
define('CMPP_MO_ROUTE_UPDATE_RESP', 0x80000014); // MO路由更新应答  
define('CMPP_PUSH_MT_ROUTE_UPDATE', 0x00000015); // MT路由更新  
define('CMPP_PUSH_MT_ROUTE_UPDATE_RESP', 0x80000015); // MT路由更新应答  
define('CMPP_PUSH_MO_ROUTE_UPDATE', 0x00000016); // MO路由更新  
define('CMPP_PUSH_MO_ROUTE_UPDATE_RESP', 0x80000016); // MO路由更新应答  

class Cmpp {
	/**
	 * Socket连接
	 * @var resource
	 */
	protected $_socket = null;
	/**
	 * 消息流水号
	 * 顺序累加,步长为1,循环使用（一对请求和应答消息的流水号必须相同）
	 * @var int
	 */
	protected $_sequence_number  = 1;
	protected $_message_sequence = 1;
	/**
	 * 发送的数据
	 * 已经排除access_token值
	 * @var mixed
	 */
	protected $_sendData = null;
	/**
	 * 收到的数据
	 * @var string
	 */
	protected $_responseData = null;
	/**
	 * 收到数据的数组格式
	 * @var array
	 */
	protected $_ArrayModeData = array();


	public function __construct() {
		$this->_socket           = null;
		$this->_sequence_number  = 1;
		$this->_message_sequence = rand(1, 255);
	}


	//云之讯  CMPP协议   发送短信验证码
	public function yunsms($phone, $content) {
		$yunsms = array(
			'account'  => 'b00mq8',
			'password' => '3de248bd',
			'port'     => '7890',
			'host'     => '123.59.181.20'
		);

		$host     = $yunsms['host'];
		$port     = $yunsms['port'];
		$username = $yunsms['account'];
		$password = $yunsms['password'];
		//云之讯 验证码
		$start = $this->Start($host, $port, $username, $password);

		if ($start) {
			$send = $this->Send($phone, $content);

			if ($send['Result'] == 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}



	/*
	 This method initiates an SMPP session.
	It is to be called BEFORE using the Send() method.
	Parameters:
	$host       : SMPP ip to connect to.
	$port       : port # to connect to.
	$username   : SMPP system ID
	$password   : SMPP passord.
	$system_type    : SMPP System type
	Returns:
	true if successful, otherwise false
	Example:
	$smpp->Start("smpp.chimit.nl", 2345, "chimit", "my_password", "client01");
	*/
	public function Start($host, $port, $username, $password) {
		$this->_socket = fsockopen($host, $port, $errno, $errstr, 20);

		// todo: sanity check on input parameters
		if (!$this->_socket) {
			return false;
		}
		socket_set_timeout($this->_socket, 1200);
		$status = $this->_CMPP_CONNECT($username, $password);

		if ($status == 0) {
			return 1;
		}

		return $this->_socket;
	}


	/**
	 * 发送短消息
	 * Example:
	 * $cmpp->Send("31649072766", "This is an SMPP Test message.");
	 * $cmpp->Send("31648072766", "صباحالخير", true, 'HTML-ENTITIES')
	 * @param mixed $to 手机号码，传单个电话号码或者数组
	 * @param stirng $text 短消息内容，如果超长会自动切分短消息变成多条发送
	 * @param bool $unicode 是否UCS2编码方式发送，默认是，注意中文必须以UCS2编码发送
	 * @param string $text_encoding 短消息内容的编码，默认UTF-8，可选值参考函数：mb_convert_encoding()
	 * @return boolean 发送成功返回true，失败返回false
	 */
	public function Send($to, $text, $unicode = true, $text_encoding = 'UTF-8') {
		if (is_array($to)) {
			$Dest_terminal_Id = '';
			$DestUsr_tl       = count($to);
			foreach ($to as $dest) {
				$Dest_terminal_Id .= pack('a21', $dest);
			}
		} else {
			$Dest_terminal_Id = $to;
			$DestUsr_tl       = 1;
		}

		$Msg_Content = '';

		$data              = array();
		$data['Msg_Id']    = 0;
		$data['Pk_total']  = 1;
		$data['Pk_number'] = 1;

		// 是否要求返回状态确认报告
		// 0：不需要
		// 1：需要
		// 2：产生SMC话单（该类型短信仅供网关计费使用，不发送给目的终端)
		$data['Registered_Delivery'] = 1;

		// 信息级别
		// 0：普通优先级（缺省值）
		// 1：高优先级
		// >1：保留
		$data['Msg_level'] = 0;

		// 业务类型
		$data['Service_Id'] = rand(100, 999) . chr(rand(65, 90)) . '!#' . rand(100, 999) . chr(rand(65, 90));

		//计费用户类型字段
		//0：对目的终端MSISDN计费；
		//1：对源终端MSISDN计费；
		//2：对SP计费;
		//3：表示本字段无效，对谁计费参见Fee_terminal_Id字段
		$data['Fee_UserType'] = 0;

		// 被计费用户的号码（如本字节填空，则表示本字段无效，对谁计费参见Fee_UserType 字段，本字段与Fee_UserType字段互斥）
		$data['Fee_terminal_Id'] = '';

		// GSM协议类型
		$data['TP_pId']  = 0;
		$data['TP_udhi'] = 0;

		// 信息格式
		// 0：ASCII串
		// 3：短信写卡操作
		// 4：二进制信息
		// 8：UCS2编码
		// 15：含GB汉字  。。。。。。
		$data['Msg_Fmt'] = 0;

		// 信息内容来源(SP_Id)
		$data['Msg_src'] = '';

		// 资费类别
		//01：对“计费用户号码”免费
		//02：对“计费用户号码”按条计信息费
		//03：对“计费用户号码”按包月收取信息费
		//04：对“计费用户号码”的信息费封顶
		//05：对“计费用户号码”的收费是由SP实现
		$data['FeeType'] = '03';

		// 资费代码（以分为单位）
		$data['FeeCode'] = '';

		// 存活有效期，格式遵循SMPP3.3 协议
		$data['ValId_Time'] = '';

		// 定时发送时间，格式遵循SMPP3.3 协议
		$data['At_Time'] = '';

		// 源号码
		//SP 的服务代码或前缀为服务代码的长号码,
		//网关将该号码完整的填到SMPP协议Submit_SM 消息相应的source_addr字段，
		//该号码最终在用户手机上显示为短消息的主叫号码
		$data['Src_Id'] = '';

		// 接收信息的用户数量(小于100 个用户)
		$data['DestUsr_tl'] = $DestUsr_tl;

		if ($data['DestUsr_tl'] > 100) {
			$msgs = $data . 'Over max DestUsr_tl<100';

			return $msgs;
		}

		// 接收短信的MSISDN 号码
		$data['Dest_terminal_Id'] = $Dest_terminal_Id;

		// 信息长度(Msg_Fmt 值为0 时：<160 个字节；其它<=140 个字节)
		$data['Msg_Length'] = strlen($Msg_Content);


		// 信息内容
		$data['Msg_Content'] = $Msg_Content;

		$data['Reserve'] = '';

		/**
		 * 消息转码分隔
		 */
		if ($unicode) {
			if (strcasecmp("UCS-2BE", $text_encoding)) {
				$unicode_text = mb_convert_encoding($text, "UCS-2BE", $text_encoding); /* UCS-2BE */
			} else {
				$unicode_text = $text;
			}
			$multi_texts = $this->_split_message_unicode($unicode_text);
			unset($unicode_text);

			$data['Msg_Fmt'] = 8;
		} else {
			$multi_texts = $this->_split_message($text);
		}

		if (count($multi_texts) > 1) {
			$data['TP_udhi']  = 1;
			$data['Pk_total'] = count($multi_texts);
		}

		$result = true;

		reset($multi_texts);
		while (list($pos, $part) = each($multi_texts)) {
			$Msg_Content         = $part;
			$data['Pk_number']   = $pos + 1;
			$data['Msg_Content'] = $Msg_Content;
			$data['Msg_Length']  = strlen($Msg_Content);

			$status = $this->_CMPP_SUBMIT($data);
			if (!$status) {
				$result = false;
			}
		}

		return $status;
	}


	/*
	 This method ends a SMPP session.
	Parameters:
	none
	Returns:
	true if successful, otherwise false
	Example: $smpp->End();
	*/
	public function End() {
		if (!$this->_socket) {
			// not connected
			return;
		}
		$status = $this->_CMPP_TERMINATE();
		if (false == $status) {

			return false;
		}
		fclose($this->_socket);
		$this->_socket = null;

		return $status;
	}


	protected function _ExpectPDU($our_sequence_number, $requestd = null) {
		do {

			if (feof($this->_socket)) {
				return false;
			}
			$elength = fread($this->_socket, 4);
			if (empty($elength)) {

				return false;
			}
			extract(unpack("Nlength", $elength));

			$stream = fread($this->_socket, $length - 4);

			extract(unpack("Ncommand_id/Nsequence_number", $stream));
			$command_id &= 0x0fffffff;

			$pdu  = substr($stream, 8);
			$data = null;
			switch ($command_id) {
				case CMPP_CONNECT:

					$data = $this->_CMPP_CONNECT_RESP($pdu, $requestd);
				break;
				case CMPP_SUBMIT:

					$data = $this->_CMPP_SUBMIT_RESP($pdu, $requestd);
				break;
				case CMPP_TERMINATE:

				break;

				default:

				break;
			}
		} while ($sequence_number != $our_sequence_number);

		$this->_responseData = $data;
		$command_status      = false === $data ? false : true;

		return $data;
	}


	protected function _SendPDU($command_id, $pdu, $requestd) {
		$length          = strlen($pdu) + 12;
		$header          = pack("NNN", $length, $command_id, $this->_sequence_number);
		$this->_sendData = $requestd;

		fwrite($this->_socket, $header . $pdu, $length);
		$status                 = $this->_ExpectPDU($this->_sequence_number, $requestd);
		$this->_sequence_number = $this->_sequence_number + 1;

		return $status;
	}


	protected function _CMPP_TERMINATE() {
		$pdu = "";

		return $this->_SendPDU(CMPP_TERMINATE, $pdu);
	}


	protected function _CMPP_CONNECT($Source_Addr, $Shared_Secret, $Version = 0x20) {
		$data = array();

		// 源地址，此处为SP_Id，即SP的企业代码。
		$data['Source_Addr'] = $Source_Addr;
		$Source_Addr_len     = 6;

		$data['Shared_Secret'] = $Shared_Secret;

		$data['Timestamp'] = date('mdHis');

		$data['Version'] = $Version;
		//$data['Version'] = 0x20;

		// 用于鉴别源地址。其值通过单向MD5 hash计算得出，表示如下：
		// AuthenticatorSource= MD5（Source_Addr+9  字节的0 +shared secret+timestamp）
		// Shared secret 由中国移动与源地址实体事先商定，timestamp 格式为：MMDDHHMMSS，即月日时分秒，10位。
		//$AuthenticatorSource_ori = $data['Source_Addr'] . str_pad('', 9, '0') . $data['Shared_Secret'] . $data['Timestamp'];
		$AuthenticatorSource_ori = $data['Source_Addr'] . pack('a9', '') . $data['Shared_Secret'] . $data['Timestamp'];

		$data['AuthenticatorSource'] = md5($AuthenticatorSource_ori, true);
		$AuthenticatorSource_len     = 16;

		$format = "a{$Source_Addr_len}a{$AuthenticatorSource_len}CN";
		$pdu    = pack($format, $data['Source_Addr'], $data['AuthenticatorSource'], $data['Version'], $data['Timestamp']);

		$debug_data                        = $data;
		$debug_data['AuthenticatorSource'] = self::pduord($debug_data['AuthenticatorSource']);

		unset($debug_data);

		return $this->_SendPDU(CMPP_CONNECT, $pdu, $data);
	}


	protected function _CMPP_CONNECT_RESP($pdu, $requestd) {
		$format = "CStatus/a16AuthenticatorISMG/CVersion";
		$data   = unpack($format, $pdu);
		/**
		 * ISMG认证码，用于鉴别ISMG。
		 * 其值通过单向MD5 hash计算得出，表示如下：AuthenticatorISMG =MD5（Status+AuthenticatorSource+shared secret）
		 * ，Shared secret 由中国移动与源地址实体事先商定，AuthenticatorSource为源地址实体发送给ISMG 的对应消息CMPP_Connect中的值。认证出错时，此项为空。
		 */
		$status = intval($data['Status']);
		if (strcasecmp('0', $status)) {
			return $status;
		} elseif (0 == strlen($data['AuthenticatorISMG'])) {
			return $status; //ISMG认证码为空
		}

		$checkAuthenticatorISMG = md5($data['Status'] . $requestd['AuthenticatorSource'] . $requestd['Shared_Secret'], true);
		if (strcmp($checkAuthenticatorISMG, $data['AuthenticatorISMG'])) {
			return $status; //ISMG认证码校验失败
		}

		return $data;
	}


	public static function cmpp_connect_resp_status_error($status) {
		$errors = array(
			0 => '正确',
			1 => '消息结构错',
			2 => '非法源地址',
			3 => '认证错',
			4 => '版本太高',
		);

		if ($status >= 0) {
			if ($status >= 5) {
				return '其他错误';
			} else {
				return $errors[$status];
			}
		} else {
			return '未知错误';
		}
	}


	protected function _CMPP_SUBMIT($data) {
		$Dest_terminal_Id_len = 21 * $data['DestUsr_tl'];
		$Msg_Content_len      = strlen($data['Msg_Content']);

		//$format = "N2CCCCa10CC21CCCa6a2a6a17a17a21Ca{$Dest_terminal_Id_len}Ca{$Msg_Content_len}a8";
		$format = "a8CCCCa10Ca21CCCa6a2a6a17a17a21Ca{$Dest_terminal_Id_len}Ca{$Msg_Content_len}a8";
		$pdu    = pack($format, $data['Msg_Id'], $data['Pk_total'], $data['Pk_number'], $data['Registered_Delivery'], $data['Msg_level'], $data['Service_Id'], $data['Fee_UserType'], $data['Fee_terminal_Id'], $data['TP_pId'], $data['TP_udhi'], $data['Msg_Fmt'], $data['Msg_src'], $data['FeeType'], $data['FeeCode'], $data['ValId_Time'], $data['At_Time'], $data['Src_Id'], $data['DestUsr_tl'], $data['Dest_terminal_Id'], $data['Msg_Length'], $data['Msg_Content'], $data['Reserve']);

		return $this->_SendPDU(CMPP_SUBMIT, $pdu, $data);
	}


	protected function _CMPP_SUBMIT_RESP($pdu, $requestd) {
		$format = "N2Msg_Id/CResult";
		//$format = "C8Msg_Id/CResult";
		$data   = unpack($format, $pdu);
		$result = intval($data['Result']);
		if (strcasecmp('0', $result)) {
			return $result;
		}

		return $data;
	}


	public static function cmpp_submit_resp_result_error($result) {
		$errors = array(
			0 => '正确',
			1 => '消息结构错',
			2 => '命令字错',
			3 => '消息序号重复',
			4 => '消息长度错',
			5 => '资费代码错',
			6 => '超过最大信息长',
			7 => '业务代码错',
			8 => '流量控制错',
		);

		if ($result >= 0) {
			if ($result >= 9) {
				return '其他错误';
			} else {
				return $errors[$result];
			}
		} else {
			return '未知错误';
		}
	}


	protected function _CMPP_QUERY($data) {
		$format = "a8Ca10a8";
		$pdu    = pack($format, $data['Time'], $data['Query_Type'], $data['Query_Code'], $data['Reserve']);

		$status = $this->_SendPDU(CMPP_QUERY, $pdu, $data);

		return $status;
	}


	protected function _CMPP_QUERY_RESP($pdu, $requestd) {
		$format = "a8Time/CQuery_Type/a10Query_Code/NMT_TLMsg/NMT_Tlusr/NMT_Scs/NMT_WT/NMT_FL/NMO_Scs/NMO_WT/NMO_FL";
		$data   = unpack($format, $pdu);

		if (empty($data)) {
			$result = intval($data['Result']);

			return $result;
		}

		return $data;
	}

	/*
	 This method sends out one SMS message.
	Parameters:
	$to : destination address.
	$text   : text of message to send.
	$unicode: Optional. Indicates if sending text in unicode format.
	$text_encoding: Optional. encoding of text
	Returns:
	true if messages sent successfull, otherwise false.
	Example:
	$cmpp->Send("31649072766", "This is an SMPP Test message.");
	$cmpp->Send("31648072766", "صباحالخير", true);
	*/


	/**
	 * PDU数据包转化ASCII数字
	 * @param string $pdu
	 * @return string
	 */
	public static function pduord($pdu) {
		$ord_pdu = '';
		for ($i = 0; $i < strlen($pdu); $i++) {
			$ord_pdu .= ord($pdu[$i]) . ' ';
		}

		if ($ord_pdu) {
			$ord_pdu = substr($ord_pdu, 0, -1);
		}

		return $ord_pdu;
	}


	/**
	 * PDU数据包转为字符串（不可见字符转为ASCII数字）
	 * @param string $pdu
	 * @return string
	 */
	public static function pdustr($pdu) {
		$str_pdu = '';
		for ($i = 0; $i < strlen($pdu); $i++) {
			$n = ord($pdu[$i]);
			if ($n <= 32 || $n >= 127) {
				$str_pdu .= '(' . $n . ')';
			} else {
				$str_pdu .= $pdu[$i];
			}
			$str_pdu .= ' ';
		}

		if ($str_pdu) {
			$str_pdu = substr($str_pdu, 0, -1);
		}

		return $str_pdu;
	}


	protected function _split_message($text) {

		$max_len = 153;
		$res     = array();
		if (strlen($text) <= 160) {

			$res[] = $text;

			return $res;
		}
		$pos          = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(strlen($text) / $max_len);
		$part_no      = 1;
		while ($pos < strlen($text)) {
			$ttext = substr($text, $pos, $max_len);
			$pos   += strlen($ttext);
			$udh   = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
		}

		return $res;
	}


	protected function _split_message_unicode($text) {

		$max_len = 134;
		$res     = array();
		if (mb_strlen($text) <= 140) {

			$res[] = $text;

			return $res;
		}
		$pos          = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(mb_strlen($text) / $max_len);
		$part_no      = 1;
		while ($pos < mb_strlen($text)) {
			$ttext = mb_substr($text, $pos, $max_len);
			$pos   += mb_strlen($ttext);
			$udh   = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
		}

		return $res;
	}
}  