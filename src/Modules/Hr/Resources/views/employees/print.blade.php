<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $employee->first_name }} {{ $employee->last_name }} - Employee Details</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .employee-name h1 {
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .employee-name p {
            color: #555;
        }
        .employee-id {
            text-align: right;
        }
        /* Two-column layout */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .col {
            flex: 1;
            padding: 0 10px;
        }
        /* Cards (sections) */
        .card {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .card-title {
            font-size: 16pt;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .info-label {
            width: 35%;
            font-weight: bold;
            color: #555;
        }
        .info-value {
            width: 65%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="employee-name">
                <h1>{{ $employee->first_name }} {{ $employee->last_name }}</h1>
                <p>
                    {{ $currentPosition?->jobTitle?->title ?? '—' }} ·
                    {{ $currentPosition?->department?->name ?? '—' }}
                </p>
            </div>
            <div class="employee-id">
                <p><strong>Employee #:</strong> {{ $employee->employee_number }}</p>
                <p><strong>Status:</strong> {{ $currentPosition?->employment_status ?? 'Active' }}</p>
            </div>
        </div>

        <div class="row">
            {{-- LEFT COLUMN: Personal, Contact, Emergency --}}
            <div class="col">
                {{-- Personal Information (from EmployeeProfile) --}}
                @php $profile = $employee->employeeProfile; @endphp
                <div class="card">
                    <div class="card-title">Personal Information</div>
                    <div class="info-row">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value">{{ $profile?->date_of_birth ? \Carbon\Carbon::parse($profile->date_of_birth)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Gender</div>
                        <div class="info-value">{{ $profile?->gender ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Marital Status</div>
                        <div class="info-value">{{ $profile?->marital_status ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nationality</div>
                        <div class="info-value">{{ $profile?->nationality ?? '—' }}</div>
                    </div>
                    @if($profile?->bio)
                    <div class="info-row">
                        <div class="info-label">Bio</div>
                        <div class="info-value">{{ $profile->bio }}</div>
                    </div>
                    @endif
                </div>

                {{-- Contact Information (from EmployeeProfile) --}}
                @if($profile)
                <div class="card">
                    <div class="card-title">Contact Information</div>
                    <div class="info-row">
                        <div class="info-label">Personal Email</div>
                        <div class="info-value">{{ $profile->personal_email ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Work Email</div>
                        <div class="info-value">{{ $employee->email ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Personal Phone</div>
                        <div class="info-value">{{ $profile->personal_phone ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Work Phone</div>
                        <div class="info-value">{{ $profile->work_phone ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Address</div>
                        <div class="info-value">
                            {{ $profile->address_street ?? '' }}<br>
                            {{ $profile->address_city ?? '' }} {{ $profile->address_state ?? '' }} {{ $profile->address_postal_code ?? '' }}<br>
                            {{ $profile->address_country ?? '' }}
                        </div>
                    </div>
                </div>
                @endif

                {{-- Emergency Contact (from EmployeeProfile) --}}
                @if($profile && ($profile->emergency_contact_name || $profile->emergency_contact_phone))
                <div class="card">
                    <div class="card-title">Emergency Contact</div>
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ $profile->emergency_contact_name ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $profile->emergency_contact_phone ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Relationship</div>
                        <div class="info-value">{{ $profile->emergency_contact_relationship ?? '—' }}</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: Employment, Compensation, Work Patterns --}}
            <div class="col">
                {{-- Employment Details (from currentPosition) --}}
                <div class="card">
                    <div class="card-title">Employment Details</div>
                    <div class="info-row">
                        <div class="info-label">Hire Date</div>
                        <div class="info-value">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Department</div>
                        <div class="info-value">{{ $currentPosition?->department?->name ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Job Title</div>
                        <div class="info-value">{{ $currentPosition?->jobTitle?->title ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Manager</div>
                        <div class="info-value">
                            @if($currentPosition?->manager)
                                {{ $currentPosition->manager->first_name }} {{ $currentPosition->manager->last_name }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Employment Status</div>
                        <div class="info-value">{{ $currentPosition?->employment_status ?? 'Active' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Location</div>
                        <div class="info-value">{{ $currentPosition?->location?->name ?? '—' }}</div>
                    </div>
                </div>

                {{-- Compensation (from currentPosition) --}}
                @if($currentPosition)
                <div class="card">
                    <div class="card-title">Compensation</div>
                    <div class="info-row">
                        <div class="info-label">Pay Type</div>
                        <div class="info-value">{{ ucfirst($currentPosition->pay_type ?? '—') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Salary / Rate</div>
                        <div class="info-value">
                            @if($currentPosition->pay_type === 'hourly')
                                ${{ number_format($currentPosition->hourly_rate, 2) }}/hour
                            @elseif($currentPosition->pay_type === 'salaried_daily' || $currentPosition->pay_type === 'salaried_full')
                                ${{ number_format($currentPosition->base_salary, 2) }}/year
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Pay Frequency</div>
                        <div class="info-value">{{ ucfirst($currentPosition->pay_frequency ?? '—') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Currency</div>
                        <div class="info-value">{{ $currentPosition->salary_currency ?? 'USD' }}</div>
                    </div>
                </div>
                @endif

                {{-- Work Patterns --}}
                @if($employee->employeeWorkPatterns && $employee->employeeWorkPatterns->count())
                <div class="card">
                    <div class="card-title">Work Patterns</div>
                    <table>
                        <thead>
                            <tr><th>Pattern</th><th>Start Date</th><th>End Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach($employee->employeeWorkPatterns as $pattern)
                            <tr>
                                <td>{{ $pattern->workPattern?->name ?? '—' }}</td>
                                <td>{{ $pattern->start_date ? \Carbon\Carbon::parse($pattern->start_date)->format('M d, Y') : '—' }}</td>
                                <td>{{ $pattern->end_date ? \Carbon\Carbon::parse($pattern->end_date)->format('M d, Y') : 'Ongoing' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Print button (only visible on screen) --}}
        <div class="text-center no-print" style="margin-top: 30px;">
            <button onclick="window.print();" class="btn btn-primary">Print this page</button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</body>
</html>