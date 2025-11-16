<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'parent_id',
        'note',
        'created_by',
    ];

    /**
     * Get the case that owns the note
     */
    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    /**
     * Get the user who created the note
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent note (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(CaseNote::class, 'parent_id');
    }

    /**
     * Get the replies for this note
     */
    public function replies()
    {
        return $this->hasMany(CaseNote::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Check if this note is a reply
     */
    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get all main notes (not replies) for a case
     */
    public static function getMainNotes($case_id)
    {
        return self::where('case_id', $case_id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
