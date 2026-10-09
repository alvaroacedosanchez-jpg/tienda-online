<?php

return [
    'name' => env('SHOP_NAME', 'Piquantum'),
    'tagline' => 'Salsas picantes artesanas de todo el mundo',

    // Los precios del catálogo incluyen IVA.
    'vat_rate' => 0.21,

    'shipping_cost' => 4.95,
    'free_shipping_from' => 60.00,

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
