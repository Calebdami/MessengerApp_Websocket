<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $conversations = $user->conversations()
            ->with([
                'participants:id,name,username,avatar,status,last_seen_at,show_online_status',
                'lastMessage.sender:id,name',
            ])
            ->wherePivot('is_archived', false)
            ->get()
            ->map(fn($c) => $this->formatConversation($c, $user));

        $archivedCount = $user->conversations()
            ->wherePivot('is_archived', true)
            ->count();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'archivedCount' => $archivedCount,
            'authUser'      => $this->formatUser($user),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        // Vérifier accès
        $participant = $conversation->participants()->where('user_id', $user->id)->first();
        if (!$participant) abort(403);

        $messages = $conversation->messages()
            ->with(['sender:id,name,username,avatar,status', 'replyTo.sender:id,name', 'reactions.user:id,name,avatar', 'reads:message_id,user_id,read_at'])
            ->where('is_deleted', false)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        // Marquer comme lu
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $participants = $conversation->participants()
            ->select('users.id','users.name','users.username','users.avatar','users.status','users.last_seen_at','users.bio')
            ->get();

        return Inertia::render('Chat/Show', [
            'conversation'  => $this->formatConversation($conversation, $user),
            'messages'      => $messages,
            'participants'  => $participants->map(fn($p) => $this->formatUser($p)),
            'authUser'      => $this->formatUser($user),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $user = auth()->user();
        $other = User::findOrFail($request->user_id);

        // Chercher si conversation directe existe déjà
        $existing = $user->conversations()
            ->where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $other->id))
            ->first();

        if ($existing) {
            return redirect()->route('conversations.show', $existing);
        }

        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $user->id,
        ]);

        $conversation->participants()->attach([
            $user->id  => ['role' => 'admin'],
            $other->id => ['role' => 'member'],
        ]);

        return redirect()->route('conversations.show', $conversation);
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $user = auth()->user();

        $conversation = Conversation::create([
            'type'       => 'group',
            'name'       => $request->name,
            'description'=> $request->description,
            'created_by' => $user->id,
        ]);

        $participants = [$user->id => ['role' => 'admin']];
        foreach ($request->user_ids as $uid) {
            $participants[$uid] = ['role' => 'member'];
        }
        $conversation->participants()->attach($participants);

        return redirect()->route('conversations.show', $conversation);
    }

    public function archive(Conversation $conversation)
    {
        $user = auth()->user();
        $conversation->participants()->updateExistingPivot($user->id, ['is_archived' => true]);
        return back();
    }

    public function pin(Conversation $conversation)
    {
        $user = auth()->user();
        $pivot = $conversation->participants()->where('user_id', $user->id)->first()?->pivot;
        $conversation->participants()->updateExistingPivot($user->id, ['is_pinned' => !$pivot?->is_pinned]);
        return back();
    }

    public function mute(Conversation $conversation)
    {
        $user = auth()->user();
        $pivot = $conversation->participants()->where('user_id', $user->id)->first()?->pivot;
        $conversation->participants()->updateExistingPivot($user->id, ['is_muted' => !$pivot?->is_muted]);
        return back();
    }

    private function formatConversation(Conversation $c, $user): array
    {
        $pivot = $c->participants->where('id', $user->id)->first()?->pivot;
        return [
            'id'            => $c->id,
            'type'          => $c->type,
            'name'          => $c->getDisplayNameFor($user),
            'avatar'        => $c->getDisplayAvatarFor($user),
            'description'   => $c->description,
            'participants'  => $c->participants->map(fn($p) => $this->formatUser($p)),
            'last_message'  => $c->lastMessage ? [
                'content'       => $c->lastMessage->display_content,
                'sender_name'   => $c->lastMessage->sender?->name,
                'created_at'    => $c->lastMessage->created_at->toISOString(),
                'sender_id'     => $c->lastMessage->sender_id,
            ] : null,
            'unread_count'  => $c->unreadCountFor($user),
            'last_message_at' => $c->last_message_at?->toISOString(),
            'is_muted'      => (bool) $pivot?->is_muted,
            'is_pinned'     => (bool) $pivot?->is_pinned,
            'is_archived'   => (bool) $pivot?->is_archived,
        ];
    }

    private function formatUser($user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->username,
            'avatar_url'  => $user->avatar_url,
            'status'      => $user->status,
            'last_seen'   => $user->last_seen_text,
            'bio'         => $user->bio ?? '',
        ];
    }
}
