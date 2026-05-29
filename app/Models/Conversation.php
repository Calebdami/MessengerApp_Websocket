<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['type','name','avatar','description','created_by','last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('role','is_muted','is_archived','is_pinned','last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest()->where('is_deleted', false);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /** Nom affiché selon le type et l'interlocuteur */
    public function getDisplayNameFor(User $user): string
    {
        if ($this->type === 'group') return $this->name ?? 'Groupe';
        $other = $this->participants->where('id', '!=', $user->id)->first();
        return $other?->name ?? 'Inconnu';
    }

    /** Avatar affiché */
    public function getDisplayAvatarFor(User $user): string
    {
        if ($this->type === 'group') {
            return $this->avatar
                ? asset('storage/' . $this->avatar)
                : 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'G') . '&background=8b5cf6&color=fff';
        }
        $other = $this->participants->where('id', '!=', $user->id)->first();
        return $other?->avatar_url ?? '';
    }

    /** Nb de messages non lus pour un utilisateur */
    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participants->where('id', $user->id)->first()?->pivot;
        $lastRead = $pivot?->last_read_at;

        $query = $this->messages()->where('sender_id', '!=', $user->id)->where('is_deleted', false);
        if ($lastRead) $query->where('created_at', '>', $lastRead);
        return $query->count();
    }
}
