<?php

namespace Redsys;

class Response implements \JsonSerializable
{
    public $httpCode = null;

    public $headers = null;

    public $raw = null;

    public ?\Redsys\Parameters $data = null;

    public $errorCode = null;

    public $code = null;

    public $description = null;

    public function jsonSerialize(): mixed {
		return get_object_vars($this);
	}
}