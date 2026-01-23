<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelLayout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'x_pos',
        'y_pos',
        'font_size',
    ];

    /**
     * Optional: Helper to get a specific coordinate pair.
     */
    public static function getCoords(string $fieldId): array
    {
        $layout = self::where('field_id', $fieldId)->first();
        
        return [
            'x' => $layout->x_pos ?? 65,
            'y' => $layout->y_pos ?? 5,
            's' => $layout->font_size ?? 3,
        ];
    }
}