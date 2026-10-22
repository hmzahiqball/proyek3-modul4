<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeRoleRequest;
use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.users', ['users' => User::orderBy('id')->paginate(20)]);
    }

    public function update(ChangeRoleRequest $request, User $user, RoleManagementService $service): RedirectResponse
    {
        $service->changeRole($request->user(), $user, $request->validated('role'));

        return back()->with('status', 'Permintaan role diproses.');
    }
}
