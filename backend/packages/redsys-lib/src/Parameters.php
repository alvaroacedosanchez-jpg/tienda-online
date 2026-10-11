<?php

namespace Redsys;

class Parameters implements \JsonSerializable 
{
	// -- COMMON --
	
	/** In: DS_MERCHANT_AMOUNT 
	 * Out: DS_AMOUNT
     */
	public $amount;
	/** In: DS_MERCHANT_AUTHORISATIONCODE
	 * Out: DS_AUTHORISATIONCODE
	 */
	public $authorisationCode;
	/** In: DS_MERCHANT_COF_TXNID
	 * Out: DS_MERCHANT_COF_TXNID
	 */
	public $cofTxnId;
	/** In: DS_MERCHANT_CONSUMERLANGUAGE
	 * Out: DS_CONSUMERLANGUAGE
	 */
	public $consumerLanguage;
	/** In: DS_MERCHANT_CURRENCY
	 * Out: DS_CURRENCY
	 */
	public $currency;
	/** In: DS_MERCHANT_EMV3DS
	 * Out: DS_EMV3DS
	 */
	public $emv3ds;
	/** In: DS_MERCHANT_IDENTIFIER
	 * Out: DS_MERCHANT_IDENTIFIER
	 */
	public $tokenizedCard;
	/** In: DS_MERCHANT_MERCHANTCODE
	 * Out: DS_MERCHANTCODE
	 */
	public $fuc;
	/** In: DS_MERCHANT_MERCHANTDATA
	 * Out:	DS_MERCHANTDATA
	 */
	public $merchantData;
	/** In: DS_MERCHANT_ORDER
	 * Out:	DS_ORDER
	 */
	public $order;
	/** In: DS_MERCHANT_TERMINAL
	 * Out:	DS_TERMINAL
	 */
	public $terminal;
	/** In: DS_MERCHANT_TRANSACTIONTYPE
	 * Out:	DS_TRANSACTIONTYPE
	 */
	public $transactionType;
	/** In: DS_MERCHANT_DCC
	 * Out:	DS_DCC 
	 */
	public $dcc;
	/** In: DS_MERCHANT_EXCEP_SCA
	 * Out:	DS_EXCEP_SCA
	 */
	public $excepSca;
	/** In: DS_MERCHANT_RTS
	 * Out:	DS_RTS / rts
	 */
	public $rts;
    /** In: DS_MERCHANT_EXPIRYDATE
	 * Out:	DS_EXPIRYDATE
	 */
	public $cardExpiryDate;
    /** In: DS_MERCHANT_PAN
	 * Out: DS_CARD_NUMBER
	 */
	public $cardNumber;
	/** In: DS_MERCHANT_BIZUM_DATA
	 * Out: DS_BIZUM_DATA
	 */
	public $bizumData;


	// -- INPUT --
	/** In: DS_MERCHANT_COF_INI
	 */
	public $cofIni;
	/** In: DS_MERCHANT_COF_TYPE
	 */
	public $cofType;
	/** In: DS_MERCHANT_CVV2
	 */
	public $cvv2;
	/** In: DS_MERCHANT_DIRECTPAYMENT
	 */
	public $directPayment;
	/** In: DS_MERCHANT_GROUP
	 */
	public $group;
	/** In: DS_MERCHANT_IDOPER
	 */
	public $idOper;
	/** In: DS_MERCHANT_MERCHANTNAME
	 */
	public $merchantName;
	/** In: DS_MERCHANT_MERCHANTURL
	 */
	public $merchantUrl;
	/** In: DS_MERCHANT_PAYMETHODS
	 */
	public $payMethods;
	/** In: DS_MERCHANT_PRODUCTDESCRIPTION
	 */
	public $productDescription;
	/** In: DS_MERCHANT_TAX_REFERENCE
	 */
	public $taxReference;
	/** In: DS_MERCHANT_TITULAR
	 */
	public $cardholderName;
	/** In: DS_MERCHANT_TRANSACTIONDATE
	 */
	public $transactionDate;
	/** In: DS_MERCHANT_URLKO
	 */
	public $urlKo;
	/** In: DS_MERCHANT_URLOK
	 */
	public $urlOk;
	/** In: DS_MERCHANT_TOKENDATA
	 */
	public $tokenData;
	/** In: DS_MERCHANT_SHIPPINGADDRESSPYP
	 */
	public $shippingAddressPyp;
	/** In: DS_MERCHANT_MERCHANTDESCRIPTOR
	 */
	public $merchantDescriptor;
	/** In: DS_MERCHANT_PERSOCODE
	 */
	public $persoCode;
	/** In: DS_MERCHANT_MATCHINGDATA
	 */
	public $matchingData;
	/** In: DS_MERCHANT_MPIEXTERNAL
	 */
	public $externalMpi;
	/** In: DS_MERCHANT_CUSTOMER_MOBILE
	 */
	public $customerMobile;
	/** In: DS_MERCHANT_CUSTOMER_MAIL
	 */
	public $customerMail;
	/** In: DS_MERCHANT_P2F_EXPIRYDATE
	 */
	public $p2fExpiryDate;
	/** In: DS_MERCHANT_CUSTOMER_SMS_TEXT
	 */
	public $customerSmsText;
	/** In: DS_MERCHANT_P2F_XMLDATA
	 */
	public $p2fXmlData;
	/** In: DS_MERCHANT_FINANCING
	 */
	public $financing;
	/** In: DS_MERCHANT_OTA
	 */
	public $ota;
	/** In: DS_MERCHANT_CLIENTIP
	 */
	public $clientIp;
	/** In: DS_XPAYTYPE
	 */
	public $xPayType;
	/** In: DS_XPAYORIGEN
	 */
	public $xPayOrigin;
	/** In: DS_XPAYDECODEDDATA
	 */
	public $xPayDecodedData;
	/** In: DS_XPAYDATA
	 */
	public $xPayData;
	/** In: DS_MERCHANT_MODULE
	 */
	public $module;
	/** In: DS_MERCHANT_BIZUM_MOBILENUMBER
	 */
	public $bizumMobileNumber;

	// -- OUTPUT --
	/** Out: DS_AMOUNT_DCC
	 */
    public $dccAmount;
	/** Out: DS_AMOUNT_EURO
	 */
    public $amountEuro;
	/** Out: DS_CARD_BRAND
	 */
    public $cardBrand;
	/** Out: DS_CARD_COUNTRY
	 */
    public $cardCountry;
	/** Out: DS_CARD_PSD2
	 */
    public $cardPsd2;
	/** Out: DS_CARD_TYPE
	 */
    public $cardType;
	/** Out: DS_CURRENCY_DCC
	 */
    public $dccCurrency;
	/** Out: DS_DATE
	 */
    public $date;
	/** Out: DS_ERRORCODE
	 */
    public $errorCode;
	/** Out: DS_HOUR
	 */
    public $hour;
	/** Out: DS_PROCESSEDPAYMETHOD
	 */
    public $processedPayMethod;
	/** Out: DS_RESPONSE
	 */
    public $codResponse;
	/** Out: DS_SECUREPAYMENT
	 */
    public $securePayment;
	/** Out: DS_SIGNATURE
	 */
    public $signature;
	/** Out: DS_URLPAGO2FASES
	 */
    public $p2fUrl;
	/** Out: CODIGO 
	 */
	public $code;
	/** Out: DS_RESPONSE_DESCRIPTION 
	 */
	public $responseDescription;
	/** Out: DS_CARD_TYPOLOGY
	 */
	public $cardTypology;
    /** Out: DS_CARD_ISSUER
	 */
	public $cardIssuer;
    /** Out: DS_CARD_TOKEN_INDICATOR
	 */
	public $cardTokenIndicator;
    /** Out: DS_CARD_TREATMENT
	 */
	public $cardTreatment;
	/** Out: DS_CARD_PRODUCTTYPE
	 */
	public $cardProductType;
	/** Out: DS_BIZUMIDSTATUS
	 */
	public $bizumIdStatus;
	/** Out: DS_BIZUMIDRESPONSE
	 */
	public $bizumIdResponse;
	/** Out: BD_BIZUMIDDESCRIPTION
	 */
	public $bizumIdDescription;

	// PSEMARKT
	// idTransaction
	public $idTransaction;
	// initialDate
	public $initialDate;
	// finalDate
	public $finalDate;
	// operations
	public $operations;
	// numRecords
	public $numRecords;
	// sessionDate
	public $sessionDate;
	// cier
	public $cier;
	// transactionId
	public $transactionId;
	// state
	public $state;
	// authorized
	public $authorized;
	// authorizationNumber
	public $authorizationNumber;
	// refundAmount
	public $refundAmount;
	// paymethod
	public $paymethod;
	// ipConnection
	public $ipConnection;
	// ipClient
	public $ipClient;
	// userLogin
	public $userLogin;
	// issuerTransactionId
	public $issuerTransactionId;
	// euroAmount
	public $euroAmount;
	// connectionType
	public $connectionType;
	// ipCountry
	public $ipCountry;
	// typeCsb
	public $typeCsb;
	// csb
	public $csb;
	// lotIndicator
	public $lotIndicator;
	// cardToken
	public $cardToken;
	// cofUse
	public $cofUse;
	// idAuthenticationDataV2
	public $idAuthenticationDataV2;
	// pspId
	public $pspId;
	// eci
	public $eci;
	// exemptionData
	public $exemptionData;
	// grouperCode
	public $grouperCode;
	// emitterCsbType
	public $emitterCsbType;
	// emitterCsb
	public $emitterCsb;
	// notifications
	public $notifications;

	// NOT SENT NOR RECEIVED
	public $customUrl;

	private $data = [];

	public function __get($name) {
		if(isset($this->data[$name])) {
			return $this->data[$name];
		}
		self::createAliasMapReverse();
		if(isset(self::$aliasMapReversed[mb_strtoupper($name)])) {
			$var = self::$aliasMapReversed[mb_strtoupper($name)];
			return $this->$var;
		}
		return null;
	}

	public function __set($name, $value) {
		self::createAliasMapReverse();
		$publicProps = get_object_vars($this);
		unset($publicProps['data']);
		if(in_array($name, $publicProps)) {
			$this->$name = $value;
		} else if(isset(self::$aliasMapReversed[mb_strtoupper($name)])) {
			$key = mb_strtoupper($name);
			$key2 = self::$aliasMapReversed[mb_strtoupper($name)];
			$this->data[$key] = $value;
			$this->$key2 = $value;
		} else {
			$key = isset(self::$aliasMapReversed[mb_strtoupper($name)]) ? mb_strtoupper($name) : $name;
			$this->data[$key] = $value;
		}
	}
	
	public function __isset($name) {
		self::createAliasMapReverse();
		if(isset(self::$aliasMapReversed[mb_strtoupper($name)])) {
			$var = self::$aliasMapReversed[mb_strtoupper($name)];
			if(isset($this->$var)) {
				return true;
			}
		}
		return isset($this->data[$name]);
	}
	
	public function jsonSerialize(): mixed {
		$publicProps = get_object_vars($this);
		unset($publicProps['data']);
		$all = array_merge($publicProps, $this->data);
		return array_filter($all, fn($x) => !is_null($x));
	}

	private static array $aliasMapReversed = [];

	public static array $aliasMapIn = [
		'amount' => 'DS_MERCHANT_AMOUNT',
		'authorisationCode' => 'DS_MERCHANT_AUTHORISATIONCODE',
		'cofTxnId' => 'DS_MERCHANT_COF_TXNID',
		'consumerLanguage' => 'DS_MERCHANT_CONSUMERLANGUAGE',
		'currency' => 'DS_MERCHANT_CURRENCY',
		'emv3ds' => 'DS_MERCHANT_EMV3DS',
		'tokenizedCard' => 'DS_MERCHANT_IDENTIFIER',
		'fuc' => 'DS_MERCHANT_MERCHANTCODE',
		'merchantData' => 'DS_MERCHANT_MERCHANTDATA',
		'order' => 'DS_MERCHANT_ORDER',
		'terminal' => 'DS_MERCHANT_TERMINAL',
		'transactionType' => 'DS_MERCHANT_TRANSACTIONTYPE',
		'dcc' => 'DS_MERCHANT_DCC',
		'excepSca' => 'DS_MERCHANT_EXCEP_SCA',
		'rts' => 'DS_MERCHANT_RTS',
		'cardExpiryDate' => 'DS_MERCHANT_EXPIRYDATE',
		'expiryDate' => 'DS_MERCHANT_EXPIRYDATE',
		'cardNumber' => 'DS_MERCHANT_PAN',
		'pan' => 'DS_MERCHANT_PAN',
		'bizumData' => 'DS_MERCHANT_BIZUM_DATA',

		'cofIni' => 'DS_MERCHANT_COF_INI',
		'cofType' => 'DS_MERCHANT_COF_TYPE',
		'cvv2' => 'DS_MERCHANT_CVV2',
		'directPayment' => 'DS_MERCHANT_DIRECTPAYMENT',
		'group' => 'DS_MERCHANT_GROUP',
		'idOper' => 'DS_MERCHANT_IDOPER',
		'merchantName' => 'DS_MERCHANT_MERCHANTNAME',
		'merchantUrl' => 'DS_MERCHANT_MERCHANTURL',
		'payMethods' => 'DS_MERCHANT_PAYMETHODS',
		'paymethod' => 'DS_MERCHANT_PAYMETHODS',
		'productDescription' => 'DS_MERCHANT_PRODUCTDESCRIPTION',
		'taxReference' => 'DS_MERCHANT_TAX_REFERENCE',
		'cardholderName' => 'DS_MERCHANT_TITULAR',
		'transactionDate' => 'DS_MERCHANT_TRANSACTIONDATE',
		'urlKo' => 'DS_MERCHANT_URLKO',
		'urlOk' => 'DS_MERCHANT_URLOK',
		'tokenData' => 'DS_MERCHANT_TOKENDATA',
		'shippingAddressPyp' => 'DS_MERCHANT_SHIPPINGADDRESSPYP',
		'merchantDescriptor' => 'DS_MERCHANT_MERCHANTDESCRIPTOR',
		'persoCode' => 'DS_MERCHANT_PERSOCODE',
		'matchingData' => 'DS_MERCHANT_MATCHINGDATA',
		'externalMpi' => 'DS_MERCHANT_MPIEXTERNAL',
		'customerMobile' => 'DS_MERCHANT_CUSTOMER_MOBILE',
		'customerMail' => 'DS_MERCHANT_CUSTOMER_MAIL',
		'p2fExpiryDate' => 'DS_MERCHANT_P2F_EXPIRYDATE',
		'customerSmsText' => 'DS_MERCHANT_STOMER_SMS_TEXT',
		'p2fXmlData' => 'DS_MERCHANT_P2F_XMLDATA',
		'financing' => 'DS_MERCHANT_FINANCING',
		'ota' => 'DS_MERCHANT_OTA',
		'clientIp' => 'DS_MERCHANT_CLIENTIP',
		'xPayType' => 'DS_XPAYTYPE',
		'xPayOrigin' => 'DS_XPAYORIGEN',
		'xPayDecodedData' => 'DS_XPAYDECODEDDATA',
		'xPayData' => 'DS_XPAYDATA',
		'module' => 'DS_MERCHANT_MODULE',
		'bizumMobileNumber' => 'DS_MERCHANT_BIZUM_MOBILENUMBER',
	];

	public static array $aliasMapOut = [
		'amount' => ['DS_AMOUNT'],
		'authorisationCode' => ['DS_AUTHORISATIONCODE'],
		'cofTxnId' => ['DS_MERCHANT_COF_TXNID'],
		'consumerLanguage' => ['DS_CONSUMERLANGUAGE', 'DS_LANGUAGE'],
		'currency' => ['DS_CURRENCY'],
		'emv3ds' => ['DS_EMV3DS'],
		'tokenizedCard' => ['DS_MERCHANT_IDENTIFIER'],
		'fuc' => ['DS_MERCHANTCODE'],
		'merchantData' => ['DS_MERCHANTDATA'],
		'order' => ['DS_ORDER'],
		'terminal' => ['DS_TERMINAL'],
		'transactionType' => ['DS_TRANSACTIONTYPE'],
		'dcc' => ['DS_DCC'],
		'excepSca' => ['DS_EXCEP_SCA'],
		'rts' => ['RTS', 'rts', 'DS_RTS'],
        'cardExpiryDate' => ['DS_EXPIRYDATE'],
		'expiryDate' => ['DS_EXPIRYDATE'],
        'cardNumber' => ['DS_CARD_NUMBER', 'DS_CARDNUMBER'],
        'pan' => ['DS_CARD_NUMBER', 'DS_CARDNUMBER'],

        'dccAmount' => ['DS_AMOUNT_DCC'],
        'dccCurrency' => ['DS_CURRENCY_DCC'],
        'amountEuro' => ['DS_AMOUNT_EURO'],
        'cardBrand' => ['DS_CARD_BRAND'],
        'cardCountry' => ['DS_CARD_COUNTRY'],
        'cardPsd2' => ['DS_CARD_PSD2'],
        'cardType' => ['DS_CARD_TYPE'],
        'date' => ['DS_DATE'],
        'errorCode' => ['DS_ERRORCODE', 'ERRORCODE'],
        'hour' => ['DS_HOUR'],
        'processedPayMethod' => ['DS_PROCESSEDPAYMETHOD'],
        'codResponse' => ['DS_RESPONSE'],
        'securePayment' => ['DS_SECUREPAYMENT'],
        'sugnature' => ['DS_SIGNATURE'],
        'p2fUrl' => ['DS_URLPAGO2FASES'],
        'code' => ['CODIGO'],
		'responseDescription' => ['DS_RESPONSE_DESCRIPTION'],
		'eci' => ['DS_ECI', 'eci'],
		'cardTypology' => ['DS_CARD_TYPOLOGY'],
		'cardIssuer' => ['DS_CARD_ISSUER'],
		'cardTokenIndicator' => ['DS_CARD_TOKEN_INDICATOR'],
		'cardTreatment' => ['DS_CARD_TREATMENT'],
		'cardProductType' => ['DS_CARD_PRODUCTTYPE'],
		'bizumIdStatus' => ['DS_BIZUMIDSTATUS'],
		'bizumIdResponse' => ['DS_BIZUMIDRESPONSE'],
		'bizumIdDescription' => ['DS_BIZUMIDDESCRIPTION'],
	];

	private static function createAliasMapReverse() {
		// Invertir aliasMapIn
		if(empty(self::$aliasMapReversed)) {
			foreach (self::$aliasMapIn as $key => $value) {
				self::$aliasMapReversed[$value] = $key;
			}

			// Invertir aliasMapOut
			foreach (self::$aliasMapOut as $key => $values) {
				foreach ((array)$values as $value) {
					self::$aliasMapReversed[$value] = $key;
				}
			}
		}
	}

	function toArrayIn() {
		$inputData = array();
		foreach (self::$aliasMapIn as $prop => $alias) {
			if (isset($this->$prop)) {
				$inputData[$alias] = $this->$prop;
				continue;
			}
		}

		foreach ($this->data as $prop => $value) {
			if (!isset($aliasMapIn[$prop])) {
				$inputData[$prop] = $value;
				continue;
			}
		}

		return $inputData;
	}

	function toJson() {
		return json_encode($this);
	}

	function toJsonIn() {
		return json_encode($this->toArrayIn());
	}

	function toArrayOut() {
		$inputData = array();
		foreach (self::$aliasMapOut as $prop => $alias) {
			if (isset($this->$prop)) {
				$inputData[$alias[0]] = $this->$prop;
				continue;
			}
		}

		foreach ($this->data as $prop => $value) {
			$inputData[$prop] = $value;
		}

		return $inputData;
	}

	function toJsonOut() {
		return json_encode($this->toArrayOut());
	}

	public function __construct($data = null)
	{
		if($data == null) {
			return;
		}

		if (!is_array($data)) {
			throw new \InvalidArgumentException('Parameters() constructor argument must be an array or null');
		}

		$uppercasedData = [];
		// $upperToRegular = [];
		$cribedData = $data;
		foreach($data as $key => $value) {
			if(property_exists($this, $key)) {
				$this->$key = $value;
				unset($cribedData[$key]);
			}
		}

		foreach($cribedData as $key => $value) {
			$uppercasedData[mb_strtoupper($key)] = $value;
			// $upperToRegular[mb_strtoupper($key)] = $key;
		}

		foreach(self::$aliasMapOut as $prop => $aliases) {
			foreach($aliases as $alias) {
				if(isset($uppercasedData[$alias])) {
					$this->$prop = $uppercasedData[$alias];
					unset($uppercasedData[$alias]);
					break;
				}
			}
		}

		foreach($uppercasedData as $key => $value) {
			// $paramName = $upperToRegular[$key];
			$this->$key = $value;
		}
	}

	public static function initWithoutTreatment($data) {
        $self = new self();
		foreach($data as $key => $value) {
			$self->$key = $value;
		}

		return $self;
    }

	public static function digest(\Redsys\Merchant $merchant, $data) {
		$merchantParametersStringB64 = $data["Ds_MerchantParameters"];
		$merchantParametersString = \Redsys\Utils::base64_url_decode_safe($merchantParametersStringB64);
		$merchantParameters = json_decode($merchantParametersString, true);
        
		$params = new \Redsys\Parameters($merchantParameters);

        $computedSignature = \Redsys\Signature::createMerchantSignature($merchant->kc, $merchantParametersStringB64, $params->order, $data["Ds_SignatureVersion"]);
        \Redsys\Signature::checkSignatures($computedSignature, $data["Ds_Signature"]);

        return $params;
	}
}