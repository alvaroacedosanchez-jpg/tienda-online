<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio del proceso de compra (carrito vacío, sin stock, pedido no pagable...).
 * Su mensaje se puede mostrar al cliente. Cualquier otra excepción es un error técnico.
 */
class CheckoutException extends RuntimeException {}
