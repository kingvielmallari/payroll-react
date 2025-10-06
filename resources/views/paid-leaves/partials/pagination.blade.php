<!-- Pagination Controls (Always Show) -->
<div class="mt-6">
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <label for="per_page" class="text-sm font-medium text-gray-700">Records per page:</label>
                <select name="per_page" id="per_page" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="text-sm text-gray-700">
                Showing {{ $paidLeaves->firstItem() ?? 0 }} to {{ $paidLeaves->lastItem() ?? 0 }} of {{ $paidLeaves->total() }} paid leaves
            </div>
        </div>
        <div class="text-sm text-gray-500">
            Page {{ $paidLeaves->currentPage() }} of {{ $paidLeaves->lastPage() }}
        </div>
    </div>
    @if($paidLeaves->count() > 0)
        {{ $paidLeaves->links() }}
    @endif
</div>

{{-- @if($paidLeaves->count() == 0)
<div class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">No paid leaves found</h3>
    <p class="mt-1 text-sm text-gray-500">
        No paid leaves match your current filter criteria. Try adjusting your filters or create a new paid leave request.
    </p>
</div>
@endif --}}