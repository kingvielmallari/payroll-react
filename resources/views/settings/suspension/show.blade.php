<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Suspension Details</h1>
            <div class="flex space-x-3">
                <a href="{{ route('settings.suspension.edit', $suspension) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Edit
                </a>
                <a href="{{ route('settings.suspension.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Back to Suspensions
                </a>
            </div>
        </div>

        @if($suspension->status !== 'active')
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">This suspension is currently {{ $suspension->status }}.</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $suspension->name }}</h2>
                <p class="text-sm text-gray-600">{{ $suspension->code }}</p>
            </div>

            <div class="p-6">
                @if($suspension->description)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                        <p class="text-gray-900">{{ $suspension->description }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Suspension Type</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($suspension->type == 'suspended') bg-red-100 text-red-800
                            @elseif($suspension->type == 'partial_suspension') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst(str_replace('_', ' ', $suspension->type)) }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Duration</h3>
                        <p class="text-gray-900">
                            {{ $suspension->date_from ? $suspension->date_from->format('M d, Y') : 'Not set' }} - 
                            {{ $suspension->date_to ? $suspension->date_to->format('M d, Y') : 'Not set' }}
                        </p>
                    </div>

                    @if($suspension->time_from && $suspension->time_to)
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Time Period</h3>
                            <p class="text-gray-900">
                                {{ $suspension->time_from ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_from)->format('g:i A') : 'Not set' }} - 
                                {{ $suspension->time_to ? \Carbon\Carbon::createFromFormat('H:i:s', $suspension->time_to)->format('g:i A') : 'Not set' }}
                            </p>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Reason</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst(str_replace('_', ' ', $suspension->reason)) }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($suspension->status == 'active') bg-green-100 text-green-800
                            @elseif($suspension->status == 'draft') bg-gray-100 text-gray-800
                            @elseif($suspension->status == 'completed') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($suspension->status) }}
                        </span>
                    </div>
                </div>

                @if($suspension->detailed_reason)
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Detailed Reason</h3>
                        <p class="text-gray-900">{{ $suspension->detailed_reason }}</p>
                    </div>
                @endif

                @if($suspension->affectedDepartments()->count() > 0 || $suspension->affectedPositions()->count() > 0 || $suspension->affectedEmployees()->count() > 0)
                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Affected Areas</h3>
                        
                        @if($suspension->affectedDepartments()->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Departments</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($suspension->affectedDepartments() as $department)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $department->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($suspension->affectedPositions()->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Positions</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($suspension->affectedPositions() as $position)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            {{ $position->title }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($suspension->affectedEmployees()->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Employees</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($suspension->affectedEmployees() as $employee)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
