# Time Log / DTR Import Guide

## Overview
The Time Log / DTR (Daily Time Record) import feature allows you to bulk import employee time data from Excel (.xlsx, .xls) or CSV files. This is useful for importing data from time clock systems, external HR systems, or manual time tracking spreadsheets.

## How to Import Time Logs

### 1. Access the Import Feature
- Go to the DTR Management page (Time Logs)
- Click the "Import DTR" button (requires "import time logs" permission)

### 2. Download the Template
- Click "Download Template" to get a pre-formatted Excel file
- The template includes sample data and empty rows for your entries
- Use this template to ensure proper formatting

### 3. Prepare Your Data
Your import file must include the following columns:

#### Required Columns:
- **Employee Number** OR **Email** (one of these is required to identify the employee)
- **Date** (YYYY-MM-DD format, e.g., 2024-08-09)
- **Time In** (HH:MM format, e.g., 08:00 or 8:00 AM)

#### Optional Columns:
- **Time Out** (HH:MM format, can be empty for ongoing shifts)
- **Break In** (HH:MM format)
- **Break Out** (HH:MM format)
- **Employee Name** (for reference only, not used in import)
- **Remarks** (any additional notes)

### 4. Time Format Support
The system supports multiple time formats:
- 24-hour format: 08:00, 17:30, 22:45
- 12-hour format with AM/PM: 8:00 AM, 5:30 PM, 10:45 PM
- Excel time format (decimal values)

### 5. Automatic Calculations
The system automatically calculates:
- **Total Hours Worked** (including break deductions)
- **Regular Hours** (up to 8 hours per day)
- **Overtime Hours** (hours beyond 8 per day)
- **Late Hours** (based on 8:00 AM standard start time)
- **Undertime Hours** (hours below 8 per day)

### 6. Break Time Handling
- If Break In and Break Out times are provided, actual break time is deducted
- If break times are missing, 1 hour is automatically deducted from total hours
- This ensures consistent payroll calculations

### 7. Import Process
1. Upload your prepared Excel/CSV file
2. Choose whether to overwrite existing time logs (same date + employee)
3. Click "Import DTR Data"
4. Review the import results

### 8. Import Results
After import, you'll see:
- **Imported**: Number of successfully imported records
- **Skipped**: Number of records skipped (if overwrite is disabled)
- **Errors**: Number of records with errors

If there are errors, detailed error messages will be displayed to help you fix the data.

## Sample Data Format

| Employee Number | Email | Employee Name | Date | Time In | Time Out | Break In | Break Out | Remarks |
|---|---|---|---|---|---|---|---|---|
| EMP-2025-0001 | juan.doe@company.com | Juan Dela Cruz | 2024-08-09 | 08:00 | 17:00 | 12:00 | 13:00 | Regular day |
| EMP-2025-0002 | maria.santos@company.com | Maria Santos | 2024-08-09 | 09:00 | 18:30 | | | Overtime |
| EMP-2025-0003 | roberto.garcia@company.com | Roberto Garcia | 2024-08-09 | 08:30 | 17:00 | 12:30 | 13:30 | Late arrival |

## Important Notes

### Employee Identification
- You can use either Employee Number OR Email to identify employees
- The system will first try to match by Employee Number, then by Email
- If an employee is not found, that row will generate an error

### Date Validation
- Dates must be in YYYY-MM-DD format
- Dates cannot be in the future
- Excel date formats are automatically converted

### Automatic Approval
- All imported time logs are automatically approved
- The import user is recorded as the approver
- This ensures imported data is immediately available for payroll

### Overwrite Behavior
- If "Overwrite existing" is checked: existing time logs for the same date/employee will be updated
- If unchecked: existing time logs will be skipped and not modified

### File Size Limits
- Maximum file size: 2MB
- Supported formats: .xlsx, .xls, .csv

## Troubleshooting Common Issues

### "Employee not found" errors
- Verify employee numbers/emails match exactly with the system
- Check for extra spaces or formatting issues
- Ensure employees are active in the system

### "Invalid date format" errors
- Use YYYY-MM-DD format (e.g., 2024-08-09)
- Avoid text dates like "August 9, 2024"
- In Excel, format date columns as "Date" or "Short Date"

### "Time format" errors
- Use HH:MM format (e.g., 08:00, 17:30)
- Avoid seconds (08:00:00 should be 08:00)
- In Excel, format time columns as "Time"

### Import taking too long
- Large files may take time to process
- Break large imports into smaller batches
- Ensure the server has adequate memory/processing time

## Best Practices

1. **Start Small**: Test with a few records first
2. **Use Templates**: Always start with the downloaded template
3. **Validate Data**: Check data in Excel before importing
4. **Backup First**: Consider exporting existing data before large imports
5. **Regular Imports**: Import time logs regularly (daily/weekly) rather than large monthly batches
6. **Review Results**: Always review import results for errors or unexpected outcomes

## Integration with Payroll

Imported time logs are:
- Immediately available for payroll processing
- Included in DTR reports and calculations
- Used for government compliance reports (SSS, PhilHealth, Pag-IBIG)
- Factored into overtime, late, and undertime calculations

This ensures seamless integration between your time tracking and payroll systems.
