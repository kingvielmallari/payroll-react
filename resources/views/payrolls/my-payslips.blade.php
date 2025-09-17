@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Payslips</h1>
        <a href="{{ route('dashboard') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('payrolls.my-payslips') }}" class="flex items-center space-x-4">
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                <select name="year" id="year" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Years</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="pt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Filter
                </button>
                @if(request()->hasAny(['year']))
                    <a href="{{ route('payrolls.my-payslips') }}" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payslips List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($payrolls->isEmpty())
            <div class="p-6 text-center">
                <div class="text-gray-500 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Payslips Found</h3>
                <p class="text-gray-500">You don't have any approved payslips yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Approved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($payrolls as $payroll)
                            @php
                                $payrollDetail = $payroll->payrollDetails->first();
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ ucfirst($payroll->payroll_type) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payroll->pay_schedule }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($payrollDetail)
                                        ₱{{ number_format($payrollDetail->total_gross, 2) }}
                                    @else
                                        ₱0.00
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($payrollDetail)
                                        <span class="text-lg font-semibold text-green-600">
                                            ₱{{ number_format($payrollDetail->total_net, 2) }}
                                        </span>
                                    @else
                                        <span class="text-lg font-semibold text-gray-500">₱0.00</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payroll->approved_at ? $payroll->approved_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('payrolls.show', $payroll) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        View Payslip
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($payrolls->hasPages())
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $payrolls->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Summary Card -->
    @if($payrolls->isNotEmpty())
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Payroll Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-blue-800 text-sm font-medium">Total Payslips</div>
                    <div class="text-2xl font-bold text-blue-900">{{ $payrolls->total() }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-green-800 text-sm font-medium">Total Net Earnings</div>
                    @php
                        $totalNet = $payrolls->sum(function($payroll) {
                            return $payroll->payrollDetails->sum('total_net');
                        });
                    @endphp
                    <div class="text-2xl font-bold text-green-900">₱{{ number_format($totalNet, 2) }}</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-purple-800 text-sm font-medium">Average Net Pay</div>
                    <div class="text-2xl font-bold text-purple-900">
                        ₱{{ $payrolls->count() > 0 ? number_format($totalNet / $payrolls->count(), 2) : '0.00' }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection