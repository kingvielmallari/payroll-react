# Clean Payroll System Documentation

## Overview

This is a cleaned and streamlined payroll system that implements a clear separation between:

1. **Draft Payroll** - Dynamic calculations on-the-fly
2. **Processing Payroll** - Static data from snapshots (no calculations)
3. **Approved Payroll** - Final locked state

## Key Features

### 🔄 Dynamic Draft Mode
- All calculations happen in real-time
- Based on current DTR data and system settings
- Changes automatically when underlying data changes
- No permanent storage of calculated values

### 📸 Static Processing Mode
- Creates snapshots when submitted to processing
- Data is locked and no longer calculated dynamically
- Shows the exact values as they were when submitted
- Immune to changes in DTR data or system settings

### ✅ Approved Final State
- Final locked state for payment processing
- Generates payslips with snapshot data
- Cannot be modified

## System Flow

```
[Draft] --submit--> [Processing] --approve--> [Approved]
   ↑                     ↓
   ←------reject---------
```

### Draft → Processing
- Creates `PayrollSnapshot` records for each employee
- Locks all calculation values at submission time
- Updates `PayrollDetail` records with snapshot data
- Status changes to 'processing'

### Processing → Approved
- No data changes, just status update
- Enables payslip generation
- Final state - no further modifications

### Processing → Draft (Reject)
- Deletes all snapshot records
- Resets PayrollDetail values to zero
- Status changes back to 'draft'
- Calculations become dynamic again

## Files Structure

### Controllers
- `PayrollController_clean.php` - Main cleaned controller with focused functionality
- `PayrollController.php` - Original controller (keep as reference)

### Models
- `PayrollSnapshot.php` - Stores static calculation data
- `Payroll.php` - Main payroll model (existing)
- `PayrollDetail.php` - Employee payroll details (existing)

### Views
- `show_clean.blade.php` - Clean payroll view supporting both dynamic and static modes
- `show.blade.php` - Original view (keep as reference)

### Routes
- `payroll_clean_routes.php` - Clean route definitions
- `web.php` - Original routes (modify as needed)

## Key Methods

### PayrollController_clean.php

#### Core CRUD
- `index()` - List payrolls with filtering
- `create()` - Show create form
- `store()` - Create draft payroll
- `show()` - Display payroll (dynamic or static based on status)
- `edit()` - Edit draft payrolls only
- `update()` - Update draft payrolls only
- `destroy()` - Delete draft payrolls only

#### Status Flow
- `submitToProcessing()` - Draft → Processing (creates snapshots)
- `approve()` - Processing → Approved
- `reject()` - Processing → Draft (deletes snapshots)

#### Calculation Engine
- `calculateDraftPayroll()` - Dynamic calculation for drafts
- `getProcessingPayrollData()` - Static data from snapshots
- `calculateEmployeeEarnings()` - Individual employee earnings
- `calculateEmployeeDeductions()` - Individual employee deductions
- `createPayrollSnapshots()` - Snapshot creation logic

## Key Features Implemented

### 1. Dynamic vs Static Display
The view automatically detects if payroll is in draft (dynamic) or processing/approved (static) mode and displays appropriate data and UI elements.

### 2. Snapshot System
When submitting to processing, the system:
- Calculates all current values based on DTR data
- Stores them in `PayrollSnapshot` table
- Updates `PayrollDetail` with snapshot values
- Locks the data against future changes

### 3. Clean UI Flow
- **Draft Mode**: Shows warning badges, edit buttons, dynamic calculation indicators
- **Processing Mode**: Shows info badges, approve/reject buttons, snapshot indicators  
- **Approved Mode**: Shows success badges, payslip generation buttons

### 4. Security & Permissions
- Only draft payrolls can be edited/deleted
- Only processing payrolls can be approved/rejected
- Only approved payrolls can generate payslips
- Proper authorization checks throughout

## Usage Examples

### Creating a New Payroll
1. Navigate to `/payrolls/create`
2. Select pay schedule and period
3. Select employees
4. Submit to create draft payroll
5. Review dynamic calculations in `/payrolls/{id}`
6. Submit to processing when ready
7. Approve for final state

### Viewing Payroll Data
- **Draft**: All values calculated in real-time from current DTR
- **Processing/Approved**: All values from snapshot taken at submission time

### Modifying Payrolls
- **Draft**: Can edit employees, period, all aspects
- **Processing**: Can only approve or reject (back to draft)
- **Approved**: Read-only, can only generate payslips

## Database Schema

### payroll_snapshots Table
```sql
- payroll_id (foreign key)
- employee_id (foreign key)
- basic_salary
- hourly_rate
- days_worked
- regular_hours
- overtime_hours
- holiday_hours
- night_differential_hours
- regular_pay
- overtime_pay
- holiday_pay
- night_differential_pay
- allowances_total
- bonuses_total
- gross_pay
- sss_contribution
- philhealth_contribution
- pagibig_contribution
- withholding_tax
- other_deductions
- total_deductions
- net_pay
- created_at
- updated_at
```

## Benefits of This Approach

### 1. Clear Separation of Concerns
- Draft = Dynamic (live calculations)
- Processing = Static (locked snapshots)
- Approved = Final (payslip ready)

### 2. Data Integrity
- Snapshots preserve exact calculation values
- Immune to retroactive changes in DTR or settings
- Audit trail of when calculations were locked

### 3. Performance
- Draft calculations only happen when viewing
- Processing/approved payrolls use pre-calculated values
- No need to recalculate static payrolls

### 4. User Experience
- Clear visual indicators of calculation mode
- Intuitive workflow from draft → processing → approved
- Prevents accidental modifications of finalized payrolls

### 5. Maintainability
- Focused, clean controller with single responsibility
- Removed duplicate and unused functions
- Clear method naming and documentation

## Implementation Steps

1. **Replace Controller**: Use `PayrollController_clean.php` as main controller
2. **Replace View**: Use `show_clean.blade.php` as main payroll view
3. **Update Routes**: Implement routes from `payroll_clean_routes.php`
4. **Test Flow**: Create draft → submit to processing → approve
5. **Verify Snapshots**: Ensure data is properly locked in processing mode
6. **Remove Old Code**: Clean up unused methods and views

## Future Enhancements

- Email notifications on status changes
- Bulk approve multiple payrolls
- Export payroll reports
- Integration with payment systems
- Advanced filtering and search
- Payroll templates for recurring periods
