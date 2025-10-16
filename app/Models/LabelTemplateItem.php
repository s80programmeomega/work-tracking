<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_template_id',
        'nom',
        'couleur',
        'description',
        'ordre',
    ];

    /**
     * Get the template this item belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(LabelTemplate::class, 'label_template_id');
    }

    /**
     * Calculate text color based on background color for readability
     */
    public function getTextColorAttribute(): string
    {
        $hex = ltrim($this->couleur, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return white for dark colors, black for light colors
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }
}
