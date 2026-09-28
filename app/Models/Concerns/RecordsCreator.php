<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * Isi kolom created_by otomatis dari user yang sedang login.
 * Data yang dibuat di background (job) mengisi created_by secara eksplisit.
 */
trait RecordsCreator
{
    public static function bootRecordsCreator(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
