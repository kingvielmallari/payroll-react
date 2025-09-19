@if($payrolls->count() > 0)
    <!-- Pagination -->
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
                    Showing {{ $payrolls->firstItem() ?? 0 }} to {{ $payrolls->lastItem() ?? 0 }} of {{ $payrolls->total() }} payrolls
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Page {{ $payrolls->currentPage() }} of {{ $payrolls->lastPage() }}
            </div>
        </div>
        {{ $payrolls->links() }}
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No payrolls found</h3>
        <p class="mt-1 text-sm text-gray-500">
            No payrolls match your current filter criteria. Try adjusting your filters or create a new payroll.
        </p>
    </div>
@endif