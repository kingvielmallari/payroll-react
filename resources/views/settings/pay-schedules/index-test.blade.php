<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Pay Schedule Settings</h1>
                    
                    <p>Total schedules: {{ $schedules->count() }}</p>
                    
                    @if($schedules->count() > 0)
                        <div class="mt-4">
                            <h2 class="text-lg font-semibold mb-2">Schedules:</h2>
                            <ul>
                                @foreach($schedules as $schedule)
                                    <li class="py-2 border-b">
                                        <strong>{{ $schedule->name }}</strong> 
                                        ({{ $schedule->code }}) 
                                        - {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p>No schedules found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
