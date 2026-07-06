<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    protected $fillable = [
        'title',
        'url',
        'username',
        'password',
        'notes',
        'created_by',
    ];

    protected $casts = [
        // Encrypted at rest using the app's APP_KEY — DB access alone can't read them.
        'username' => 'encrypted',
        'password' => 'encrypted',
        'notes' => 'encrypted',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
