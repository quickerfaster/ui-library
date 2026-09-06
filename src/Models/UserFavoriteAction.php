<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserFavoriteAction stores a user's pinned/favorite quick actions.
 *
 * Pinned actions always appear at the top of the palette, ⚡ dropdown,
 * and dashboard widget, above ranked actions.
 */
class UserFavoriteAction extends Model
{
    protected $table = 'user_favorite_actions';

    protected $fillable = [
        'user_id',
        'action_id',
    ];

    /**
     * Get the user who favorited the action.
     */
    public function user(): BelongsTo
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}