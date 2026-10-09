<article class="card product-card">
    <a href="{{ route('product.show', $product) }}" class="thumb" aria-label="{{ $product->name }}">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="" width="130" height="130" loading="lazy">
        @else
            {{ $product->emoji }}
        @endif
    </a>
    <div class="card-body">
        <p class="muted small">{{ $product->category->name }}</p>
        <h3><a href="{{ route('product.show', $product) }}">{{ $product->name }}</a></h3>
        <p class="muted">{{ $product->short_description }}</p>
        <p class="price">{{ number_format($product->price, 2, ',', '.') }} €</p>
    </div>
</article>
