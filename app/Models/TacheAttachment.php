<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tache_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'original_name',
        'uploaded_by',
    ];

    protected $appends = ['file_url'];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('uploads/' . $this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}