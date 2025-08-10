<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Holiday Settings - Test</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <p>Holiday page is working!</p>
        <p>Total holidays: {{ $holidays->flatten()->count() ?? 0 }}</p>
    </div>
</div>
</x-app-layout>
