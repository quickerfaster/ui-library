<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserActionHistory records each quick action a user executes.
 *
 * The user model is resolved via the library's configured user model
 * (config('ui-library.user.model')), falling back to the consuming app's
 * default App\Models\User.
 */
class UserActionHistory extends Model
{
    protected $table = 'user_action_histories';

    protected $fillable = [
        'user_id',
        'action_id',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}
