<?php

namespace Redsys;

class GenerateRedirectForm {
    
    static function genericRedirectForm($url, $data = array()) {
        $redirectForm = "<form id='redirectForm' action='" . htmlspecialchars($url) . "' method='post'>";
        foreach ($data as $key => $value) {
            $redirectForm .= "<input type='hidden' name='$key' value='" . htmlspecialchars($data[$key]) . "'>";
        }
        $redirectForm .= "</form>";
        
        $redirectForm .= "<noscript><p>Haz clic para continuar:</p><button type='submit' form='redirectForm'>Continuar</button></noscript>";

        $redirectForm .= "<script>document.getElementById('redirectForm').submit();</script>";
        return $redirectForm;
    }

    static function generic(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $transactionType = "0") {
        if(\Redsys\TransactionType::$authorisation != $params->transactionType && empty($params->transactionType) 
            && (\Redsys\TransactionType::$authorisation == $transactionType || !empty($transactionType))) {
            $params->transactionType = $transactionType;
        }

        $url = \Redsys\Endpoints::$redirect[$merchant->env];
        if(!empty($params->customUrl)) {
            $url = $params->customUrl;
        }

        return self::genericRedirectForm($url, \Redsys\RequestData::generate($merchant, $params));
    }

    static function authorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authorisation);
    }

    static function authorization(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authorization);
    }

    static function preauthorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorisation);
    }

    static function preauthorization(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$preauthorization);
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

    static function authenticationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$authenticationConfirmation);
    }

    static function void(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        return self::generic($merchant, $params, \Redsys\TransactionType::$void);
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

    static function challenge(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        $emv3ds = $params->emv3ds;
        $url = $emv3ds["acsURL"];
        $creq = $emv3ds["creq"];
        $data = array("CReq" => $creq);
        return self::genericRedirectForm($url, $data);
    }
}

?>