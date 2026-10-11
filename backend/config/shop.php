<?php

return [
    'name' => env('SHOP_NAME', 'Piquantum'),
    'tagline' => 'Salsas picantes artesanas de todo el mundo',

    // Los precios del catálogo incluyen IVA.
    'vat_rate' => 0.21,

    'shipping_cost' => 4.95,
    'free_shipping_from' => 60.00,

    // Buzón de la tienda que recibe los avisos internos (p. ej. nuevas solicitudes de soporte)
    'notify_email' => env('SHOP_NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Datos de la empresa FICTICIA que aparecen en las facturas (prototipo académico)
    'company' => [
        'legal_name' => 'Piquantum Salsas S.L. (empresa ficticia)',
        'tax_id' => 'B00000000 (CIF ficticio)',
        'address' => 'Calle Inventada 10, 30001 Murcia',
        'email' => 'facturacion@piquantum.test',
    ],

    // Códigos de descuento simulados: código => porcentaje
    'discount_codes' => [
        'BIENVENIDA10' => 10,
        'ACADEMICO15' => 15,
    ],
];
