<?php

namespace QuickerFaster\UILibrary\Services\Config\Approvals;


use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;

class ApprovalConfigResolver
{
    protected array $config;
    protected string $configKey;

    public function __construct(string $configKey)
    {
        $this->configKey = $configKey;
        $repository = app(ModelConfigRepository::class);
        $this->config = $repository->get($configKey);
    }

    public function getModelClass(): string
    {
        return $this->config['model'];
    }

    public function getTitle(): string
    {
        return $this->config['title'];
    }

    public function getDescription(): string
    {
        return $this->config['description'] ?? '';
    }

    public function lockWhileApproving(): bool
    {
        return $this->config['lock_while_approving'] ?? false;
    }

    public function getTiers(): array
    {
        return $this->config['tiers'];
    }

    public function getNotifications(): array
    {
        return $this->config['notifications'] ?? [];
    }

    public function getContext(): ?string
    {
        return $this->config['context'] ?? null;
    }

    public function getModule(): string
    {
        return $this->config['module'] ?? 'system';
    }

    /**
     * Get the full config array for advanced use.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}