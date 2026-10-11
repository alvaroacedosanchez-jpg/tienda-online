<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiqueta de Envío - {{ $order->reference }}</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .actions-bar {
            margin-bottom: 20px;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        
        /* Contenedor de la etiqueta formato estándar 10x15cm */
        .shipping-label {
            width: 100mm;
            height: 150mm;
            background: #ffffff;
            border: 2px solid #000000;
            padding: 8mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #000000;
        }
        .header {
            border-bottom: 2px solid #000000;
            padding-bottom: 4mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ref-box {
            font-size: 0.9rem;
            text-align: right;
        }
        .section {
            border-bottom: 1px dashed #000000;
            padding: 4mm 0;
        }
        .title {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 2mm;
        }
        .address-box {
            font-size: 0.95rem;
            line-height: 1.3;
        }
        .address-box strong {
            font-size: 1.1rem;
            display: block;
            margin-bottom: 2px;
        }
        .barcode-container {
            text-align: center;
            padding-top: 4mm;
        }
        .barcode {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.8rem;
            letter-spacing: 4px;
            font-weight: bold;
            background: #e5e7eb;
            padding: 8px;
            display: inline-block;
            border: 1px solid #000000;
            margin-bottom: 4px;
        }
        
        /* Ajustes específicos para impresora */
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .actions-bar {
                display: none;
            }
            .shipping-label {
                border: 1px solid #000000;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<div class="actions-bar">
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir Etiqueta</button>
</div>

<div class="shipping-label">
    <div class="header">
        <div class="brand">{{ config('shop.name', 'Piquantum') }}</div>
        <div class="ref-box">
            <strong>URGENTE 24H</strong><br>
            {{ $order->created_at->format('d/m/Y') }}
        </div>
    </div>

    <!-- REMITENTE -->
    <div class="section">
        <div class="title">Remitente</div>
        <div class="address-box" style="font-size: 0.8rem;">
            <strong>{{ config('shop.name', 'Piquantum Logística') }}</strong>
            Polígono Industrial El Palmar, Calle B, NAVE 4<br>
            30120 Murcia, España
        </div>
    </div>

    <!-- DESTINATARIO -->
    <div class="section" style="flex-grow: 1;">
        <div class="title">Destinatario</div>
        <div class="address-box">
            <strong>{{ $order->shipping_name }}</strong>
            {{ $order->shipping_address }}<br>
            {{ $order->shipping_postal_code }} - {{ $order->shipping_city }}<br>
            España
        </div>
    </div>

    <!-- DATOS DEL PAQUETE -->
    <div class="section">
        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
            <span><strong>Bultos:</strong> 1/1</span>
            <span><strong>Artículos:</strong> {{ $order->items->sum('quantity') }} uds.</span>
            <span><strong>Ref:</strong> {{ $order->reference }}</span>
        </div>
    </div>

    <!-- CÓDIGO DE BARRAS / SEGUIMIENTO -->
    <div class="barcode-container">
        <div class="barcode">*{{ $order->reference }}*</div>
        <div style="font-size: 0.75rem;">CÓDIGO DE SEGUIMIENTO NACIONAL</div>
    </div>
</div>

</body>
</html>