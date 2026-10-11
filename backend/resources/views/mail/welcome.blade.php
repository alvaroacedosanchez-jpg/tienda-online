<x-mail::message>
# Hola, {{ $user->name }}

Tu cuenta en **{{ config('shop.name') }}** se ha creado correctamente. Ya puedes guardar tu carrito, comprar y consultar tus pedidos y facturas.

<x-mail::button :url="route('catalog')">
Ver el catálogo
</x-mail::button>

Desde [Mi cuenta]({{ route('account.show') }}) puedes ver tus pedidos, descargar tus facturas y cambiar tu dirección o tu contraseña.

Gracias,<br>
{{ config('shop.name') }}

@include('mail._prototype')
</x-mail::message>
