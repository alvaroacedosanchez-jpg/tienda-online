<?php

namespace Redsys;

class Rest {
    static function generic(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $transactionType = "0") {
        if(\Redsys\TransactionType::$authorisation != $params->transactionType && empty($params->transactionType) 
            && (\Redsys\TransactionType::$authorisation == $transactionType || !empty($transactionType))) {
            $params->transactionType = $transactionType;
        }

        return self::treat($merchant, $params);
    }

    static function genericInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $transactionType = "0") {
        if(\Redsys\TransactionType::$authorisation != $params->transactionType && empty($params->transactionType) 
            && (\Redsys\TransactionType::$authorisation == $transactionType || !empty($transactionType))) {
            $params->transactionType = $transactionType;
        }

        return self::init($merchant, $params);
    }

    static function authorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authorisation);
    }

    static function authorization(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authorization);
    }

    static function authorisationInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::genericInit($merchant, $params, \Redsys\TransactionType::$authorisation);
    }

    static function authorizationInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::genericInit($merchant, $params, \Redsys\TransactionType::$authorization);
    }

    static function preauthorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorisation);
    }

    static function preauthorization(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorization);
    }

    static function preauthorisationInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::genericInit($merchant, $params, \Redsys\TransactionType::$preauthorisation);
    }

    static function preauthorizationInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::genericInit($merchant, $params, \Redsys\TransactionType::$preauthorization);
    }

    static function preauthorisationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorisationConfirmation);
    }

    static function preauthorizationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorizationConfirmation);
    }

    static function refund(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$refund);
    }

    static function authentication(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authentication);
    }

    static function authenticationInit(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::genericInit($merchant, $params, \Redsys\TransactionType::$authentication);
    }

    static function authenticationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authenticationConfirmation);
    }

    static function void(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$void);
    }

    static function voidAuthenticationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$voidAuthenticationConfirmation);
    }

    static function paygold(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$paygold);
    }

    static function challengeResponse(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        $emv3ds = !empty($params) ? $params->emv3ds : null;
        if ($emv3ds !== null && ($emv3ds["threeDSInfo"] ?? null) !== "ChallengeResponse") {
            $emv3ds["threeDSInfo"] = "ChallengeResponse";
            $params->emv3ds = $emv3ds;
        }

        return self::generic($merchant, $params, "");
    }

    static function cancellation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$cancellation);
    }

    static function preauthorisationPartialConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$partialConfirmation);
    }

    static function preauthorizationPartialConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$partialConfirmation);
    }

    static function rest($url, $data, $headers = array()) {
        $post_request = json_encode($data);
        $rest = curl_init();

        curl_setopt($rest, CURLOPT_URL, $url);
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($rest, CURLOPT_POST, true);
        curl_setopt($rest, CURLOPT_SSLVERSION, 6);
        curl_setopt($rest, CURLOPT_POSTFIELDS, $post_request);
        curl_setopt($rest, CURLOPT_HEADER, true);
        curl_setopt($rest, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_request)
        ], $headers));

        $curlRawResponse = curl_exec($rest);
        
        // Obtener información de la respuesta
        $headerSize = curl_getinfo($rest, CURLINFO_HEADER_SIZE);

        // Separar headers y cuerpo
        $rawHeaders = substr($curlRawResponse, 0, $headerSize);
        $body = substr($curlRawResponse, $headerSize);

        // Procesar línea por línea
        $headers = array();
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }

        $resp = new \Redsys\Response();
        $resp->headers = $headers;
        $resp->raw = $body;
        $resp->httpCode = curl_getinfo($rest, CURLINFO_HTTP_CODE);
        
        return $resp;
    }

    static function sis($url, $merchant, $params) {
        $payload = \Redsys\RequestData::generate($merchant, $params);

        if(!empty($params->customUrl)) {
            $url = $params->customUrl;
        }

        $response = self::rest($url, $payload);
        
        if(!empty($response) && !empty($response->raw)) {
            $tmp = json_decode($response->raw, true);
        }

        if(isset($tmp["errorCode"])) {
            $response->errorCode = $tmp["errorCode"];

            $lang = empty($params->consumerLanguage) ? 1 : $params->consumerLanguage;
            $desc = \Redsys\Resources::getDescriptionError($response->errorCode, $lang);
            if(!empty($desc)) {
                $response->description = $desc;
            }

        } else if(isset($tmp["Ds_MerchantParameters"])) {
            $response->data = \Redsys\Parameters::digest($merchant, $tmp);

            $lang = empty($params->consumerLanguage) ? 1 : $params->consumerLanguage;
            $desc = \Redsys\Resources::getDescriptionResposeCode($response->data->codResponse, $lang);

            $response->description = (empty($response->data->codResponse) ? $response->description : \Redsys\Resources::getDescriptionResposeCode($response->data->codResponse, $lang));

        }

        return $response;
    }

    static function mkpl($url, $merchant, $params) {
        $headers = array();
        if(!empty($merchant)) {
            if(!empty($merchant->redsysClientId)) {
                $headers["RedsysClientId"] = $merchant->redsysClientId;
            }

            if(!empty($merchant->redsysClientSecret)) {
                $headers["RedsysClientSecret"] = $merchant->redsysClientSecret;
            }
        }  

        $response = self::rest($url, \Redsys\RequestData::generateMkpl($merchant, $params), $headers);

        $decodedResponse = json_decode($response->raw, true);
        if(!empty($decodedResponse["info"])) {
            if(!empty($decodedResponse["info"]["data"])) {
                $data = $decodedResponse["info"]["data"];
                $dataJsonEncoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
                $computedSignature = \Redsys\Signature::createMerchantSignature($merchant->kc, $dataJsonEncoded, $decodedResponse["info"]["idTransaction"], $decodedResponse["signatureData"]["signatureType"]);
                \Redsys\Signature::checkSignatures($computedSignature, $decodedResponse["signatureData"]["signature"]);
        
                $response->data = \Redsys\Parameters::initWithoutTreatment($data);
            }
    
            if(!empty($decodedResponse["info"]["result"])) {
                if(!empty($decodedResponse["info"]["result"]["code"])) {
                    $response->code = $decodedResponse["info"]["result"]["code"];
                }

                if(!empty($decodedResponse["info"]["result"]["description"])) {
                    $response->description = $decodedResponse["info"]["result"]["description"];
                }
            }
        }

        return $response;
    }

    static function init($merchant, $params) {
        if(empty($params->emv3ds)) {
            $params->emv3ds = array("threeDSInfo" => "CardData");
        }
        $url = \Redsys\Endpoints::$init[$merchant->env];
        return self::sis($url, $merchant, $params);

    }

    static function treat($merchant, $params) {
        $url = \Redsys\Endpoints::$treat[$merchant->env];
        return self::sis($url, $merchant, $params);
    }

    static function queryOperation($merchant, $params) {
        $url = \Redsys\Endpoints::$query[$merchant->env];
        return self::mkpl($url, $merchant, $params);
    }
}

?>