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
                Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} employees
            </div>
        </div>
        <div class="text-sm text-gray-500">
            Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }}
        </div>
    </div>
    @if($employees->count() > 0)
        {{ $employees->links() }}
    @endif
</div>