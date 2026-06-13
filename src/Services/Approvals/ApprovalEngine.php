<?php

namespace QuickerFaster\UILibrary\Services\Approvals;

use App\Modules\System\Models\ApprovalRequest;
use App\Modules\System\Models\ApprovalTier;
use App\Modules\System\Models\ApprovalLog;
use App\Modules\System\Models\ApprovalTierApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Services\Config\Approvals\ApprovalConfigResolver;


class ApprovalEngine
{
    protected ApprovalConfigResolver $configResolver;

    public function __construct(ApprovalConfigResolver $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * Start a new approval request for the given model.
     *
     * @param mixed $approvable The model instance (must have an 'id')
     * @param \App\Models\User|null $submitter
     * @return ApprovalRequest
     * @throws \Exception
     */
    public function startApproval($approvable, $submitter = null): ApprovalRequest
    {
        if (!$submitter) {
            $submitter = Auth::user();
        }

        // Prevent duplicate active approval
        if ($this->hasActiveApproval($approvable)) {
            throw new \Exception('This record already has a pending approval request.');
        }

        DB::transaction(function () use ($approvable, $submitter, &$request) {
            // Create the approval request
            $request = ApprovalRequest::create([
                'approvable_type' => get_class($approvable),
                'approvable_id'   => $approvable->id,
                'status'          => 'pending',
                'submitted_by'    => $submitter?->id,
                'submitted_at'    => now(),
            ]);

            // Create tiers from config
            $tiers = $this->configResolver->getTiers();
            $sequence = 1;
            $previousTierId = null;

            foreach ($tiers as $tierConfig) {
                $tier = ApprovalTier::create([
                    'approval_request_id' => $request->id,
                    'tier_type'           => $tierConfig['type'],
                    'sequence'            => $sequence++,
                    'name'                => $tierConfig['name'],
                    'roles'               => $tierConfig['roles'] ?? [],
                    'approval_mode'       => $tierConfig['approval_mode'] ?? 'any',
                    'status'              => 'pending',
                ]);
                $previousTierId = $tier->id;
            }

            // Set current tier to the first pending tier (should be first reviewing or authorization)
            $firstTier = $request->tiers()->orderBy('sequence')->first();
            $request->current_tier_id = $firstTier?->id;
            $request->save();

            // Log the submission
            $this->log($request, $submitter, 'submitted', null, null, 'draft', 'pending');
        });

        return $request;
    }

    /**
     * Approve the current tier of an approval request.
     *
     * @param ApprovalRequest $request
     * @param \App\Models\User|null $approver
     * @param string|null $comments
     * @return void
     * @throws \Exception
     */
    public function approve(ApprovalRequest $request, $approver = null, ?string $comments = null): void
    {
        if (!$approver) {
            $approver = Auth::user();
        }

        if ($request->status !== 'pending') {
            throw new \Exception('Approval request is not pending.');
        }

        $currentTier = $request->currentTier;
        if (!$currentTier || $currentTier->status !== 'pending') {
            throw new \Exception('No pending tier found for this approval request.');
        }

        // TODO: Check if the approver has any of the roles defined for this tier
        // For now, we assume the approver is authorized (you will integrate with your RBAC later)

        DB::transaction(function () use ($request, $currentTier, $approver, $comments) {
            if ($currentTier->approval_mode === 'all') {
                // Record this approver's approval
                ApprovalTierApproval::updateOrCreate(
                    ['tier_id' => $currentTier->id, 'user_id' => $approver->id],
                    ['comments' => $comments, 'approved_at' => now()]
                );

                // Check if all required approvers have approved
                $requiredRoles = $currentTier->roles;
                $approvedCount = $currentTier->tierApprovals()->count();
                // Simplified: we need to know total users with these roles; for now, we just count approvals.
                // You may want to fetch users with those roles and compare. We'll implement properly later.
                // For this basic version, we'll treat 'all' mode as immediate approval (to be improved).
            }

            // For 'any' mode (or simplified 'all' above), complete this tier
            $currentTier->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'comments'    => $comments,
            ]);

            $this->log($request, $approver, 'approve', $currentTier->id, $comments, $currentTier->status, 'approved');

            // Advance to next tier or complete the request
            $this->advanceToNextTier($request);
        });
    }

    /**
     * Reject the approval request.
     *
     * @param ApprovalRequest $request
     * @param \App\Models\User|null $rejecter
     * @param string|null $comments
     * @return void
     * @throws \Exception
     */
    public function reject(ApprovalRequest $request, $rejecter = null, ?string $comments = null): void
    {
        if (!$rejecter) {
            $rejecter = Auth::user();
        }

        if ($request->status !== 'pending') {
            throw new \Exception('Approval request is not pending.');
        }

        DB::transaction(function () use ($request, $rejecter, $comments) {
            $oldStatus = $request->status;
            $request->update([
                'status'       => 'rejected',
                'completed_at' => now(),
            ]);

            $this->log($request, $rejecter, 'reject', $request->current_tier_id, $comments, $oldStatus, 'rejected');
        });
    }

    /**
     * Recall (cancel) an approval request before completion.
     *
     * @param ApprovalRequest $request
     * @param \App\Models\User|null $initiator
     * @return void
     * @throws \Exception
     */
    public function recall(ApprovalRequest $request, $initiator = null): void
    {
        if (!$initiator) {
            $initiator = Auth::user();
        }

        if ($request->status !== 'pending') {
            throw new \Exception('Only pending requests can be recalled.');
        }

        // Only the submitter can recall
        if ($request->submitted_by !== $initiator->id) {
            throw new \Exception('Only the original submitter can recall this request.');
        }

        DB::transaction(function () use ($request, $initiator) {
            $oldStatus = $request->status;
            $request->update([
                'status'       => 'cancelled',
                'completed_at' => now(),
            ]);

            $this->log($request, $initiator, 'recall', null, null, $oldStatus, 'cancelled');
        });
    }

    /**
     * Check if a model already has an active (pending) approval.
     *
     * @param mixed $approvable
     * @return bool
     */
    protected function hasActiveApproval($approvable): bool
    {
        return ApprovalRequest::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->whereIn('status', ['draft', 'pending'])
            ->exists();
    }

    /**
     * Advance to the next pending tier after a tier is approved.
     *
     * @param ApprovalRequest $request
     * @return void
     */
    protected function advanceToNextTier(ApprovalRequest $request): void
    {
        $nextTier = $request->tiers()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();

        if ($nextTier) {
            $request->current_tier_id = $nextTier->id;
            $request->save();
        } else {
            // No more pending tiers – approval complete
            $request->update([
                'status'       => 'approved',
                'completed_at' => now(),
            ]);
            $this->log($request, null, 'completed', null, null, 'pending', 'approved');
        }
    }

    /**
     * Log an approval action.
     *
     * @param ApprovalRequest $request
     * @param \App\Models\User|null $user
     * @param string $action
     * @param int|null $tierId
     * @param string|null $comments
     * @param string|null $oldStatus
     * @param string|null $newStatus
     */
    protected function log(ApprovalRequest $request, $user, string $action, ?int $tierId, ?string $comments, ?string $oldStatus, ?string $newStatus): void
    {
        ApprovalLog::create([
            'approval_request_id' => $request->id,
            'user_id'             => $user?->id,
            'action'              => $action,
            'tier_id'             => $tierId,
            'comments'            => $comments,
            'old_status'          => $oldStatus,
            'new_status'          => $newStatus,
        ]);
    }
}