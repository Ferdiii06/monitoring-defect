<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KanbanComponent extends Model
{
    use HasFactory;

    protected $table = 'kanban_components';

    protected $fillable = [
        'component_key',
        'label',
        'sequence',
        'delimiter',
    ];

    public const KEY_ASSY_NUMBER = 'assy_number';
    public const KEY_CONVEYOR = 'conveyor';
    public const KEY_SERIAL = 'serial';

    public const ALLOWED_KEYS = [
        self::KEY_ASSY_NUMBER,
        self::KEY_CONVEYOR,
        self::KEY_SERIAL,
    ];

    public const ALLOWED_DELIMITERS = [
        '.' => 'Titik (.)',
        ' ' => 'Spasi ( )',
    ];

    /**
     * Scope order by sequence asc
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence', 'asc');
    }
}

