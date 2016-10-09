<?php
	/**
  * GUID generator, which allow to store some data in GUID (code and decode).
  *
  * Create GUID string and store upto 8 bytes of positive numeric data in hexadecimal representation in it.
  *
  * @author		Oleg Zorin <zorinoa@yandex.ru>
	* @link			http://oleg.zorin.ru Oleg Zorin home page
	*
	* @license https://opensource.org/licenses/GPL-3.0 GNU Public License, version 3
	*
	* @package	OZ\
  * @version	1.0
	*/
	
	namespace OZ;
	
	abstract class GUID {
		/** @var string $_key		Secreat key */
		private static $_key = '';
		/**
		*	@var array $_schema		Data schema, contains pairs 'filed => length':
		*	filed - any valid associated array key.
		* lenght - int value, length of field in HEX digits:
		*		1 - field could contain one HEX digit (0-15 DEC values);
		*		2 - field could contain two HEX digit (0-255 DEC values);
		*		...
		*		Total length of all fields should be 16 digits (8 bytes).
		*/
		private static $_schema = array(
			'type' => 2,
			'field1' => 7,
			'field2' => 7,
		);
		/** @var bool $_init		Initialization flag */
		private static $_init = false;
		
		/** @var int const DATA_LIMIT		Limitation of total data fields length */
		const DATA_LIMIT = 16;
		
		
		/**
		* Initialization method. Set data schema, secret key and initialization flag.
		*
		* @param array $schema	Your data schema. Same structure as $_schema (see above).
		* @param string $key		Your secret key.
		*
		* @return bool Returns initialization flag value.
		*/
		static function init($schema = array(), $key = '') {
			$result = false;
			if(empty($key)) {
				$key = md5(microtime(true));
			}
			if(!preg_match('/^[0-9a-f]{32}$/', $key)) {
				$key = md5($key);
			}
			self::$_key = $key;
			if(!empty($schema)) {
				self::$_schema = $schema;
			}
			
			/* verify data schema */
			if(is_array(self::$_schema)) {
				$digits = 0;
				foreach(self::$_schema as $key => $val) {
					if(!is_int($val)) {
						return false;
					}
					$digits += (int)$val;
				}
				if($digits > 0 && $digits <= self::DATA_LIMIT) {
					$result = true;
				}
				else {
					$result = false;
				}
			}
			self::$_init = $result;
			return $result;
		}
		
		/**
		* Code data to GUID.
		*
		* @param array $data		Data for generating GUID. Should contain same keys as your data schema.
		*		0 values can be skipped.
		*
		* @return string|false Returns false, if class not initialized, or GUID.
		*/
		static function code($data) {
			$result = false;
			if(self::$_init) {
				$guid_data = array();
				$h_data = '';
				foreach(self::$_schema as $key => $val) {
					/* prepare data: convert to HEX and make length of fields according to schema */
					$guid_data[$key] = !empty($data[$key]) ? (int)$data[$key] : 0;
					$guid_data[$key] = strtolower(dechex($guid_data[$key]));
					if(strlen($guid_data[$key]) > $val) {
						$guid_data[$key] = substr($guid_data[$key], -$val);
					}
					else {
						while(strlen($guid_data[$key]) < $val) {
							$guid_data[$key] = '0' . $guid_data[$key];
						}
					}
					/* make data string */
					$h_data .= $guid_data[$key];
				}
				/* get check sum */
				$h_sum = self::_checkSum($h_data);
				/* data normalization */
				while(strlen($h_data) < self::DATA_LIMIT) {
					$guid_data .= '0';
				}
				$h_data = $h_sum . $h_data . '00000000';
				/* convert to guid */
				$h_data = self::_hexXOR($h_data, md5($h_sum));
				$h_data = preg_replace('/[0-9a-f]{8}$/', $h_sum, $h_data);
				if(preg_match('/^([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})$/', $h_data, $m)) {
					$result = $m[1] . '-' . $m[2] . '-' . $m[3] . '-' . $m[4] . '-' . $m[5];
				}
			}
			return $result;
		}
		
		/**
		* Decode GUID to data.
		*
		* @param string $guid		One of your GUID.
		*
		* @return array|false Returns false, if check sum not passed or class not initialized, or data.
		*/
		static function decode($guid) {
			$result = array();
			if(self::$_init) {
				$guid = preg_replace('/[^0-9a-f]/', '', strtolower($guid));
				if(preg_match('/^[0-9a-f]{32}$/', $guid)) {
					$h_sum1 = substr($guid, 24, 8);
					$h_data = self::_hexXOR($guid, md5($h_sum1));
					$h_sum2 = substr($h_data, 0, 8);
					$h_data = substr($h_data, 8, 16);
					if($h_sum1 == $h_sum2 && $h_sum2 == self::_checkSum($h_data)) {
						$position = 0;
						foreach(self::$_schema as $key => $val) {
							$result[$key] = hexdec(substr($h_data, $position, $val));
							$position += $val;
						}
					}
				}
			}
			return !empty($result) ? $result : false;
		}

		
		
		/**
		* Calculate check sum of data.
		*
		* @param string $string		String with normalized data.
		*
		* @return string Returns check sum.
		*/
		private static function _checkSum($string) {
			$key_pre = self::_hexToBin(substr(self::$_key, 0, 8));
			$key_index = self::_binOR(self::_hexToBin(substr(self::$_key, 8, 8)), '10000000000000000000000000000000'); /* make sure that first digit is 1 */
			$key_post = self::_hexToBin(substr(self::$_key, 16, 8));
			$string = self::_hexToBin($string);
			$string_pre = $string;
			$string_post = '';
			if(strlen($key_pre) < strlen($string)) {
				$string_pre = substr($string, 0, strlen($key_pre));
				$string_post = substr($string, strlen($key_pre));
			}
			$crc32 = self::_binXOR($key_pre, $string_pre) . $string_post;
			$length = strlen($crc32);
			for($i = 0; $i < $length; $i++) {
				if($crc32[$i] == '1') {
					$ts_pre = substr($crc32, 0, $i);
					$ts_index = substr($crc32, $i, strlen($key_pre));
					$ts_post = substr($crc32, $i + strlen($key_pre));
					while(strlen($ts_index) < strlen($key_pre)) {
						$ts_index .= '0';
					}
					$crc32 = $ts_pre . self::_binXOR($ts_index, $key_index) . $ts_post;
				}
			}
			$crc32 = preg_replace('/^0+/', '', $crc32);
			$crc32 = self::_binXOR($crc32, $key_post);
			while(strlen($crc32) < 32) {
				$crc32 = '0' . $crc32;
			}
			$result = array(
				dechex(bindec(substr($crc32, 0, 4))),
				dechex(bindec(substr($crc32, 4, 4))),
				dechex(bindec(substr($crc32, 8, 4))),
				dechex(bindec(substr($crc32, 12, 4))),
				dechex(bindec(substr($crc32, 16, 4))),
				dechex(bindec(substr($crc32, 20, 4))),
				dechex(bindec(substr($crc32, 24, 4))),
				dechex(bindec(substr($crc32, 28, 4)))
			);
			return implode('', $result);
		}
		
		/**
		* Convert HEX string to BIN string.
		*
		* @param string $string		Hexadecimal string of any length.
		*
		* @return string Returns binary string.
		*/
		private static function _hexToBin($string) {
			$result = '';
			$string = strtolower($string);
			if(preg_match('/^[0-9a-fA-F]*$/', $string)) {
				$string = preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
				foreach($string as $key => $char) {
					$string[$key] = decbin(hexdec($char));
					while(strlen($string[$key]) < 4) {
						$string[$key] = '0' . $string[$key];
					}
				}
				$result = implode('', $string);
			}
			return $result;
		}
	
		/**
		* Convert BIN string to HEX string.
		*
		* @param string $string		Binary string of any length.
		*
		* @return string Returns hexadecimal string.
		*/
		private static function _binToHex($string) {
			$result = '';
			if(preg_match('/^[01]*$/', $string)) {
				while(strlen($string) % 4 != 0) {
					$string = '0' . $string;
				}
				$chars = array();
				for($i = 0; $i < round(strlen($string) / 4); $i++) {
					$chars[] = dechex(bindec(substr($string, $i * 4, 4)));
				}
				$result = implode('', $chars);
			}
			return $result;
		}

		/**
		* XOR login with BIN strings.
		*
		* @param string $a		Binary string of any length.
		* @param string $b		Binary string of any length.
		*
		* @return string Returns $a XOR $b string.
		*/
		private static function _binXOR($a, $b) {
			$result = '';
			if(preg_match('/^[01]*$/', $a) && preg_match('/^[01]*$/', $b)) {
				$length = max(strlen($a), strlen($b));
				while(strlen($a) < $length) {
					$a = '0' . $a;
				}
				while(strlen($b) < $length) {
					$b = '0' . $b;
				}
				$result = array();
				for($i = 0; $i < $length; $i++) {
					$result[] = $a[$i] == $b[$i] ? '0' : '1';
				}
				$result = implode('', $result);
			}
			return $result;
		}

		/**
		* OR login with BIN strings.
		*
		* @param string $a		Binary string of any length.
		* @param string $b		Binary string of any length.
		*
		* @return string Returns $a OR $b string.
		*/
		private static function _binOR($a, $b) {
			$result = '';
			if(preg_match('/^[01]*$/', $a) && preg_match('/^[01]*$/', $b)) {
				$length = max(strlen($a), strlen($b));
				while(strlen($a) < $length) {
					$a = '0' . $a;
				}
				while(strlen($b) < $length) {
					$b = '0' . $b;
				}
				$result = array();
				for($i = 0; $i < $length; $i++) {
					$result[] = $a[$i] == '1' || $b[$i] == '1' ? '1' : '0';
				}
				$result = implode('', $result);
			}
			return $result;
		}
		
		/**
		* XOR login with HEX strings.
		*
		* @param string $a		Hexadecimal string of any length.
		* @param string $b		Hexadecimal string of any length.
		*
		* @return string Returns $a XOR $b string.
		*/
		private static function _hexXOR($a, $b) {
			$result = '';
			if(preg_match('/^[0-9a-fA-F]*$/', $a) && preg_match('/^[0-9a-fA-F]*$/', $b)) {
				$a = self::_hexToBin($a);
				$b = self::_hexToBin($b);
				$length = max(strlen($a), strlen($b));
				while(strlen($a) < $length) {
					$a = '0' . $a;
				}
				while(strlen($b) < $length) {
					$b = '0' . $b;
				}
				$result = array();
				for($i = 0; $i < $length; $i++) {
					$result[] = $a[$i] == $b[$i] ? '0' : '1';
				}
				$result = implode('', $result);
				$result = self::_binToHex($result);
			}
			return $result;
		}
	}