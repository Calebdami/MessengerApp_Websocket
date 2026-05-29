<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['user_id','contact_id','is_blocked','is_favorite'];
    protected $casts = ['is_blocked' => 'boolean', 'is_favorite' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function contact() { return $this->belongsTo(User::class, 'contact_id'); }
}
