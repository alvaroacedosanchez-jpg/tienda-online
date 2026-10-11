<?php

namespace Redsys;

class RequestData {

	static function generate(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $signatureVersion = "HMAC_SHA512_V2") {
		$key = $merchant->kc;
        if(empty($params->fuc)) {
            $params->fuc = $merchant->fuc;
        }

        if(empty($params->terminal)) {
            $params->terminal = $merchant->terminal;
        }

		if(empty($params->module)) {
			$params->module = \Redsys\Version::VERSION;	
		}

		if(empty($params->currency)) {
            $params->currency = \Redsys\Currency::$EUR; // € Default
        }

        if(!empty($params->paymethod) && empty($params->payMethods)) {
            $params->payMethods = $params->paymethod;
        }

		$merchantParameters = \Redsys\Utils::base64_url_encode_safe($params->toJsonIn());
		$signature = \Redsys\Signature::createMerchantSignature($key, $merchantParameters, $params->order, $signatureVersion);

		return array(
			"Ds_MerchantParameters" => $merchantParameters,
			"Ds_Signature" => $signature,
			"Ds_SignatureVersion" => $signatureVersion,
		);
	}

	static function generateMkpl(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $signatureVersion = "HMAC_SHA512_V2") {
		$key = $merchant->kc;
        if(empty($params->fuc)) {
            $params->fuc = $merchant->fuc;
        }

        if(empty($params->terminal)) {
            $params->terminal = $merchant->terminal;
        }

		if(empty($params->idTransaction)) {
			$params->idTransaction = \Redsys\Utils::randomString(12);	
		}

		$merchantParameters = json_encode($params);
		if($signatureVersion == "HMAC_SHA256_V1") {
			$signature = \Redsys\Signature::createMerchantSignature256V1($key, $merchantParameters, $params->idTransaction);
		} else if ($signatureVersion == "HMAC_SHA512_V1") {
			$signature = \Redsys\Signature::createMerchantSignature512V1($key, $merchantParameters, $params->idTransaction);
		} else if($signatureVersion == "HMAC_SHA512_V2") {
			$signature = \Redsys\Signature::createMerchantSignature512V2($key, $merchantParameters, $params->idTransaction);
		}

		return array(
			"info" => array(
				"data" => json_decode($merchantParameters, true)
			),
			"signatureData" => array(
				"signatureType" => $signatureVersion,
				"signature" => $signature,
			),
		);
	}
}

?>