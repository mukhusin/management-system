<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceLine extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active', 'position'];

    protected $casts = ['active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (ServiceLine $line) {
            if (blank($line->slug)) {
                $line->slug = Str::slug($line->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
