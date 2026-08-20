<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    private function client(): Client
    {
        return Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);
    }

    public function index()
    {
        $client = $this->client();

        return Inertia::render('Tenant/Users', [
            'users' => User::where('tenant_id', tenant('id'))
                ->latest()
                ->get(['id', 'name', 'email', 'role', 'created_at'])
                ->map(fn($u) => [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'role'       => $u->role,
                    'created_at' => $u->created_at->format('d M Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $client = $this->client();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make(Str::random(32)),
            'role'      => 'admin',
            'tenant_id' => tenant('id'),
            'client_id' => $client->id,
        ]);

        // Send password setup invitation
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', "Invitation sent to {$data['email']}.");
    }

    public function destroy(User $user)
    {
        // Can't delete yourself or users from other tenants
        abort_if($user->tenant_id !== tenant('id'), 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');

        $user->delete();

        return back()->with('success', 'User removed.');
    }
}
