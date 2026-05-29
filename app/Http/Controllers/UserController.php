<?php
namespace App\Http\Controllers;

use App\Events\UserStatusChanged;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $users = User::where('id', '!=', auth()->id())
            ->where(fn($query) => $query
                ->where('name', 'ilike', "%{$q}%")
                ->orWhere('username', 'ilike', "%{$q}%")
                ->orWhere('email', 'ilike', "%{$q}%"))
            ->select('id','name','username','avatar','status','bio')
            ->limit(20)
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'username'   => $u->username,
                'avatar_url' => $u->avatar_url,
                'status'     => $u->status,
                'bio'        => $u->bio,
            ]);

        return response()->json($users);
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status' => 'required|in:online,offline,busy,away']);
        $user = auth()->user();
        $user->update(['status' => $request->status, 'last_seen_at' => now()]);
        event(new UserStatusChanged($user->id, $request->status, now()->toISOString()));
        return response()->json(['success' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'bio'      => 'nullable|string|max:300',
            'phone'    => 'nullable|string|max:20',
            'avatar'   => 'nullable|image|max:5120',
        ]);

        $data = $request->only(['name', 'username', 'bio', 'phone']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);
        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe incorrect.']);
        }

        $user->update(['password' => $request->password]);
        return back()->with('success', 'Mot de passe modifié.');
    }

    public function updatePreferences(Request $request)
    {
        $request->validate([
            'theme'                  => 'nullable|in:dark,light,system',
            'language'               => 'nullable|string',
            'show_online_status'     => 'boolean',
            'allow_calls'            => 'boolean',
            'notifications_enabled'  => 'boolean',
        ]);

        auth()->user()->update($request->only([
            'theme','language','show_online_status','allow_calls','notifications_enabled'
        ]));

        return back()->with('success', 'Préférences enregistrées.');
    }

    public function settings()
    {
        return Inertia::render('Settings/Index', [
            'authUser' => auth()->user()->only([
                'id','name','username','email','bio','phone','avatar',
                'status','theme','language','show_online_status','allow_calls','notifications_enabled',
            ]) + ['avatar_url' => auth()->user()->avatar_url],
        ]);
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->limit(50)->get();
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        return response()->json($notifications);
    }
}
