<!-- Pagination Controls (Always Show) -->
<div class="px-6 py-3 border-t border-gray-200">
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
                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
            </div>
        </div>
        <div class="text-sm text-gray-500">
            Page {{ $users->currentPage() }} of {{ $users->lastPage() }}
        </div>
    </div>
    @if($users->count() > 0)
        {{ $users->links() }}
    @endif
</div>