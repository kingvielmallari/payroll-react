<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | These values are used for government forms and official documents.
    | Make sure to update these with your actual company information.
    |
    */

    'company_name' => env('COMPANY_NAME', 'Your Company Name'),
    'company_address' => env('COMPANY_ADDRESS', 'Your Company Address'),
    'company_tin' => env('COMPANY_TIN', 'XXX-XXX-XXX-XXX'),
    'sss_employer_number' => env('SSS_EMPLOYER_NUMBER', 'XX-XXXXXXX-X'),
    'philhealth_pen' => env('PHILHEALTH_PEN', 'XX-XXXXXXXXX-X'),
    'pagibig_ern' => env('PAGIBIG_ERN', 'XXXXXXXXXXXX'),
    
    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    */
    
    'company_phone' => env('COMPANY_PHONE', ''),
    'company_email' => env('COMPANY_EMAIL', ''),
    'company_website' => env('COMPANY_WEBSITE', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Authorized Representatives
    |--------------------------------------------------------------------------
    */
    
    'authorized_representative' => env('AUTHORIZED_REPRESENTATIVE', 'HR Manager'),
    'hr_manager' => env('HR_MANAGER', 'HR Manager Name'),
    'ceo_name' => env('CEO_NAME', 'CEO Name'),
    
    /*
    |--------------------------------------------------------------------------
    | Government Forms Settings
    |--------------------------------------------------------------------------
    */
    
    'bir_forms_enabled' => env('BIR_FORMS_ENABLED', true),
    'sss_forms_enabled' => env('SSS_FORMS_ENABLED', true),
    'philhealth_forms_enabled' => env('PHILHEALTH_FORMS_ENABLED', true),
    'pagibig_forms_enabled' => env('PAGIBIG_FORMS_ENABLED', true),

];
