<nav class="filters" aria-label="Back-office">
    <a class="chip @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a class="chip @if(request()->routeIs('admin.orders.*')) active @endif" href="{{ route('admin.orders.index') }}">Pedidos</a>
    <a class="chip @if(request()->routeIs('admin.customers.*')) active @endif" href="{{ route('admin.customers.index') }}">Clientes</a>
    <a class="chip @if(request()->routeIs('admin.stock.*')) active @endif" href="{{ route('admin.stock.index') }}">Stock</a>
    <a class="chip @if(request()->routeIs('admin.events.*')) active @endif" href="{{ route('admin.events.index') }}">Eventos</a>
    <a class="chip @if(request()->routeIs('admin.tickets.*')) active @endif" href="{{ route('admin.tickets.index') }}">Incidencias</a>
    <form method="POST" action="{{ route('admin.logout') }}" class="inline-form">@csrf<button class="btn btn-small btn-light" type="submit">Salir</button></form>
</nav>