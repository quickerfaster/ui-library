<?php

namespace QuickerFaster\UILibrary\Exceptions;

use RuntimeException;
use Throwable;

/**
 * RecordNotAccessibleException
 *
 * Thrown when a model record cannot be accessed by the current user.
 * Replaces raw ModelNotFoundException with rich context that the global
 * exception handler can use to render appropriate responses.
 *
 * Distinguishes between:
 *  - 404: Record genuinely does not exist in the database
 *  - 403: Record exists but the current user/company does not have access
 */
class RecordNotAccessibleException extends RuntimeException
{
    /**
     * HTTP status code (404 or 403).
     *
     * @var int
     */
    protected int $httpStatusCode;

    /**
     * User-friendly message safe to display in the UI.
     *
     * @var string
     */
    protected string $userMessage;

    /**
     * Suggested redirect route name.
     *
     * @var string|null
     */
    protected ?string $redirectRoute;

    /**
     * Contextual data for logging/debugging.
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * Create a new RecordNotAccessibleException.
     *
     * @param  string      $userMessage   Human-readable message for the UI
     * @param  int         $httpStatusCode HTTP status: 404 (not found) or 403 (forbidden)
     * @param  array       $context        Debug/log context: model class, ID, user, etc.
     * @param  string|null $redirectRoute  Suggested redirect route name
     * @param  Throwable   $previous       Previous exception for chaining
     */
    public function __construct(
        string $userMessage = 'The requested record was not found.',
        int $httpStatusCode = 404,
        array $context = [],
        ?string $redirectRoute = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($userMessage, $httpStatusCode, $previous);

        $this->userMessage   = $userMessage;
        $this->httpStatusCode = $httpStatusCode;
        $this->context       = $context;
        $this->redirectRoute = $redirectRoute;
    }

    /**
     * Get the HTTP status code.
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the user-friendly message.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get the suggested redirect route, if any.
     */
    public function getRedirectRoute(): ?string
    {
        return $this->redirectRoute;
    }

    /**
     * Get the debug/log context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return array_merge($this->context, [
            'exception_class' => static::class,
            'http_status'     => $this->httpStatusCode,
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    /**
     * Create an exception for a record that belongs to a different company.
     *
     * @param  string $modelClass  Model class name
     * @param  mixed  $id          Record ID
     * @param  int    $recordCompanyId  The company the record belongs to
     * @param  int    $userCompanyId    The user's current session company
     * @return static
     */
    public static function differentCompany(
        string $modelClass,
        $id,
        int $recordCompanyId,
        int $userCompanyId
    ): self {
        $modelName = class_basename($modelClass);

        return new self(
            "This {$modelName} belongs to a different company and cannot be accessed from your current company view. Please switch to the appropriate company.",
            403,
            [
                'model_class'      => $modelClass,
                'record_id'        => $id,
                'record_company_id' => $recordCompanyId,
                'user_company_id'  => $userCompanyId,
            ],
            null // No suggested redirect — user should switch companies
        );
    }

    /**
     * Create an exception for a record that truly does not exist.
     *
     * @param  string      $modelClass
     * @param  mixed       $id
     * @param  string|null $redirectRoute
     * @return static
     */
    public static function notFound(string $modelClass, $id, ?string $redirectRoute = null): self
    {
        $modelName = class_basename($modelClass);

        return new self(
            "The requested {$modelName} could not be found. It may have been deleted or the link is invalid.",
            404,
            [
                'model_class' => $modelClass,
                'record_id'   => $id,
            ],
            $redirectRoute
        );
    }
}