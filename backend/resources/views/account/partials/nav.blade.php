<nav class="filters" aria-label="Mi cuenta">
    <a class="chip @if (request()->routeIs('account.show')) active @endif" href="{{ route('account.show') }}" @if (request()->routeIs('account.show')) aria-current="page" @endif>Resumen</a>
    <a class="chip @if (request()->routeIs('account.orders')) active @endif" href="{{ route('account.orders') }}" @if (request()->routeIs('account.orders')) aria-current="page" @endif>Mis pedidos</a>
    <a class="chip @if (request()->routeIs('account.invoices')) active @endif" href="{{ route('account.invoices') }}" @if (request()->routeIs('account.invoices')) aria-current="page" @endif>Mis facturas</a>
</nav>
