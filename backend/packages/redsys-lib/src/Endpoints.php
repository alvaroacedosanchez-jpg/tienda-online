<?php

namespace Redsys;

class Endpoints {
    public static $redirect = array(
        "DESA" => "https://sis-d.redsys.es/sis/realizarPago",
        "TEST" => "https://sis-t.redsys.es:25443/sis/realizarPago",
        "INTE" => "https://sis-i.redsys.es:25443/sis/realizarPago",
        "PROD" => "https://sis.redsys.es/sis/realizarPago",
    );
    
    public static $init = array(
        "DESA" => "https://sis-d.redsys.es/sis/rest/iniciaPeticionREST",
        "INTE" => "https://sis-i.redsys.es:25443/sis/rest/iniciaPeticionREST",
        "TEST" => "https://sis-t.redsys.es:25443/sis/rest/iniciaPeticionREST",
        "PROD" => "https://sis.redsys.es/sis/rest/iniciaPeticionREST",
    );

    public static $treat = array(
        "DESA" => "https://sis-d.redsys.es/sis/rest/trataPeticionREST",
        "INTE" => "https://sis-i.redsys.es:25443/sis/rest/trataPeticionREST",
        "TEST" => "https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST",
        "PROD" => "https://sis.redsys.es/sis/rest/trataPeticionREST",
    );

    public static $query = array(
        "DESA" => "https://apis-d.redsys.es:20443/acquirement/commerces-channel/no-presencial/v1/operation/search",
        "INTE" => "https://apis-i.redsys.es:20443/acquirement/commerces-channel/no-presencial/v1/operation/search",
        "PROD" => "https://apis.redsys.es/acquirement/commerces-channel/no-presencial/v1/operation/search",
    );
}

?>