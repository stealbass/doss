<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'document_title',
        'document_category',
        'file_size',
        'source',
        'ip_address',
        'device_type',
        'user_agent',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    /**
     * Relation: Appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Téléchargements depuis l'app mobile
     */
    public function scopeFromMobile($query)
    {
        return $query->where('source', 'mobile_app');
    }

    /**
     * Scope: Téléchargements depuis le web
     */
    public function scopeFromWeb($query)
    {
        return $query->where('source', 'web_chat');
    }

    /**
     * Scope: Téléchargements d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('downloaded_at', today());
    }

    /**
     * Scope: Téléchargements ce mois
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('downloaded_at', now()->year)
                     ->whereMonth('downloaded_at', now()->month);
    }

    /**
     * Taille du fichier formatée
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
