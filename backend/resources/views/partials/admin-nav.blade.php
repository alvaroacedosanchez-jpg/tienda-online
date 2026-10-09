<nav class="filters" aria-label="Back-office">
    <a class="chip" href="{{ route('admin.orders.index') }}">Pedidos</a>
    <a class="chip" href="{{ route('admin.stock.index') }}">Stock</a>
    <a class="chip" href="{{ route('admin.events.index') }}">Eventos</a>
    <a class="chip" href="{{ route('admin.tickets.index') }}">Incidencias</a>
    <form method="POST" action="{{ route('admin.logout') }}" class="inline-form">@csrf<button class="btn btn-small btn-light" type="submit">Salir</button></form>
</nav>
