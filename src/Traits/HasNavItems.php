<?php

namespace QuickerFaster\UILibrary\Traits;

trait HasNavItems
{
    protected function defaultNavItems(): array
    {
        return [
            ['key' => 'dashboard', 'label' => __('qf::nav.dashboard'), 'tooltip' => __('qf::nav.dashboard'), 'route' => route('dashboard'), 'icon' => 'fa-tachometer-alt'],
            ['key' => 'profile', 'label' => __('qf::nav.profile'), 'tooltip' => __('qf::nav.profile'), 'route' => route('profile'), 'icon' => 'fa-user'],
            ['key' => 'account', 'label' => __('qf::nav.account'), 'tooltip' => __('qf::nav.account'), 'route' => route('profile'), 'icon' => 'fa-user-cog'],
            ['key' => 'help', 'label' => __('qf::nav.help'), 'tooltip' => __('qf::nav.help'), 'route' => route('help'), 'icon' => 'fa-question-circle'],
            ['key' => 'settings', 'label' => __('qf::nav.settings'), 'tooltip' => __('qf::nav.settings'), 'route' => route('settings'), 'icon' => 'fa-cog'],
        ];
    }
}
