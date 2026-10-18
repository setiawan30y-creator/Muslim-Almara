<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// @2026-Alifiandra
class IslamicEvent extends Model
{
    protected $fillable = ['title', 'hijri_month', 'hijri_day', 'gregorian_date', 'date_basis', 'description', 'source_id'];

    protected function casts(): array
    {
        return ['gregorian_date' => 'date', 'hijri_month' => 'integer', 'hijri_day' => 'integer'];
    }
}
