<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="/themes/defaulttheme/css/theme.css">
</head>
<body>
    <header>
        <h1>{{ config('app.name') }} - Blog</h1>
        <nav>
            <!-- Header menu will be rendered here -->
        </nav>
    </header>

    <main>
        <h2>Recent Posts</h2>
        <div class="posts">
            @foreach($posts ?? [] as $post)
            <article>
                <h3><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h3>
                <p>{{ Str::limit($post->content, 200) }}</p>
                <small>{{ $post->published_at->format('F d, Y') }}</small>
            </article>
            @endforeach
        </div>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </footer>
</body>
</html>
