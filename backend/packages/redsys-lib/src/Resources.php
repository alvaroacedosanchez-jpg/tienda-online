<?php

namespace Redsys;

class Resources {
    private static $sisErrorDescription = [
		1 => array(),
    ];

	private static $codResponseDescription = [
		1 => array(),
    ];

    public static function getDescriptionError($code, $lang = 1) {
		$lang = intval($lang);
		self::loadLang("sisErrorDescription", $lang);
        return self::$sisErrorDescription[$lang][$code] ?? null;
    }

	public static function getDescriptionResposeCode($code, $lang = 1) {
		if($code != "z") {
			$intCode = intval($code);
			$intCode = ($intCode > 0 && $intCode < 100) ? 0 : $intCode;
			$code = strval($intCode);
		}

		$lang = intval($lang);
		self::loadLang("codResponseDescription", $lang);
        return self::$codResponseDescription[$lang][$code] ?? null;
    }

	private static function loadLang($resource, $lang = 1) {
		if(!empty(self::${$resource}[$lang])) {
			return;
		}
		
		$path = __DIR__ . "/Resources/$resource/$lang.json";
		if (file_exists($path)) {
			self::${$resource}[$lang] = json_decode(file_get_contents($path), true);
		} else {
			self::${$resource}[$lang] = array();
		}
	}
}

?>