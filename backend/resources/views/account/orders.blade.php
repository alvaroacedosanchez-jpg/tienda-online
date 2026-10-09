@extends('layouts.app')
@section('title', 'Mis pedidos')
@section('content')
<h1>Mis pedidos</h1>
@include('account.partials.nav')

<h2>En curso</h2>
@include('account.partials.orders-table', ['orders' => $inProgress, 'empty' => 'No tienes pedidos en curso.'])

<h2>Anteriores</h2>
@include('account.partials.orders-table', ['orders' => $past, 'empty' => 'Todavía no tienes pedidos terminados (enviados o cancelados).'])
{{ $past->links() }}
@endsection
