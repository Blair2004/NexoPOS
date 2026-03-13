<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="/themes/defaulttheme/css/theme.css">
</head>
<body>
    <header>
        <h1>{{ config('app.name') }} - Store</h1>
        <nav>
            <!-- Header menu will be rendered here -->
        </nav>
    </header>

    <main>
        <h2>Our Products</h2>
        <div class="products-grid">
            @foreach($products ?? [] as $product)
            <div class="product-card">
                <img src="{{ $product->thumbnail ?? '/placeholder.png' }}" alt="{{ $product->name }}">
                <h3><a href="/store/product/{{ $product->id }}">{{ $product->name }}</a></h3>
                <p class="price">${{ number_format($product->price, 2) }}</p>
                <button>Add to Cart</button>
            </div>
            @endforeach
        </div>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
