<div class="mt-6">
    <div class="flex items-center justify-between mb-5">
        <div class="text-sm text-gray-700">
            Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} employees
        </div>
        <div class="text-sm text-gray-500">
            Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }}
        </div>
    </div>
    {{ $employees->links() }}
</div>