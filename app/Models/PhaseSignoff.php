<?php

namespace App\Models;

use App\Enums\ProjectPhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseSignoff extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_id', 'phase', 'signed_by', 'note', 'forced', 'signed_at'];

    protected $casts = [
        'phase' => ProjectPhase::class,
        'forced' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
