<?php

namespace App\Models\Traits;

use App\Models\User;

trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $userId = auth()->id();
                $model->created_by ??= $userId;
                $model->updated_by ??= $userId;
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                if (method_exists($model, 'runSoftDelete')) {
                    $model->save();
                }
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

