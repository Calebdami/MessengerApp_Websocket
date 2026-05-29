<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id','sender_id','reply_to_id','type',
        'content','file_name','file_size','file_mime','duration',
        'metadata','is_edited','edited_at','is_deleted',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'is_edited'  => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at'  => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with('sender:id,name,avatar');
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class)->with('user:id,name,avatar');
    }

    /** Formater le contenu affiché (supprimé, édité...) */
    public function getDisplayContentAttribute(): string
    {
        if ($this->is_deleted) return '🚫 Message supprimé';
        return match($this->type) {
            'image'   => '📷 Photo',
            'video'   => '🎥 Vidéo',
            'audio'   => '🎵 Audio',
            'file'    => '📎 ' . ($this->file_name ?? 'Fichier'),
            'sticker' => '🎭 Sticker',
            'call'    => '📞 ' . ($this->content ?? 'Appel'),
            'system'  => '⚙️ ' . $this->content,
            default   => $this->content ?? '',
        };
    }

    /** URL du fichier media */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->content) return null;
        if (str_starts_with($this->content, 'http')) return $this->content;
        return asset('storage/' . $this->content);
    }

    /** Taille humaine */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) return '';
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = $this->file_size;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) { $size /= 1024; $i++; }
        return round($size, 1) . ' ' . $units[$i];
    }
}
