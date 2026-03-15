<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CherokeeWord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'seed_id',
        'cherokee',
        'transliteration',
        'ipa',
        'english',
        'category',
        'source_key',
        'source_name',
        'source_kind',
        'source_citation',
        'source_url',
        'source_note',
        'confidence',
        'dialect',
        'notes',
        'alternates',
        'is_user_modified',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternates' => 'array',
            'is_user_modified' => 'boolean',
        ];
    }
}
