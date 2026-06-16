<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="/themes/defaulttheme/css/theme.css">
</head>
<body>
    <header>
        <h1>{{ config('app.name') }}</h1>
        <nav>
            <!-- Header menu will be rendered here -->
        </nav>
    </header>

    <main>
        <article>
            <h1>{{ $page->title }}</h1>
            <div class="content">
                {!! $content !!}
            </div>
        </article>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
