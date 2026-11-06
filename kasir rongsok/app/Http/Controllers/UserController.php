<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::withCount(['purchaseTransactions', 'salesTransactions'])->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_DRIVER])],
            'phone' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_name' => 'nullable|string|max:150',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;

        $user = User::create($data);

        ActivityLog::record(
            action: 'user_created',
            subject: $user,
            description: "Menambahkan user baru: {$user->name} ({$user->role})"
        );

        return back()->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_DRIVER])],
            'phone' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_name' => 'nullable|string|max:150',
            'password' => 'nullable|string|min:6',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        // Prevent locking out all super admins
        if ($user->id === Auth::id() && !$data['is_active']) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update($data);

        ActivityLog::record(
            action: 'user_updated',
            subject: $user,
            description: "Memperbarui profil pengguna: {$user->name}"
        );

        return back()->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->purchaseTransactions()->exists() || $user->salesTransactions()->exists()) {
            return back()->with('error', "Pengguna {$user->name} memiliki riwayat transaksi dan tidak dapat dihapus. Anda dapat menonaktifkannya.");
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record(
            action: 'user_deleted',
            description: "Menghapus pengguna: {$name}"
        );

        return back()->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}

