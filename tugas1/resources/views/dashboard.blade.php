<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; color: #1f2937; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
        main { max-width: 46rem; margin: 3rem auto; background: #fff; padding: 2rem; border-radius: .75rem; box-shadow: 0 4px 16px #0001; }
        button { padding: .55rem 1rem; border: 0; border-radius: .4rem; background: #dc2626; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <strong>Dashboard</strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </header>
    <main>
        <h1>Selamat datang, {{ Auth::user()->nama_lengkap }}!</h1>
        <p>Halaman ini hanya bisa dibuka setelah login.</p>
        <p>Username: {{ Auth::user()->username }}</p>
    </main>
</body>
</html>
