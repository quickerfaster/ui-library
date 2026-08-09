<?php

namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface NavigationProvider
{
    /**
     * Get navigation items for a given context within a module.
     *
     * @param string $module Module key (e.g., 'admin', 'hr')
     * @param string|null $context Context key (e.g., 'users', 'reports')
     * @return array Array of navigation item definitions
     */
    public function getNavigationItems(string $module, ?string $context = null): array;

    /**
     * Get context groups for a module (sidebar grouping).
     *
     * @param string $module Module key
     * @return array Array of context group definitions
     */
    public function getContextGroups(string $module): array;

    /**
     * Get shared navigation items (header/footer) for a module.
     *
     * @param string $module Module key
     * @param string $section 'header' or 'footer'
     * @return array
     */
    public function getSharedItems(string $module, string $section): array;
}