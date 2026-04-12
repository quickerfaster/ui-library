<?php

namespace App\Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Modules\Admin\Models\JobTitle;

class JobTitleSeeder extends Seeder
{
    public function run()
    {
        $titles = [
            // Executive Leadership
            /*['title' => 'Chief Executive Officer', 'description' => 'Leads the organization and defines strategic direction'],
            ['title' => 'Chief Operating Officer', 'description' => 'Oversees daily operations and ensures business efficiency'],
            ['title' => 'Chief Financial Officer', 'description' => 'Manages financial planning, risk, and reporting'],
            ['title' => 'Chief Technology Officer', 'description' => 'Leads technology strategy and innovation'],
            ['title' => 'Chief Marketing Officer', 'description' => 'Oversees marketing strategy and brand development'],*/
    
            // Administrative & Support
            /*['title' => 'Executive Assistant', 'description' => 'Supports executives with scheduling, communication, and coordination'],
            ['title' => 'Office Manager', 'description' => 'Manages office operations, supplies, and facility coordination'],
            ['title' => 'Administrative Assistant', 'description' => 'Handles clerical tasks and office support'],*/
    
            // Finance & Accounting
            /*['title' => 'Finance Manager', 'description' => 'Oversees financial strategy, budgeting, and compliance'],
            ['title' => 'Accountant', 'description' => 'Prepares financial records and statements'],
            ['title' => 'Accounts Payable Specialist', 'description' => 'Processes outgoing payments and vendor relations'],
            ['title' => 'Accounts Receivable Specialist', 'description' => 'Manages incoming payments and customer billing'],
            ['title' => 'Auditor', 'description' => 'Reviews financial records for accuracy and compliance'],*/
    
            // Human Resources
            ['title' => 'HR Manager', 'description' => 'Leads recruitment, performance, and HR policy'],
            ['title' => 'HR Assistant', 'description' => 'Supports daily HR operations and employee documentation'],
            ['title' => 'Recruiter', 'description' => 'Sources and screens candidates for open positions'],
            ['title' => 'Training and Development Officer', 'description' => 'Organizes staff learning and growth programs'],
    
            // IT & Engineering
            /*['title' => 'IT Administrator', 'description' => 'Maintains network systems and technical support'],
            ['title' => 'Software Developer', 'description' => 'Designs and builds software applications'],
            ['title' => 'Systems Analyst', 'description' => 'Analyzes system requirements and implements solutions'],
            ['title' => 'DevOps Engineer', 'description' => 'Ensures CI/CD and infrastructure reliability'],
            ['title' => 'Data Analyst', 'description' => 'Collects, analyzes, and visualizes data insights'],*/
    
            // Marketing & Sales
            /*['title' => 'Marketing Manager', 'description' => 'Develops campaigns and brand strategies'],
            ['title' => 'Digital Marketing Specialist', 'description' => 'Manages online ads, SEO, and social media'],
            ['title' => 'Sales Representative', 'description' => 'Builds client relationships and drives revenue'],
            ['title' => 'Business Development Manager', 'description' => 'Identifies and grows new business opportunities'],*/
    
            // Logistics, Production & Operations
            /*['title' => 'Operations Manager', 'description' => 'Oversees daily production or service delivery'],
            ['title' => 'Production Supervisor', 'description' => 'Manages manufacturing and quality processes'],
            ['title' => 'Warehouse Supervisor', 'description' => 'Leads warehouse logistics and inventory management'],
            ['title' => 'Procurement Officer', 'description' => 'Handles purchasing and supplier negotiations'],
            ['title' => 'Logistics Coordinator', 'description' => 'Plans and manages transportation and deliveries'],*/
    
            // Legal & Compliance
            /*['title' => 'Compliance Officer', 'description' => 'Ensures adherence to laws and regulations'],
            ['title' => 'Legal Advisor', 'description' => 'Provides legal counsel and document review'],*/
    
            // Customer Service
            /*['title' => 'Customer Support', 'description' => 'Assists customers with inquiries and issues'],
            ['title' => 'Call Center Agent', 'description' => 'Handles incoming/outgoing service calls'],
            ['title' => 'Client Relationship Manager', 'description' => 'Maintains long-term client satisfaction'],*/
    
            // Research, Design & Others
            /*['title' => 'Product Manager', 'description' => 'Leads product planning and lifecycle'],
            ['title' => 'Graphic Designer', 'description' => 'Creates visual content for digital and print'],
            ['title' => 'Research Analyst', 'description' => 'Conducts market and business research'],
            ['title' => 'Intern', 'description' => 'Assists teams while gaining hands-on experience'],*/
        ];
    
        foreach ($titles as $data) {
            JobTitle::firstOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    // 'editable' => "No",
                ]
            );
        }
    }
    
}



