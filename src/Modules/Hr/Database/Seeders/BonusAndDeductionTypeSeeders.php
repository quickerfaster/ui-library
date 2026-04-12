<?php

namespace App\Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BonusAndDeductionTypeSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        // 1. Allowance Types (Recurring non-discretionary additions)
        /*$allowanceTypes = [
            ['name' => 'Housing Allowance', 'description' => 'Regular payment for accommodation expenses', 'editable' => 'No'],
            ['name' => 'Transportation Allowance', 'description' => 'Compensation for commute/travel costs', 'editable' => 'No'],
            ['name' => 'Meal Allowance', 'description' => 'Daily food expense reimbursement', 'editable' => 'No'],
            ['name' => 'Education Allowance', 'description' => 'Support for employee education/training', 'editable' => 'No'],
            ['name' => 'Remote Work Allowance', 'description' => 'Compensation for home office expenses', 'editable' => 'No'],
            ['name' => 'Shift Differential', 'description' => 'Additional pay for non-standard work hours', 'editable' => 'No'],
        ];
        DB::table('allowance_types')->insert($allowanceTypes);

        // 2. Bonus Types (Variable discretionary payments)
        $bonusTypes = [
            ['name' => 'Performance Bonus', 'description' => 'Awarded based on individual/team KPIs', 'editable' => 'No'],
            ['name' => 'Annual Bonus', 'description' => 'Year-end discretionary bonus', 'editable' => 'No'],
            ['name' => 'Referral Bonus', 'description' => 'Payment for successful new hire referrals', 'editable' => 'No'],
            ['name' => 'Retention Bonus', 'description' => 'Incentive to retain key employees', 'editable' => 'No'],
            ['name' => 'Spot Bonus', 'description' => 'Immediate reward for exceptional contributions', 'editable' => 'No'],
            ['name' => 'Sales Commission', 'description' => 'Percentage-based sales performance incentive', 'editable' => 'No'],
            ['name' => 'Signing Bonus', 'description' => 'One-time payment upon hiring acceptance', 'editable' => 'No'],
        ];
        DB::table('bonus_types')->insert($bonusTypes);

        // 3. Tax Types (Government-mandated deductions)
        $taxTypes = [
            ['name' => 'Federal Income Tax', 'description' => 'Federal government tax withholding', 'editable' => 'No'],
            ['name' => 'State Income Tax', 'description' => 'State government tax withholding', 'editable' => 'No'],
            ['name' => 'Local Income Tax', 'description' => 'Municipal/city tax withholding', 'editable' => 'No'],
            ['name' => 'Social Security (FICA)', 'description' => 'Federal Insurance Contributions Act tax', 'editable' => 'No'],
            ['name' => 'Medicare Tax', 'description' => 'Hospital insurance tax', 'editable' => 'No'],
        ];
        DB::table('tax_types')->insert($taxTypes);

        // 4. Other Deduction Types (Voluntary/statutory non-tax deductions)
        $otherDeductionTypes = [
            ['name' => 'Health Insurance', 'description' => 'Employee share of medical premiums', 'editable' => 'No'],
            ['name' => 'Dental Insurance', 'description' => 'Employee dental coverage contribution', 'editable' => 'No'],
            ['name' => 'Vision Insurance', 'description' => 'Employee vision coverage contribution', 'editable' => 'No'],
            ['name' => '401(k) Retirement', 'description' => 'Pre-tax retirement savings plan', 'editable' => 'No'],
            ['name' => 'Roth 401(k)', 'description' => 'Post-tax retirement savings plan', 'editable' => 'No'],
            ['name' => 'HSA Contribution', 'description' => 'Health Savings Account deduction', 'editable' => 'No'],
            ['name' => 'FSA Contribution', 'description' => 'Flexible Spending Account deduction', 'editable' => 'No'],
            ['name' => 'Union Dues', 'description' => 'Labor organization membership fees', 'editable' => 'No'],
            ['name' => 'Loan Repayment', 'description' => 'Company loan repayments', 'editable' => 'No'],
            ['name' => 'Wage Garnishment', 'description' => 'Court-ordered earnings withholding', 'editable' => 'No'],
            ['name' => 'Charitable Donation', 'description' => 'Workplace giving program deductions', 'editable' => 'No'],
        ];
        DB::table('deduction_types')->insert($otherDeductionTypes);*/
    }




}