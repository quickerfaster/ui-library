<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\Employee;

class UserSyncController extends Controller
{
    
    // This could be redundant
    public function syncUser()
    {
        // Query employees who have BOTH employee profile AND user relationship
        $employees = Employee::with([
                'user.roles',  // Load user with their roles (from Spatie Permission)
                'department',
                'employeeProfile',
                'employeePosition'
            ])
            ->whereHas('employeeProfile')  // Must have employee profile
            ->whereHas('user')             // Must have user account
            ->whereNotNull('user_id')      // Additional check for user relationship
            ->get()
            ->map(function ($employee) {
                $user = $employee->user;
                $employeeProfile = $employee->employeeProfile;
                
                // Get the user's role
                $role = '';
                if ($user?->roles->isNotEmpty()) {
                    $role = $user?->roles->first()->name;
                }
                
                return [
                    // User account information
                    'user_id' => $user?->id,
                    'username' => $user?->email, // Assuming email is username
                    'role' => $role,
                    'user_name' => $user?->name,
                    'password' => $user?->password,
                    'user_location' => $user?->location,
                    'user_about_me' => $user?->about_me,
                    'user_company_id' => $user?->company_id,
                    'user_email' => $user?->email,
                    'user_phone' => $user?->phone,
                    
                    // Employee core information
                    'status' => "Active",
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->first_name . ' ' . $employee->last_name,
                    'official_email' => $employee->email,
                    'official_phone' => $employee->phone,
                    
                    // Employee Profile information
                    'profile_picture' => $employeeProfile->photo
                        ? asset('storage/' . $employeeProfile->photo)
                        : null,
                    'middle_name' => $employeeProfile->middle_name,
                    'preferred_name' => $employeeProfile->preferred_name,
                    'personal_email' => $employeeProfile->personal_email,
                    'personal_phone' => $employeeProfile->personal_phone,
                    'work_phone' => $employeeProfile->work_phone,
                    'emergency_contact_name' => $employeeProfile->emergency_contact_name,
                    'emergency_contact_phone' => $employeeProfile->emergency_contact_phone,
                    'emergency_contact_relationship' => $employeeProfile->emergency_contact_relationship,
                    'passport_number' => $employeeProfile->passport_number,
                    'passport_expiry_date' => $employeeProfile->passport_expiry_date,
                    'national_id_number' => $employeeProfile->national_id_number,
                    'bio' => $employeeProfile->bio,
                    
                    // Employee personal details
                    'gender' => $employee->gender,
                    'date_of_birth' => $employee->date_of_birth,
                    'nationality' => $employee->nationality,
                    'marital_status' => $employee->marital_status,
                    'address_street' => $employee->address_street,
                    'address_city' => $employee->address_city,
                    'address_state' => $employee->address_state,
                    'address_postal_code' => $employee->address_postal_code,
                    'address_country' => $employee->address_country,
                    
                    // Department information
                    'department' => $employee->department ? $employee->department->name : null,
                    'department_id' => $employee->department_id,
                    
                    // Position/Designation information
                    'designation' => $employee->employeePosition 
                        ? $employee->employeePosition->position_name 
                        : null,
                    'position_id' => $employee->employeePosition 
                        ? $employee->employeePosition->position_id 
                        : null,
                    
                    // Employment details
                    'status' => $employee->status,
                    'hire_date' => $employee->hire_date,
                    
                    // Additional computed fields for mobile app convenience
                    'display_name' => $employeeProfile->preferred_name 
                        ?: $employee->first_name . ' ' . $employee->last_name,
                    'primary_email' => $employeeProfile->personal_email 
                        ?: $employee->email 
                        ?: $user?->email,
                    'primary_phone' => $employeeProfile->personal_phone 
                        ?: $employeeProfile->work_phone 
                        ?: $employee->phone 
                        ?: $user?->phone,
                    'is_active' => $employee->status === 'active',
                    
                    // Timestamps
                    'created_at' => $employee->created_at,
                    'updated_at' => $employee->updated_at,
                    'user_created_at' => $user?->created_at,
                    'user_updated_at' => $user?->updated_at
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Employee data with user accounts synced successfully.',
            'count' => $employees->count(),
            'data' => $employees,
        ]);
    }
    

    public function syncAllEmployeesWithUserAndProfiles()
    {
        // Query employees who have BOTH employee profile AND user relationship
        $employees = Employee::with([
                'user.roles',  // Load user with their roles (from Spatie Permission)
                'department',
                'employeeProfile',
                'employeePosition'
            ])
            ->whereHas('employeeProfile')  // Must have employee profile
            ->whereHas('user')             // Must have user account
            ->whereNotNull('user_id')      // Additional check for user relationship
            ->get()
            ->map(function ($employee) {
                $user = $employee->user;
                $employeeProfile = $employee->employeeProfile;
                
                // Get the user's role
                $role = '';
                if ($user?->roles->isNotEmpty()) {
                    $role = $user?->roles->first()->name;
                }
                
                return [
                    // User account information
                    'user_id' => $user?->id,
                    'username' => $user?->email, // Assuming email is username
                    'role' => $role,
                    'user_name' => $user?->name,
                    'password' => $user?->password,
                    'user_location' => $user?->location,
                    'user_about_me' => $user?->about_me,
                    'user_company_id' => $user?->company_id,
                    'user_email' => $user?->email,
                    'user_phone' => $user?->phone,
                    
                    // Employee core information
                    'status' => "Active",
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->first_name . ' ' . $employee->last_name,
                    'official_email' => $employee->email,
                    'official_phone' => $employee->phone,
                    
                    // Employee Profile information
                    'profile_picture' => $employeeProfile->photo
                        ? asset('storage/' . $employeeProfile->photo)
                        : null,
                    'middle_name' => $employeeProfile->middle_name,
                    'preferred_name' => $employeeProfile->preferred_name,
                    'personal_email' => $employeeProfile->personal_email,
                    'personal_phone' => $employeeProfile->personal_phone,
                    'work_phone' => $employeeProfile->work_phone,
                    'emergency_contact_name' => $employeeProfile->emergency_contact_name,
                    'emergency_contact_phone' => $employeeProfile->emergency_contact_phone,
                    'emergency_contact_relationship' => $employeeProfile->emergency_contact_relationship,
                    'passport_number' => $employeeProfile->passport_number,
                    'passport_expiry_date' => $employeeProfile->passport_expiry_date,
                    'national_id_number' => $employeeProfile->national_id_number,
                    'bio' => $employeeProfile->bio,
                    
                    // Employee personal details
                    'gender' => $employee->gender,
                    'date_of_birth' => $employee->date_of_birth,
                    'nationality' => $employee->nationality,
                    'marital_status' => $employee->marital_status,
                    'address_street' => $employee->address_street,
                    'address_city' => $employee->address_city,
                    'address_state' => $employee->address_state,
                    'address_postal_code' => $employee->address_postal_code,
                    'address_country' => $employee->address_country,
                    
                    // Department information
                    'department' => $employee->department ? $employee->department->name : null,
                    'department_id' => $employee->department_id,
                    
                    // Position/Designation information
                    'designation' => $employee->employeePosition 
                        ? $employee->employeePosition->position_name 
                        : null,
                    'position_id' => $employee->employeePosition 
                        ? $employee->employeePosition->position_id 
                        : null,
                    
                    // Employment details
                    'status' => $employee->status,
                    'hire_date' => $employee->hire_date,
                    
                    // Additional computed fields for mobile app convenience
                    'display_name' => $employeeProfile->preferred_name 
                        ?: $employee->first_name . ' ' . $employee->last_name,
                    'primary_email' => $employeeProfile->personal_email 
                        ?: $employee->email 
                        ?: $user?->email,
                    'primary_phone' => $employeeProfile->personal_phone 
                        ?: $employeeProfile->work_phone 
                        ?: $employee->phone 
                        ?: $user?->phone,
                    'is_active' => $employee->status === 'active',
                    
                    // Timestamps
                    'created_at' => $employee->created_at,
                    'updated_at' => $employee->updated_at,
                    'user_created_at' => $user?->created_at,
                    'user_updated_at' => $user?->updated_at
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Employee data with user accounts synced successfully.',
            'count' => $employees->count(),
            'data' => $employees,
        ]);
    }

    /**
     * Alternative: Get employees with profiles, including those without user accounts
     */
    public function syncAllEmployeesWithProfiles()
    {
        $employees = Employee::with([
                'user.roles',
                'department',
                'employeeProfile',
                'employeePosition'
            ])
            ->whereHas('employeeProfile')
            ->get()
            ->map(function ($employee) {
                $user = $employee->user;
                $employeeProfile = $employee->employeeProfile;
                
                $role = '';
                $userData = null;
                
                if ($user) {
                    if ($user?->roles->isNotEmpty()) {
                        $role = $user?->roles->first()->name;
                    }
                    
                    $userData = [
                        'user_id' => $user?->id,
                        'username' => $user?->email,
                        'role' => $role,
                        'name' => $user?->name,
                        'email' => $user?->email,
                        'phone' => $user?->phone,
                        'has_account' => true
                    ];
                }
                
                return [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'department' => $employee->department ? $employee->department->name : null,
                    'status' => $employee->status,
                    'has_user_account' => !is_null($user),
                    'user' => $userData,
                    'profile' => [
                        'photo' => $employeeProfile->photo ? asset('storage/' . $employeeProfile->photo) : null,
                        'personal_email' => $employeeProfile->personal_email,
                        'personal_phone' => $employeeProfile->personal_phone
                    ]
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'All employees with profiles synced successfully.',
            'count' => $employees->count(),
            'data' => $employees,
        ]);
    }


    // Get employees who may not have profiles or user accounts
    public function syncAllEmployees()
    {
        $employees = Employee::with([
                'user.roles',
                'department',
                'employeeProfile',
                'employeePosition'
            ])
            ->get()
            ->map(function ($employee) {
                $user = $employee->user;
                $employeeProfile = $employee->employeeProfile;
                
                // Get the user's role
                $role = '';
                if ($user?->roles->isNotEmpty()) {
                    $role = $user?->roles->first()->name;
                }

                return [
                    // User account information
                    'user_id' => $user?->id,
                    'username' => $user?->email, // Assuming email is username
                    'role' => $role,
                    'user_name' => $user?->name,
                    'password' => $user?->password,
                    'user_location' => $user?->location,
                    'user_about_me' => $user?->about_me,
                    'user_company_id' => $user?->company_id,
                    'user_email' => $user?->email,
                    'user_phone' => $user?->phone,  
                    
                    // Employee core information
                    'status' => "Active",
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->first_name . ' ' . $employee->last_name,
                    'official_email' => $employee->email,
                    'official_phone' => $employee->phone,
                    
                    // Employee Profile information
                    'profile_picture' => $employeeProfile?->photo
                        ? asset('storage/' . $employeeProfile->photo)
                        : null,
                    'middle_name' => $employeeProfile?->middle_name,
                    'preferred_name' => $employeeProfile?->preferred_name,
                    'personal_email' => $employeeProfile?->personal_email,
                    'personal_phone' => $employeeProfile?->personal_phone,
                    'work_phone' => $employeeProfile?->work_phone,
                    'emergency_contact_name' => $employeeProfile?->emergency_contact_name,
                    'emergency_contact_phone' => $employeeProfile?->emergency_contact_phone,
                    'emergency_contact_relationship' => $employeeProfile?->emergency_contact_relationship,
                    'passport_number' => $employeeProfile?->passport_number,
                    'passport_expiry_date' => $employeeProfile?->passport_expiry_date,
                    'national_id_number' => $employeeProfile?->national_id_number,
                    'bio' => $employeeProfile?->bio,
                    
                    // Employee personal details
                    'gender' => $employee->gender,
                    'date_of_birth' => $employee->date_of_birth,
                    'nationality' => $employee->nationality,
                    'marital_status' => $employee->marital_status,
                    'address_street' => $employee->address_street,
                    'address_city' => $employee->address_city,
                    'address_state' => $employee->address_state,
                    'address_postal_code' => $employee->address_postal_code,
                    'address_country' => $employee->address_country,
                    
                    // Department information
                    'department' => $employee->department ? $employee->department->name : null,
                    'department_id' => $employee->department_id,
                    
                    // Position/Designation information
                    'designation' => $employee->employeePosition 
                        ? $employee->employeePosition->position_name 
                        : null,
                    'position_id' => $employee->employeePosition 
                        ? $employee->employeePosition->position_id 
                        : null,
                    
                    // Employment details
                    'status' => $employee->status,
                    'hire_date' => $employee->hire_date,
                    
                    // Additional computed fields for mobile app convenience
                    'display_name' => $employeeProfile?->preferred_name 
                        ?: $employee->first_name . ' ' . $employee->last_name,
                    'primary_email' => $employeeProfile?->personal_email 
                        ?: $employee->email 
                        ?: $user?->email,
                    'primary_phone' => $employeeProfile?->personal_phone
                        ?: $employeeProfile?->work_phone 
                        ?: $employee->phone 
                        ?: $user?->phone,
                    'is_active' => $employee->status === 'active',
                    
                    // Timestamps
                    'created_at' => $employee->created_at,
                    'updated_at' => $employee->updated_at,
                    'user_created_at' => $user?->created_at,
                    'user_updated_at' => $user?->updated_at
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'All employees synced successfully.',
            'count' => $employees->count(),
            'data' => $employees,
        ]);         
    }



}
