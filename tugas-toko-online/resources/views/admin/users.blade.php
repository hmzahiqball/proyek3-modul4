<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kelola role</title></head>
<body>
<main>
    <h1>Kelola role pengguna</h1>
    <a href="{{ route('products.index') }}">Katalog</a>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @foreach($errors->all() as $error)<p role="alert">{{ $error }}</p>@endforeach
    <table>
        <thead><tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th><th>Perubahan</th></tr></thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td><td>{{ $user->username }}</td><td>{{ $user->nama_lengkap }}</td><td>{{ $user->role }}</td>
                <td>
                    @if($user->role !== 'admin' && ! auth()->user()->is($user))
                        <form method="POST" action="{{ route('admin.users.role.update', $user) }}">
                            @csrf @method('PATCH')
                            <label for="role-{{ $user->id }}">Role {{ $user->username }}</label>
                            <select id="role-{{ $user->id }}" name="role">
                                <option value="customer" @selected($user->role === 'customer')>customer</option>
                                <option value="staff" @selected($user->role === 'staff')>staff</option>
                            </select>
                            <button type="submit">Simpan role</button>
                        </form>
                    @else
                        Akun admin dilindungi.
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $users->links() }}
</main>
</body>
</html>
