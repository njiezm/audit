<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Administration des comptes. Protégé par le middleware `admin`. */
class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::withCount('audits')->orderBy('name')->paginate(20),
            'roles' => UserRole::options(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User, 'roles' => UserRole::options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        ActivityLog::record('user_created', $user, $user->email);

        return redirect()->route('users.index')->with('success', 'Compte créé pour '.$user->name.'.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => UserRole::options()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'job_title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        // Garde-fou : un administrateur ne peut pas se retirer ses propres
        // droits ni se désactiver, sinon l'instance devient inadministrable.
        if ($user->id === Auth::id()) {
            $data['role'] = $user->role->value;
            $data['is_active'] = true;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Compte mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if (User::where('role', UserRole::Admin->value)->where('is_active', true)->count() <= 1 && $user->isAdmin()) {
            return back()->with('error', 'Il doit rester au moins un administrateur actif.');
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return back()->with('success', 'Compte désactivé et archivé.');
    }

    public function activity(): View
    {
        return view('users.activity', [
            'logs' => ActivityLog::with('user')->latest()->paginate(50),
        ]);
    }
}
