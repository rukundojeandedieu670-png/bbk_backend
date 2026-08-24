<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private const MANAGED_ROLES = ['system-owner', 'admin', 'publisher'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManagement($request);

        return response()->json(['data' => User::query()->with('roles')->latest()->get()->map(fn (User $user): array => $this->payload($user))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'role' => ['required', Rule::in(self::MANAGED_ROLES)],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);
        $user->assignRole($data['role']);

        return response()->json(['data' => $this->payload($user->fresh()), 'message' => 'Staff account created.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeManagement($request);
        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:12', 'max:255'],
            'role' => ['sometimes', Rule::in(self::MANAGED_ROLES)],
        ]);

        $attributes = collect($data)->except('role')->all();
        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }
        if ($attributes !== []) {
            $user->update($attributes);
        }
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json(['data' => $this->payload($user->fresh()), 'message' => 'Staff account updated.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeManagement($request);
        abort_if($request->user()->id === $id, 422, 'You cannot delete your own account.');
        $user = User::query()->findOrFail($id);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Staff account deleted.']);
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()?->can('manage-users'), 403);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
