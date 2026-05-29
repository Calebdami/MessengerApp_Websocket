<?php
namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\MessageRead as MessageReadEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\MessageReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        // Vérifier accès
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'type'        => 'required|in:text,image,video,audio,file,sticker,emoji',
            'content'     => 'nullable|string|max:10000',
            'file'        => 'nullable|file|max:512000', // 500 Mo max
            'reply_to_id' => 'nullable|exists:messages,id',
            'sticker_url' => 'nullable|string',
        ]);

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'type'            => $request->type,
            'reply_to_id'     => $request->reply_to_id,
        ];

        // Traitement selon le type
        if ($request->type === 'text' || $request->type === 'emoji') {
            $data['content'] = $request->content;

        } elseif ($request->type === 'sticker') {
            $data['content'] = $request->sticker_url;

        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = match($request->type) {
                'image' => 'images',
                'video' => 'videos',
                'audio' => 'audios',
                default => 'files',
            };

            // Upload vers Supabase Storage via l'API REST
            $path = $this->uploadToSupabase($file, $folder);

            $data['content']   = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['file_mime'] = $file->getMimeType();

            // Durée pour audio/video (nécessite ffprobe côté serveur ou metadata JS)
            if ($request->has('duration')) {
                $data['duration'] = (int) $request->duration;
            }

            // Dimensions pour images
            if ($request->type === 'image') {
                [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];
                $data['metadata'] = ['width' => $width, 'height' => $height];
            }
        }

        $message = Message::create($data);

        // Marquer la conversation avec last_message_at
        $conversation->update(['last_message_at' => now()]);

        // Marquer comme lu pour l'expéditeur
        MessageRead::firstOrCreate([
            'message_id' => $message->id,
            'user_id'    => $user->id,
        ], ['read_at' => now()]);

        // Diffuser en temps réel
        event(new MessageSent($message));

        // Notifier les autres participants (hors muted)
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->wherePivot('is_muted', false)
            ->get();

        foreach ($otherParticipants as $participant) {
            $participant->notify(new \App\Notifications\NewMessageNotification($message));
        }

        return response()->json(['success' => true, 'message_id' => $message->id]);
    }

    public function markRead(Conversation $conversation)
    {
        $user = auth()->user();

        // Marquer tous les messages non lus comme lus
        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($unreadMessages as $message) {
            MessageRead::firstOrCreate([
                'message_id' => $message->id,
                'user_id'    => $user->id,
            ], ['read_at' => now()]);
        }

        // Mettre à jour le pivot
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        // Diffuser l'event de lecture
        event(new MessageReadEvent($conversation->id, $user->id, now()->toISOString()));

        return response()->json(['success' => true]);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        event(new UserTyping(
            $conversation->id,
            $user->id,
            $user->name,
            (bool) $request->is_typing
        ));
        return response()->json(['success' => true]);
    }

    public function react(Request $request, Message $message)
    {
        $request->validate(['emoji' => 'required|string']);
        $user = auth()->user();

        $existing = MessageReaction::where([
            'message_id' => $message->id,
            'user_id'    => $user->id,
            'emoji'      => $request->emoji,
        ])->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => $user->id,
                'emoji'      => $request->emoji,
            ]);
            $action = 'added';
        }

        // Re-diffuser le message mis à jour
        $message->load(['sender', 'reactions.user', 'reads']);
        event(new MessageSent($message));

        return response()->json(['success' => true, 'action' => $action]);
    }

    public function edit(Request $request, Message $message)
    {
        $user = auth()->user();
        if ($message->sender_id !== $user->id) abort(403);
        if ($message->type !== 'text') abort(422, 'Seuls les messages texte peuvent être modifiés.');

        $request->validate(['content' => 'required|string|max:10000']);

        $message->update([
            'content'   => $request->content,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        event(new MessageSent($message));

        return response()->json(['success' => true]);
    }

    public function destroy(Message $message)
    {
        $user = auth()->user();
        if ($message->sender_id !== $user->id) abort(403);

        $message->update(['is_deleted' => true, 'content' => null]);
        event(new MessageSent($message));

        return response()->json(['success' => true]);
    }

    public function loadMore(Request $request, Conversation $conversation)
    {
        $user = auth()->user();
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) abort(403);

        $messages = $conversation->messages()
            ->with(['sender:id,name,username,avatar,status', 'replyTo.sender:id,name', 'reactions.user:id,name,avatar'])
            ->where('is_deleted', false)
            ->where('id', '<', $request->before_id)
            ->latest()
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['messages' => $messages, 'has_more' => $messages->count() === 30]);
    }

    private function uploadToSupabase($file, string $folder): string
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.secret');
        $bucket      = config('services.supabase.bucket', 'messenger-files');
        $fileName    = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
        $storagePath = "{$folder}/{$fileName}";
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "Bearer {$supabaseKey}",
            'Content-Type'  => $file->getMimeType(),
        ])->withBody(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        )->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$storagePath}");

        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error('Supabase upload failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \Exception('Upload Supabase échoué : ' . $response->body());
        }

        return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$storagePath}";
    }
}
