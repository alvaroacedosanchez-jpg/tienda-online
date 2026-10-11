<?php

namespace Redsys;

class Utils {
	/******  Random String  ******/
	static function randomString($len = 12, $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
		if(empty($alphabet)) {
			throw new Exception("Alphabet must not be empty!");
		}

		$chars = $alphabet;
		$str = '';
		for ($i = 0; $i < $len; $i++) {
			$str .= $chars[random_int(0, strlen($chars) - 1)];
		}
		return $str;
	}

    static $defaultNumLang = "2";
    private static $langToNumberArray = array(
		"es" => "1",
    	"en" => "2",
    	"ca" => "3",
    	"va" => "10",
    	"gl" => "12",
    	"eu" => "13"
	);

	static function getSystemNumLang() {
		$locale = setlocale(LC_ALL, 0);
		return getNumLangFromLang($locale);
	}

	static function  getNumLangFromLang($lang) {
		if(empty($lang)) {
			return self::$defaultNumLang;			
		}
		
		$minLang = substr($lang, 0, 2);
		$numLang = $langToNumberArray[$minLang];
		
		return $numLang ?? self::$defaultNumLang;
	}

	/******  Base64 Functions  ******/
	static function base64_url_encode($input) {
		return strtr(base64_encode($input), '+/', '-_');
	}

	static function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}

	static function base64_url_encode_safe($input) {
		return str_replace("=", "", strtr(base64_encode($input), '+/', '-_'));
	}

	static function base64_url_decode_safe($input) {
		$str = str_pad($input, strlen($input) + (4 - strlen($input) % 4) % 4, '=', STR_PAD_RIGHT);
		return base64_decode(strtr($str, '-_', '+/'));
	}

    /******  Current URL  ******/
	static function getCurrentUrl() {
		$isHttps = (
			(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
			$_SERVER['SERVER_PORT'] == 443 ||
			(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
		);

        $url = ($isHttps ? "https" : "http");
        $url .= "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $url;
    }
}

?>