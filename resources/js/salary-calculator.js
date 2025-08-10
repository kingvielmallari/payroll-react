// Salary Calculator Functions
function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
}

function parseNumber(value) {
    if (typeof value === 'string') {
        return parseFloat(value.replace(/[^0-9.-]/g, '')) || 0;
    }
    return parseFloat(value) || 0;
}

function calculateSalaryRates(sourceField, sourceValue) {
    const value = parseNumber(sourceValue);
    const rates = {};

    // Base calculations (8 hours/day, 5 days/week, 22 days/month)
    switch (sourceField) {
        case 'hourly_rate':
            rates.hourly_rate = value;
            rates.daily_rate = value * 8;
            rates.weekly_rate = value * 8 * 5;
            rates.semi_monthly_rate = value * 8 * 11; // 22 days / 2
            rates.basic_salary = value * 8 * 22;
            break;

        case 'daily_rate':
            rates.hourly_rate = value / 8;
            rates.daily_rate = value;
            rates.weekly_rate = value * 5;
            rates.semi_monthly_rate = value * 11;
            rates.basic_salary = value * 22;
            break;

        case 'weekly_rate':
            rates.hourly_rate = value / (8 * 5);
            rates.daily_rate = value / 5;
            rates.weekly_rate = value;
            rates.semi_monthly_rate = value * 2.2; // 11 days / 5 days
            rates.basic_salary = value * 4.4; // 22 days / 5 days
            break;

        case 'semi_monthly_rate':
            rates.hourly_rate = value / (8 * 11);
            rates.daily_rate = value / 11;
            rates.weekly_rate = value / 2.2;
            rates.semi_monthly_rate = value;
            rates.basic_salary = value * 2;
            break;

        case 'basic_salary':
            rates.hourly_rate = value / (8 * 22);
            rates.daily_rate = value / 22;
            rates.weekly_rate = value / 4.4;
            rates.semi_monthly_rate = value / 2;
            rates.basic_salary = value;
            break;
    }

    return rates;
}

function updateSalaryFields(sourceField) {
    const sourceElement = document.getElementById(sourceField);
    if (!sourceElement) return;

    const sourceValue = sourceElement.value;
    if (!sourceValue || sourceValue === '0' || sourceValue === '') return;

    const rates = calculateSalaryRates(sourceField, sourceValue);

    // Update all other fields except the source field
    Object.keys(rates).forEach((field) => {
        if (field !== sourceField) {
            const element = document.getElementById(field);
            if (element) {
                element.value = rates[field].toFixed(2);
            }
        }
    });

    // Update calculation preview
    updateCalculationPreview(rates);
}

function updateCalculationPreview(rates) {
    const previewElement = document.getElementById('salary-preview');
    if (!previewElement) return;

    const hourly = rates.hourly_rate;
    const daily = rates.daily_rate;
    const weekly = rates.weekly_rate;
    const semiMonthly = rates.semi_monthly_rate;
    const monthly = rates.basic_salary;

    previewElement.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
            <div class="text-center">
                <p class="text-gray-600">Per Hour</p>
                <p class="font-semibold text-blue-600">${formatCurrency(hourly)}</p>
            </div>
            <div class="text-center">
                <p class="text-gray-600">Per Day</p>
                <p class="font-semibold text-green-600">${formatCurrency(daily)}</p>
            </div>
            <div class="text-center">
                <p class="text-gray-600">Per Week</p>
                <p class="font-semibold text-purple-600">${formatCurrency(weekly)}</p>
            </div>
            <div class="text-center">
                <p class="text-gray-600">Semi-Monthly</p>
                <p class="font-semibold text-orange-600">${formatCurrency(semiMonthly)}</p>
            </div>
            <div class="text-center">
                <p class="text-gray-600">Monthly</p>
                <p class="font-semibold text-red-600">${formatCurrency(monthly)}</p>
            </div>
        </div>
    `;
}

function initializeSalaryCalculator() {
    // Add event listeners to all salary fields
    const salaryFields = ['hourly_rate', 'daily_rate', 'weekly_rate', 'semi_monthly_rate', 'basic_salary'];

    salaryFields.forEach((field) => {
        const element = document.getElementById(field);
        if (element) {
            // Add input event listener for real-time calculation
            element.addEventListener('input', function () {
                if (this.value && parseNumber(this.value) > 0) {
                    updateSalaryFields(field);
                }
            });

            // Add blur event listener for final formatting
            element.addEventListener('blur', function () {
                if (this.value) {
                    const value = parseNumber(this.value);
                    this.value = value.toFixed(2);
                }
            });
        }
    });

    // Initialize preview with existing values on page load
    const basicSalary = document.getElementById('basic_salary');
    if (basicSalary && basicSalary.value && parseNumber(basicSalary.value) > 0) {
        updateSalaryFields('basic_salary');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializeSalaryCalculator);

// Export functions for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        calculateSalaryRates,
        updateSalaryFields,
        updateCalculationPreview,
        initializeSalaryCalculator,
    };
}
