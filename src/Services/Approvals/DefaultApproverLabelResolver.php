<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use QuickerFaster\UILibrary\Contracts\Approvals\ApproverLabelResolver;

/**
 * Default implementation of the ApproverLabelResolver contract.
 *
 * Resolves display labels, avatars, and profile routes for approvers using
 * the configured User model. Consuming applications can override this by
 * binding their own implementation against the ApproverLabelResolver contract,
 * or by publishing the ui-library config and pointing
 * 'approvals.approver_label_resolver' at a custom class.
 *
 * The resolver probes a small set of conventional attributes so it works with
 * most User models out of the box:
 *   - label:  name, full_name, email
 *   - avatar: avatar_url, avatar, profile_photo_url, photo
 *   - profile route: none (returned as null by default — override to supply one)
 */
class DefaultApproverLabelResolver implements ApproverLabelResolver
{
    public function label($userId): string
    {
        $user = $this->resolveUser($userId);

        if (! $user) {
            return 'User #' . $userId;
        }

        foreach (['name', 'full_name', 'email'] as $attribute) {
            if (isset($user->{$attribute}) && $user->{$attribute} !== null && $user->{$attribute} !== '') {
                return (string) $user->{$attribute};
            }
        }

        return 'User #' . $userId;
    }

    public function avatar($userId): ?string
    {
        $user = $this->resolveUser($userId);

        if (! $user) {
            return null;
        }

        foreach (['avatar_url', 'avatar', 'profile_photo_url', 'photo'] as $attribute) {
            if (isset($user->{$attribute}) && is_string($user->{$attribute}) && $user->{$attribute} !== '') {
                return $user->{$attribute};
            }
        }

        return null;
    }

    public function profileRoute($userId): ?string
    {
        return null;
    }

    /**
     * Resolve the user model instance for the given identifier.
     *
     * @param int|string $userId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function resolveUser($userId)
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);

        return $userModel::find($userId);
    }
}
