@forelse($users as $user)
    <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-150" 
        onclick="window.location='{{ route('users.show', $user) }}'"
        oncontextmenu="showContextMenu(event, {{ $user->id }}); return false;">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? $user->name, 0, 1)) }}
                        </span>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                </div>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">{{ $user->email }}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            @if($user->roles->count() > 0)
                @foreach($user->roles as $role)
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mb-1
                        @if($role->name === 'System Administrator') bg-red-100 text-red-800
                        @elseif($role->name === 'HR Head') bg-purple-100 text-purple-800
                        @elseif($role->name === 'HR Staff') bg-indigo-100 text-indigo-800
                        @elseif($role->name === 'Employee') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $role->name }}
                    </span>
                    @if(!$loop->last)<br>@endif
                @endforeach
            @else
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                    No Role
                </span>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                @if($user->status === 'active') bg-green-100 text-green-800
                @elseif($user->status === 'inactive') bg-yellow-100 text-yellow-800
                @elseif($user->status === 'suspended') bg-red-100 text-red-800
                @else bg-gray-100 text-gray-800
                @endif">
                {{ ucfirst($user->status) }}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $user->created_at->format('M d, Y') }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
            No users found.
        </td>
    </tr>
@endforelse