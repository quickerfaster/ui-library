<?php

namespace QuickerFaster\UILibrary\Concerns;

use QuickerFaster\UILibrary\Exceptions\RecordNotAccessibleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Trait ResolvesModels
 *
 * Provides safe, scope-aware model resolution methods for Livewire components
 * and controllers. Designed to replace all findOrFail() / firstOrFail() calls
 * across the application.
 *
 * Usage in a Livewire component:
 *   use ResolvesModels;
 *
 *   $record = $this->resolveModel(Invoice::class, $this->invoiceId);
 *   if (!$record) {
 *       $this->flashAndRedirect('error', 'Invoice not found.', 'invoices.index');
 *       return;
 *   }
 *
 * Usage in a controller:
 *   use ResolvesModels;
 *
 *   $record = $this->resolveModelOrFail(Record::class, $id);
 */
trait ResolvesModels
{
    /**
     * Safely resolve a model by ID with optional scoping.
     *
     * Uses withoutCompanyScope() by default to ensure the record is found
     * regardless of the user's current session company. Company access
     * should be checked separately as an authorization concern.
     *
     * @param  string      $modelClass  Fully-qualified model class name
     * @param  int|string  $id          Primary key value
     * @param  array       $scopes      Optional additional query scopes.
     *                                  Each element is a closure: fn($query) => $query->where(...)
     * @return Model|null               The model instance or null if not found
     */
    public function resolveModel(string $modelClass, $id, array $scopes = []): ?Model
    {
        // Validate ID is non-empty and positive (for integer IDs)
        if (empty($id) || (is_int($id) && $id <= 0)) {
            return null;
        }

        // Validate the class exists
        if (!class_exists($modelClass)) {
            Log::warning('ResolvesModels: Attempted to resolve non-existent model class', [
                'model_class' => $modelClass,
                'id'          => $id,
            ]);
            return null;
        }

        // Build the query
        // Use withoutCompanyScope() if available (multi-tenant apps),
        // otherwise fall back to a plain query (standard models).
        $query = method_exists($modelClass, 'withoutCompanyScope')
            ? $modelClass::withoutCompanyScope()
            : $modelClass::query();

        // Apply additional scopes
        foreach ($scopes as $scope) {
            if (is_callable($scope)) {
                $query = $scope($query);
            }
        }

        return $query->find($id);
    }

    /**
     * Resolve a model or throw a RecordNotAccessibleException.
     *
     * @param  string      $modelClass    Fully-qualified model class name
     * @param  int|string  $id            Primary key value
     * @param  array       $scopes        Optional additional query scopes
     * @param  int         $httpStatus    HTTP status code (404 or 403)
     * @param  string|null $message       Custom user-facing message
     * @param  string|null $redirectRoute Route name to suggest for redirection
     * @return Model
     *
     * @throws RecordNotAccessibleException
     */
    public function resolveModelOrFail(
        string $modelClass,
        $id,
        array $scopes = [],
        int $httpStatus = 404,
        ?string $message = null,
        ?string $redirectRoute = null
    ): Model {
        $record = $this->resolveModel($modelClass, $id, $scopes);

        if (!$record) {
            $modelName = class_basename($modelClass);
            throw new RecordNotAccessibleException(
                $message ?? "{$modelName} not found.",
                $httpStatus,
                [
                    'model_class' => $modelClass,
                    'id'          => $id,
                    'scopes'      => $scopes,
                ],
                $redirectRoute
            );
        }

        return $record;
    }

    /**
     * Resolve a model scoped to a specific company.
     *
     * Designed for multi-tenant scenarios where the user should only access
     * records belonging to a given company. Uses withoutCompanyScope() to
     * bypass the session-based global scope, then applies an explicit
     * company_id WHERE clause.
     *
     * @param  string      $modelClass  Fully-qualified model class name
     * @param  int|string  $id          Primary key value
     * @param  int         $companyId   Company ID to scope to
     * @return Model|null
     */
    public function resolveModelForCompany(string $modelClass, $id, int $companyId): ?Model
    {
        return $this->resolveModel($modelClass, $id, [
            function ($query) use ($companyId) {
                return $query->where('company_id', $companyId);
            },
        ]);
    }

    /**
     * Validate that the resolved record belongs to the user's current session company.
     *
     * Call this AFTER resolving a model with resolveModel() to check company access.
     * If the session company is 0 (All Companies mode), access is always granted.
     *
     * @param  Model $record  The resolved model instance
     * @return bool           True if access is allowed, false otherwise
     */
    public function checkCompanyAccess(Model $record): bool
    {
        $sessionCompanyId = Session::get('current_company_id');

        // All Companies mode — super admin access
        if (empty($sessionCompanyId) || $sessionCompanyId === 0) {
            return true;
        }

        // Check if the model has a company_id attribute
        if (!array_key_exists('company_id', $record->getAttributes())) {
            return true; // Model doesn't support company scoping
        }

        return (int) $record->company_id === (int) $sessionCompanyId;
    }

    /**
     * Flash a message to the session and redirect.
     *
     * Works from both Livewire components and controllers. In Livewire,
     * dispatches a showAlert browser event so the UI can display a toast.
     *
     * @param  string $type     Alert type: 'success', 'error', 'warning', 'info'
     * @param  string $message  User-facing message
     * @param  string $route    Route name to redirect to
     * @param  array  $params   Optional route parameters
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function flashAndRedirect(
        string $type,
        string $message,
        string $route,
        array $params = []
    ) {
        session()->flash($type, $message);

        // If this is a Livewire component, also dispatch a browser event
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('showAlert', [
                'type'    => $type,
                'message' => $message,
            ]);
        }

        if (method_exists($this, 'redirectRoute')) {
            // Livewire redirect
            return $this->redirectRoute($route, $params);
        }

        // Controller redirect
        return redirect()->route($route, $params)->with($type, $message);
    }

    /**
     * Clean up wizard session data and redirect with an error message.
     *
     * Used when a wizard component discovers that its backing record has
     * been deleted or is no longer accessible. Prevents the user from
     * being stuck with stale wizard state.
     *
     * @param  string $wizardId      Session key for the wizard (e.g., 'invoice-wizard-' . auth()->id())
     * @param  string $errorMessage  User-facing error message
     * @param  string $fallbackRoute Route to redirect to
     * @param  array  $params        Optional route parameters
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function cleanupWizardSession(
        string $wizardId,
        string $errorMessage,
        string $fallbackRoute,
        array $params = []
    ) {
        // Clear all wizard-related session data
        if (session()->has($wizardId)) {
            session()->forget($wizardId);
        }

        Log::info('ResolvesModels: Cleaned up stale wizard session', [
            'wizard_id' => $wizardId,
            'user_id'   => auth()->id() ?? 'guest',
            'reason'    => $errorMessage,
        ]);

        return $this->flashAndRedirect('error', $errorMessage, $fallbackRoute, $params);
    }
}