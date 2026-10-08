<?php

return [
    'name' => env('SHOP_NAME', 'Piquantum'),
    'tagline' => 'Tecnología y accesorios para tu escritorio',

    // Los precios del catálogo incluyen IVA.
    'vat_rate' => 0.21,

    'shipping_cost' => 4.95,
    'free_shipping_from' => 60.00,

    // Códigos de descuento simulados: código => porcentaje
    'discount_codes' => [
        'BIENVENIDA10' => 10,
        'ACADEMICO15' => 15,
    ],
];
