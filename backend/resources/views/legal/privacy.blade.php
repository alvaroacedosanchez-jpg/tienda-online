@extends('layouts.app')
@section('title', 'Privacidad y datos')
@section('content')
<article class="legal">
    <h1>Privacidad y datos del prototipo</h1>
    <p class="muted">{{ config('shop.name') }} es un <strong>prototipo académico</strong> de la asignatura Soluciones Informáticas para la Empresa. No tiene actividad comercial real y esta página no es un aviso legal con validez jurídica: solo documenta qué hace el sistema con los datos.</p>

    <h2>Qué es y qué no es</h2>
    <ul>
        <li>No se venden productos ni se envía nada: los pedidos son de demostración.</li>
        <li>La empresa, el CIF y las facturas son <strong>ficticios</strong> y no tienen validez fiscal.</li>
    </ul>

    <h2>Datos que se guardan</h2>
    <ul>
        <li><strong>Cuenta y pedidos:</strong> nombre, correo, dirección de envío y contraseña (cifrada con hash, nunca en claro).</li>
        <li><strong>Eventos de uso:</strong> se registran acciones de la compra (ver un producto, añadir al carrito, iniciar el checkout, crear un pedido, pagar…). Pueden llevar el identificador interno de la cuenta, pero no el nombre, el correo ni la dirección.</li>
        <li><strong>Soporte:</strong> nombre, correo y mensaje que escribas en el formulario.</li>
    </ul>

    <h2>Pagos</h2>
    <p>La tarjeta se introduce en el <strong>TPV de pruebas de Redsys</strong>, no en esta tienda. La tienda nunca recibe el número completo: solo guarda los 4 últimos dígitos que devuelve Redsys. No uses una tarjeta real.</p>

    <h2>Cookies</h2>
    <p>Solo se usan cookies técnicas: la de <strong>sesión</strong> (mantiene el carrito y el inicio de sesión) y la de <strong>protección de formularios</strong> (CSRF). No hay cookies de publicidad ni de analítica de terceros.</p>

    <h2>Correos</h2>
    <p>Se envían correos al registrarte, al pagar un pedido (con la factura en PDF), al enviarse y al contactar con soporte. Usa una dirección propia o de prueba: si introduces el correo de otra persona, le llegarán mensajes.</p>

    <h2>Tus datos y su borrado</h2>
    <p>Desde <strong>Mi cuenta</strong> puedes cambiar tu contraseña y dirección, y darte de baja (si no tienes pedidos en curso). Para cualquier duda, escribe desde <a href="{{ route('support.create') }}">Soporte</a>.</p>
</article>
@endsection
