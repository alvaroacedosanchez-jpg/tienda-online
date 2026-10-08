@extends('layouts.app')
@section('title', 'Catálogo')
@section('content')
<h1>Catálogo</h1>
<nav class="filters" aria-label="Categorías">
    <a href="{{ route('catalog') }}" @class(['chip', 'active' => ! $current])>Todas</a>
    @foreach ($categories as $category)
        <a href="{{ route('catalog', ['categoria' => $category->slug]) }}" @class(['chip', 'active' => $current === $category->slug])>{{ $category->name }}</a>
    @endforeach
</nav>

@if ($products->isEmpty())
    <p>No hay productos en esta categoría.</p>
@else
    <div class="grid grid-3">
        @foreach ($products as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
@endif
@endsection
