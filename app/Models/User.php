<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','username','email','password','avatar','bio','phone',
        'status','last_seen_at','show_online_status','allow_calls',
        'notifications_enabled','theme','language',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'last_seen_at'           => 'datetime',
        'show_online_status'     => 'boolean',
        'allow_calls'            => 'boolean',
        'notifications_enabled'  => 'boolean',
        'password'               => 'hashed',
    ];

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('role','is_muted','is_archived','is_pinned','last_read_at')
            ->withTimestamps()
            ->orderByDesc('last_message_at');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class)->where('is_blocked', false);
    }

    public function blockedUsers()
    {
        return $this->hasMany(Contact::class)->where('is_blocked', true);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff&size=128';
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->status === 'online';
    }

    public function getLastSeenTextAttribute(): string
    {
        if ($this->status === 'online') return 'En ligne';
        if (!$this->last_seen_at) return 'Hors ligne';
        return 'Vu ' . $this->last_seen_at->diffForHumans();
    }
}
