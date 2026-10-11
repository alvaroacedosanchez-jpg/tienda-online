<?php

namespace Redsys;

class Redirect {

    public static function __callStatic($name, $arguments) {
        if (method_exists(\Redsys\GenerateRedirectForm::class, $name)) {
            echo \Redsys\GenerateRedirectForm::$name(...$arguments);
        }
        throw new BadMethodCallException("Method '$name' not found in \Redsys\GenerateRedirectForm.");
    }

    static function toUrl(string $url) {
        echo \Redsys\GenerateRedirectForm::genericRedirectForm($url);
        exit();
    }

    static function generic(\Redsys\Merchant $merchant, \Redsys\Parameters $params, $transactionType = 0) {
        echo \Redsys\GenerateRedirectForm::generic($merchant, $params, $transactionType);
    }

    static function authorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        echo \Redsys\GenerateRedirectForm::authorisation($merchant, $params);
    }

    static function preauthorisation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        echo \Redsys\GenerateRedirectForm::preauthorisation($merchant, $params);
    }

    static function preauthorizationConfirmation(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        echo \Redsys\GenerateRedirectForm::preauthorisationConfirmation($merchant, $params);
    }

    static function challenge(\Redsys\Merchant $merchant, \Redsys\Parameters $params) {
        echo \Redsys\GenerateRedirectForm::challenge($merchant, $params);
    }
}

?>