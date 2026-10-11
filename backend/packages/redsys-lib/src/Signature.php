<?php

/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
* 
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
* 
* Redsys es titular de todos los derechos de propiedad intelectual e industrial
* del software.
* 
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
* 
* Redsys se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
* 
* Redsys Servicios de Procesamiento, S.L., CIF B85955367
*
* VERSIÓN SIMPLIFICADA DE LA LIBRERÍA
*
*/

namespace Redsys;

class Signature {
	/******  3DES Function  ******/
	static function encrypt_3DES($message, $key) {
		// Se cifra
		$l = ceil(strlen($message) / 8) * 8;
		
		return substr(openssl_encrypt($message . str_repeat("\0", $l - strlen($message)), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0"), 0, $l);
		
	}

	/******  AES Function  ******/
	static function encrypt_AES($data, $key) {
		$fixedKey = str_pad(substr($key, 0, 16), 16, "0");
		$firma = base64_encode(openssl_encrypt($data, "aes-128-cbc", $fixedKey, OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"));

		return $firma;
	}

	/******  MAC Function ******/
	static function mac256($data, $key) {
		return hash_hmac('sha256', $data, $key, true);
	}

	static function mac512($data, $key) {
		$sha = hash_hmac('sha512', $data, $key, true);
		return $sha;
	}

	/******  Notificaciones SOAP SALIDA ******/
	static function createMerchantSignature256V1($key, $datos, $numPedido) {
		// Se decodifica la clave Base64
		$key = \Redsys\Utils::base64_url_decode($key);

		// Se diversifica la clave con el Número de Pedido
		$key = self::encrypt_3DES($numPedido, $key);

		// MAC256 del parámetro Ds_Parameters que envía Redsys
		$res = self::mac256($datos, $key);
		
		// Se codifican los datos Base64
		return \Redsys\Utils::base64_url_encode($res);
	}

	/******  Notificaciones SOAP SALIDA ******/
	static function createMerchantSignature512V1($key, $datos, $numPedido) {
		// Se diversifica la clave con el Número de Pedido
		$key = self::encrypt_AES($numPedido, $key);

		// MAC512 del parámetro Ds_Parameters que envía Redsys
		$res = self::mac512($datos, $key);

		// Se codifican los datos Base64
		return \Redsys\Utils::base64_url_encode($res);
	}

	/******  Notificaciones SOAP SALIDA ******/
	static function createMerchantSignature512V2($key, $data, $diversifyingFactor) {
		// Se diversifica la clave con el Número de Pedido
		$key = self::encrypt_AES($diversifyingFactor, $key);

		// MAC512 del parámetro Ds_Parameters que envía Redsys
		$res = self::mac512($data, $key);

		// Se codifican los datos Base64
		return \Redsys\Utils::base64_url_encode_safe($res);
	}

	static function createMerchantSignature($key, $data, $diversifyingFactor, $signatureVersion = "HMAC_SHA512_V2") {
		if($signatureVersion == "HMAC_SHA256_V1") {
			return self::createMerchantSignature256V1($key, $data, $diversifyingFactor);
		} else if ($signatureVersion == "HMAC_SHA512_V1") {
			return self::createMerchantSignature512V1($key, $data, $diversifyingFactor);
		} else if($signatureVersion == "HMAC_SHA512_V2") {
			return self::createMerchantSignature512V2($key, $data, $diversifyingFactor);
		}
	}

	static function checkSignatures($sig1, $sig2) {
		if (strcasecmp($sig1, $sig2) !== 0) {
			throw new \Exception("Integrity failure. The received signature does not match the calculated one.");
		}
	}
}

?>