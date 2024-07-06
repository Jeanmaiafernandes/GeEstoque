<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite('resources/css/app.css')
    <title>@yield('title', 'GeEstoque')</title>

</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>GeEstoque</h1>
                @yield('content')
                <div class="max-w-md  m-24 rounded overflow-hidden shadow-lg">
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/login">Login</a></li>
                        <li><a href="/register">Register</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</body>
</html>
