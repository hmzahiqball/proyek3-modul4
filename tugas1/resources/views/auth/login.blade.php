<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; color: #1f2937; }
        main { max-width: 28rem; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: .75rem; box-shadow: 0 4px 16px #0001; }
        h1 { margin-top: 0; } label { display: block; margin-top: 1rem; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: .7rem; margin-top: .35rem; border: 1px solid #d1d5db; border-radius: .4rem; }
        button { width: 100%; margin-top: 1.5rem; padding: .7rem; border: 0; border-radius: .4rem; background: #2563eb; color: white; font-weight: 600; cursor: pointer; }
        .error { padding: .75rem; border-radius: .4rem; background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <main>
        <h1>Login</h1>
        <p>Masuk untuk membuka dashboard</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" value="{{ old('username') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
