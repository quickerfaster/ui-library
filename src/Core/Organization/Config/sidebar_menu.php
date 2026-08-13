<?php

return [
    [
    'title' => 'All Companies',
    'icon' => 'fas fa-building',
    'url' => 'organization/organization/companies',
    'permission' => 'view_company',
    'order' => 10,
],
    [
    'title' => 'Branches',
    'icon' => 'fas fa-code-branch',
    'url' => 'organization/organization/branches',
    'permission' => 'view_branch',
    'order' => 20,
],
    [
    'title' => 'Business Units',
    'icon' => 'fas fa-briefcase',
    'url' => 'organization/organization/business-units',
    'permission' => 'view_business_unit',
    'order' => 30,
],
    [
    'itemType' => 'item-separator',
    'title' => '<h6 class="ps-3 mt-4 mb-2 text-uppercase text-xs font-weight-bolder opacity-6 group-title">Structure</h6>',
    'url' => null,
],
    [
    'title' => 'Departments',
    'icon' => 'fas fa-layer-group',
    'url' => 'organization/organization/departments',
    'permission' => 'view_department',
    'groupTitle' => 'Structure',
    'order' => 10,
],
    [
    'title' => 'Divisions',
    'icon' => 'fas fa-diagram-project',
    'url' => 'organization/organization/divisions',
    'permission' => 'view_division',
    'groupTitle' => 'Structure',
    'order' => 20,
],
    [
    'itemType' => 'item-separator',
    'title' => '<h6 class="ps-3 mt-4 mb-2 text-uppercase text-xs font-weight-bolder opacity-6 group-title">Locations</h6>',
    'url' => null,
],
    [
    'title' => 'All Locations',
    'icon' => 'fas fa-location-dot',
    'url' => 'organization/organization/locations',
    'permission' => 'view_location',
    'groupTitle' => 'Locations',
    'order' => 10,
],
    [
    'title' => 'All Teams',
    'icon' => 'fas fa-people-group',
    'url' => 'organization/organization/teams',
    'permission' => 'view_team',
],
];
