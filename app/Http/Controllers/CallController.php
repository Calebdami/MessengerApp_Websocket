<?php
namespace App\Http\Controllers;

use App\Events\CallInitiated;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function initiate(Request $request, Conversation $conversation)
    {
        $request->validate(['type' => 'required|in:audio,video', 'signal' => 'required|string']);

        $user = auth()->user();
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) abort(403);

        $call = Call::create([
            'uuid'           => Str::uuid(),
            'conversation_id'=> $conversation->id,
            'initiated_by'   => $user->id,
            'type'           => $request->type,
            'status'         => 'ringing',
        ]);

        event(new CallInitiated($call, $request->signal));

        return response()->json(['call_uuid' => $call->uuid]);
    }

    public function signal(Request $request, string $uuid)
    {
        $request->validate(['signal' => 'required|string', 'status' => 'nullable|string']);
        $call = Call::where('uuid', $uuid)->firstOrFail();

        if ($request->status) {
            $updates = ['status' => $request->status];
            if ($request->status === 'active')  $updates['started_at'] = now();
            if ($request->status === 'ended' || $request->status === 'declined') {
                $updates['ended_at'] = now();
                if ($call->started_at) {
                    $updates['duration'] = now()->diffInSeconds($call->started_at);
                }
                // Créer un message système dans la conversation
                $text = match($request->status) {
                    'declined' => '📞 Appel refusé',
                    'ended'    => '📞 Appel terminé (' . (isset($updates['duration']) ? gmdate('i:s', $updates['duration']) : '0:00') . ')',
                    default    => '📞 Appel manqué',
                };
                Message::create([
                    'conversation_id' => $call->conversation_id,
                    'sender_id'       => $call->initiated_by,
                    'type'            => 'call',
                    'content'         => $text,
                ]);
            }
            $call->update($updates);
        }

        event(new CallInitiated($call, $request->signal));

        return response()->json(['success' => true]);
    }
}
