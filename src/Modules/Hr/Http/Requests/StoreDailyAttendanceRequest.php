<?php

namespace App\Modules\Hr\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /*public function authorize(): bool
    {
        // Adjust authorization logic based on your application's roles/permissions
        return auth()->check() && auth()->user()->can('record-attendance');
    }*/

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employee_profiles,employee_id'], // Assuming 'employees' is your employee table
            'attendance_time' => ['required', 'date'],
            'attendance_type' => ['required', Rule::in(['check-in', 'check-out'])],
            'device_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],// 'between:-90,90'],
            'longitude' => ['nullable', 'numeric'],// 'between:-180,180'],
            // Add any other specific validation rules here
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add attendance_date based on attendance_time for convenience
        if ($this->has('attendance_time')) {
            $this->merge([
                'attendance_date' => \Carbon\Carbon::parse($this->attendance_time)->toDateString(),
            ]);
        }
    }
}
