<?php

namespace Redsys;

class TransactionType {
    public static string $authorisation = "0";
    public static string $authorization = "0";
    public static string $preauthorisation = "1";
    public static string $preauthorization = "1";
    public static string $preauthorisationConfirmation = "2";
    public static string $preauthorizationConfirmation = "2";
    public static string $refund = "3";
    public static string $authentication = "7";
    public static string $authenticationConfirmation = "8";
    public static string $void = "9";
    public static string $voidAuthenticationConfirmation = "47";
    public static string $replacementAuthorization = "11";
    public static string $paygold = "15";
    public static string $paygoldConfirmation = "38";
    public static string $cancellation = "45";
    public static string $partialConfirmation = "48";
}