<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\PermissionOverride;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(30)]);
    }

    public function create()
    {
        return view('users.form', [
            'user' => new User(['role' => UserRole::DevMember]),
            'catalog' => config('permissions.catalog'),
            'roleDefaults' => config('permissions.roles'),
            'overrides' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        $tempPassword = Str::password(14);
        $user = User::create([...$data, 'password' => $tempPassword]);
        $this->syncOverrides($request, $user);

        return redirect()->route('users.index')
            ->with('status', "Account created for {$user->name}. Temporary password: {$tempPassword}");
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'user' => $user,
            'catalog' => config('permissions.catalog'),
            'roleDefaults' => config('permissions.roles'),
            'overrides' => $user->overrides()->pluck('granted', 'permission')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(UserRole::values())],
        ]);

        // Never leave the system without an admin.
        if ($user->isAdmin() && $data['role'] !== UserRole::SystemAdmin->value
            && User::where('role', UserRole::SystemAdmin->value)->count() <= 1) {
            return back()->with('error', 'You cannot remove the last system administrator.');
        }

        $user->update($data);
        $this->syncOverrides($request, $user);

        return redirect()->route('users.index')->with('status', 'Account updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdmin() && User::where('role', UserRole::SystemAdmin->value)->count() <= 1) {
            return back()->with('error', 'You cannot delete the last system administrator.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Account deleted.');
    }

    /**
     * Persist only the checkboxes that differ from the role default:
     * a box ticked against a role that doesn't grant it → grant override;
     * unticked against a role that does grant it → revoke override.
     */
    private function syncOverrides(Request $request, User $user): void
    {
        $checked = collect($request->input('permissions', []))->flip();
        $defaults = config('permissions.roles.'.$user->role->value, []);
        $grantsAll = in_array('*', $defaults, true);

        $user->overrides()->delete();

        foreach (array_keys(config('permissions.catalog')) as $key) {
            $roleGrants = $grantsAll || in_array($key, $defaults, true);
            $wantGranted = $checked->has($key);

            if ($roleGrants !== $wantGranted) {
                PermissionOverride::create([
                    'user_id' => $user->id,
                    'permission' => $key,
                    'granted' => $wantGranted,
                ]);
            }
        }
    }
}
