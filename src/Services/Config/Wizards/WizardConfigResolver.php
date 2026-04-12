<?php

namespace QuickerFaster\UILibrary\Services\Config\Wizards;

use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;

class WizardConfigResolver
{
    protected array $config;

    /**
     * @param string $wizardKey  Format: "module.wizards.wizard_name", e.g. "hr.wizards.employee_onboarding"
     * @param ModelConfigRepository|null $repository
     */
    public function __construct(string $wizardKey, ?ModelConfigRepository $repository = null)
    {
        $repository = $repository ?? app(ModelConfigRepository::class);
        $this->config = $repository->get($wizardKey);
    }

    public function getSteps(): array
    {
        return $this->config['steps'] ?? [];
    }

    public function getModels(): array
    {
        return $this->config['models'] ?? [];
    }

    public function getCompletion(): array
    {
        return $this->config['completion'] ?? [];
    }

    public function getLinkFields(): array
    {
        return $this->config['linkFields'] ?? [];
    }

    public function getTitle(): string
    {
        return $this->config['title'] ?? 'Wizard';
    }

    public function getDescription(): string
    {
        return $this->config['description'] ?? '';
    }


    public function getReturnPath(): string
    {
        return $this->config['returnPath'] ?? '';
    }

    public function getId(): string
    {
        return $this->config['id'] ?? '';
    }
}