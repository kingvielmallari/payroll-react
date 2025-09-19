@if($users->hasPages())
    <div class="px-6 py-3 border-t border-gray-200">
        {{ $users->links() }}
    </div>
@endif