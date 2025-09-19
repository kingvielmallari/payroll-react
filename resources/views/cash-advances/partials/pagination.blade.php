@if($cashAdvances->count() > 0)
<!-- Pagination -->
<div class="flex items-center justify-between mt-6">
    <div class="text-sm text-gray-700">
        Showing {{ $cashAdvances->firstItem() }} to {{ $cashAdvances->lastItem() }} 
        of {{ $cashAdvances->total() }} results
    </div>
    <div>
        {{ $cashAdvances->appends(request()->query())->links() }}
    </div>
</div>
@endif