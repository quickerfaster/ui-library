<?php

namespace QuickerFaster\UILibrary\Contracts\Modules;

interface ModuleContract
{
    /**
     * Get the module's unique key (lowercase).
     */
    public function getKey(): string;

    /**
     * Get the module's display label.
     */
    public function getLabel(): string;

    /**
     * Get the module's icon (Font Awesome class).
     */
    public function getIcon(): string;

    /**
     * Get the module's dashboard route name.
     */
    public function getRoute(): string;

    /**
     * Get the module's display order.
     */
    public function getOrder(): int;

    /**
     * Get roles that can access this module (* for all).
     */
    public function getRoles(): array;

    /**
     * Whether this is a Core module (shipped with library).
     */
    public function isCore(): bool;

    /**
     * Get the module's absolute filesystem path.
     */
    public function getPath(): string;

    /**
     * Whether this module should appear in user-facing navigation.
     * Infrastructure modules (System, Common) return false.
     */
    public function isUserFacing(): bool;

    /**
     * Get the module keys this module depends on.
     * Returns an array of module key strings (e.g., ['organization']).
     */
    public function getDependencies(): array;
}