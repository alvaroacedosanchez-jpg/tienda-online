<?php

namespace Redsys;

class Merchant
{
    public $fuc;

    public $terminal;

    public $kc;

    public $env;
    
    public $redsysClientId;
    
    public $redsysClientSecret;

    public function __construct($fuc, $terminal, $kc, $env) {
        $this->fuc = $fuc;
        $this->terminal = intval($terminal);
        $this->kc = $kc;
        $this->env = $env;
    }

    public static function initWithApiKey($apiKey) {
        if(empty($apiKey)) {
            return;
        }

        $aux = explode("_", $apiKey);
        if(count($aux) != 2 || strlen($aux[0]) != 4) {
            return;
        }

        $aux2 = explode("_", \Redsys\Utils::base64_url_decode_safe($aux[1]));
        return new self($aux2[0], $aux2[1], $aux2[2], $aux[0]);
    }

    public static function initWithParams($fuc, $terminal, $kc, $env) {
        return new self($fuc, $terminal, $kc, $env);
    }

    public function base64url_decode($data) {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($data);
    }

    /**
     * Get the value of fuc
     */ 
    public function getFuc()
    {
        return $this->fuc;
    }

    /**
     * Set the value of fuc
     *
     * @return  self
     */ 
    public function setFuc($fuc)
    {
        $this->fuc = $fuc;
    }

    /**
     * Get the value of terminal
     */ 
    public function getTerminal()
    {
        return $this->terminal;
    }

    /**
     * Set the value of terminal
     *
     * @return  self
     */ 
    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    /**
     * Get the value of kc
     */ 
    public function getKc()
    {
        return $this->kc;
    }

    /**
     * Set the value of kc
     *
     * @return  self
     */ 
    public function setKc($kc)
    {
        $this->kc = $kc;
    }

    /**
     * Get the value of env
     */ 
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set the value of env
     *
     * @return  self
     */ 
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Get the value of redsysClientId
     */ 
    public function getRedsysClientId()
    {
        return $this->redsysClientId;
    }

    /**
     * Set the value of redsysClientId
     *
     * @return  self
     */ 
    public function setRedsysClientId($redsysClientId)
    {
        $this->redsysClientId = $redsysClientId;
    }

    /**
     * Get the value of redsysClientSecret
     */ 
    public function getRedsysClientSecret()
    {
        return $this->redsysClientSecret;
    }

    /**
     * Set the value of redsysClientSecret
     *
     * @return  self
     */ 
    public function setRedsysClientSecret($redsysClientSecret)
    {
        $this->redsysClientSecret = $redsysClientSecret;
    }
}