<?php

namespace QuickerFaster\UILibrary\Contracts\Settings;

interface SettingsProvider
{
    /**
     * Resolve a setting value by key.
     * Return null if this provider cannot resolve the key.
     *
     * @param string $key
     * @return mixed|null
     */
    public function resolve(string $key): mixed;
}