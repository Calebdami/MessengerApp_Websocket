<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = ['uuid','conversation_id','initiated_by','type','status','started_at','ended_at','duration'];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function initiator()    { return $this->belongsTo(User::class, 'initiated_by'); }

    public function getDurationHumanAttribute(): string
    {
        if (!$this->duration) return '';
        $m = intdiv($this->duration, 60);
        $s = $this->duration % 60;
        return sprintf('%d:%02d', $m, $s);
    }
}
