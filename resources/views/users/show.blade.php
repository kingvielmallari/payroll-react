@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">User Details: {{ $user->name }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('users.edit', $user) }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit User
            </a>
            <a href="{{ route('users.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Users
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-20 w-20">
                    <div class="h-20 w-20 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-2xl font-medium text-gray-700">
                            {{ substr($user->name, 0, 2) }}
                        </span>
                    </div>
                </div>
                <div class="ml-6">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-lg text-gray-600">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Role</dt>
                    <dd class="mt-1">
                        @php
                            $roleColors = [
                                'system_admin' => 'bg-purple-100 text-purple-800',
                                'hr_head' => 'bg-blue-100 text-blue-800',
                                'hr_staff' => 'bg-green-100 text-green-800',
                                'employee' => 'bg-gray-100 text-gray-800',
                            ];
                            $roleLabels = [
                                'system_admin' => 'System Administrator',
                                'hr_head' => 'HR Head',
                                'hr_staff' => 'HR Staff',
                                'employee' => 'Employee',
                            ];
                        @endphp
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $roleLabels[$user->role] ?? $user->role }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F d, Y \a\t g:i A') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('F d, Y \a\t g:i A') }}</dd>
                </div>

                @if($user->employee_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Employee ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->employee_id }}</dd>
                </div>
                @endif

                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Current Permissions</dt>
                    <dd class="mt-1">
                        <div class="flex flex-wrap gap-2">
                            @foreach($user->getAllPermissions() as $permission)
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection