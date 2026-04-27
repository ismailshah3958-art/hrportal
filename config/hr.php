<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late incidents → salary deduction (payroll)
    |--------------------------------------------------------------------------
    | Each day with a counted late (after shift grace) is one incident.
    | Every N incidents in a payroll period deduct one calendar day of pay.
    */
    'lates_per_deduction_day' => (int) env('HR_LATES_PER_DEDUCTION_DAY', 3),

    /*
    |--------------------------------------------------------------------------
    | Daily rate divisor (for “one day salary” from monthly amount)
    |--------------------------------------------------------------------------
    */
    'salary_day_divisor' => (int) env('HR_SALARY_DAY_DIVISOR', 30),

    /*
    |--------------------------------------------------------------------------
    | Default shift (DB id) when employee has no assignment
    |--------------------------------------------------------------------------
    */
    'default_shift_id' => env('HR_DEFAULT_SHIFT_ID'),

];
