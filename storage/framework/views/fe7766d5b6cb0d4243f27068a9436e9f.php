<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="md:flex">
            <!-- Image Section -->
            <div class="md:w-1/2 bg-gray-100 flex items-center justify-center p-4">
                <?php if($toy->image_path): ?>
                    <img src="<?php echo e(asset('storage/' . $toy->image_path)); ?>" alt="<?php echo e($toy->name); ?>" 
                         class="max-w-full max-h-96 object-contain rounded-lg">
                <?php else: ?>
                    <div class="w-full h-96 bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center rounded-lg">
                        <span class="text-9xl">🎮</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details Section -->
            <div class="md:w-1/2 p-8">
                <div class="flex justify-between items-start mb-4">
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo e($toy->name); ?></h1>
                    <div class="flex gap-2">
                        <a href="<?php echo e(route('toys.edit', $toy)); ?>" 
                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            Edit
                        </a>
                        <form method="POST" action="<?php echo e(route('toys.destroy', $toy)); ?>" onsubmit="return confirm('Are you sure you want to delete this toy?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php if($toy->brand): ?>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Brand:</span>
                            <span class="ml-2 text-gray-900"><?php echo e($toy->brand); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if($toy->category): ?>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Category:</span>
                            <span class="ml-2 text-gray-900"><?php echo e($toy->category); ?></span>
                        </div>
                    <?php endif; ?>

                    <div>
                        <span class="text-sm font-medium text-gray-500">Condition:</span>
                        <span class="ml-2 px-3 py-1 rounded-full text-sm font-medium 
                            <?php if($toy->condition == 'Mint'): ?> bg-green-100 text-green-800
                            <?php elseif($toy->condition == 'Excellent'): ?> bg-blue-100 text-blue-800
                            <?php elseif($toy->condition == 'Good'): ?> bg-yellow-100 text-yellow-800
                            <?php else: ?> bg-gray-100 text-gray-800
                            <?php endif; ?>">
                            <?php echo e($toy->condition); ?>

                        </span>
                    </div>

                    <?php if($toy->description): ?>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Description:</span>
                            <p class="mt-1 text-gray-900"><?php echo e($toy->description); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <?php if($toy->purchase_price): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Purchase Price:</span>
                                <p class="text-lg font-semibold text-gray-900">$<?php echo e(number_format($toy->purchase_price, 2)); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($toy->estimated_value): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Estimated Value:</span>
                                <p class="text-lg font-semibold text-indigo-600">$<?php echo e(number_format($toy->estimated_value, 2)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- eBay Search Section -->
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-500">eBay Market Data</span>
                            <button id="searchEbayBtn" 
                                    onclick="searchEbay(<?php echo e($toy->id); ?>)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span id="searchEbayBtnText">Search eBay</span>
                                <span id="searchEbayBtnSpinner" class="hidden">Searching...</span>
                            </button>
                        </div>
                        <div id="ebayResults" class="space-y-2">
                            <?php if($toy->ebay_listings_count !== null): ?>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Listings Found:</span>
                                        <p class="text-lg font-semibold text-blue-600"><?php echo e(number_format($toy->ebay_listings_count)); ?></p>
                                    </div>
                                    <?php if($toy->ebay_average_price): ?>
                                        <div>
                                            <span class="text-sm font-medium text-gray-500">Average Price:</span>
                                            <p class="text-lg font-semibold text-blue-600">$<?php echo e(number_format($toy->ebay_average_price, 2)); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if($toy->ebay_last_searched_at): ?>
                                    <p class="text-xs text-gray-400">Last searched: <?php echo e($toy->ebay_last_searched_at->diffForHumans()); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-400">Click "Search eBay" to find current listings and prices</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <?php if($toy->purchase_date): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Purchase Date:</span>
                                <p class="text-gray-900"><?php echo e(\Carbon\Carbon::parse($toy->purchase_date)->format('F j, Y')); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($toy->manufacture_date): ?>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Manufacture Date:</span>
                                <p class="text-gray-900"><?php echo e(\Carbon\Carbon::parse($toy->manufacture_date)->format('F j, Y')); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if($toy->serial_number): ?>
                        <div class="pt-4 border-t border-gray-200">
                            <span class="text-sm font-medium text-gray-500">Serial Number:</span>
                            <p class="text-gray-900 font-mono"><?php echo e($toy->serial_number); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="pt-4 border-t border-gray-200">
                        <span class="text-sm font-medium text-gray-500">In Original Box:</span>
                        <span class="ml-2">
                            <?php if($toy->in_box): ?>
                                <span class="text-green-600 font-medium">✓ Yes</span>
                            <?php else: ?>
                                <span class="text-gray-400">✗ No</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if($toy->notes): ?>
                        <div class="pt-4 border-t border-gray-200">
                            <span class="text-sm font-medium text-gray-500">Notes:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-wrap"><?php echo e($toy->notes); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="<?php echo e(route('toys.index')); ?>" 
                       class="text-indigo-600 hover:text-indigo-700 font-medium">
                        ← Back to Collection
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function searchEbay(toyId) {
    const btn = document.getElementById('searchEbayBtn');
    const btnText = document.getElementById('searchEbayBtnText');
    const btnSpinner = document.getElementById('searchEbayBtnSpinner');
    const results = document.getElementById('ebayResults');
    
    btn.disabled = true;
    btnText.classList.add('hidden');
    btnSpinner.classList.remove('hidden');
    
    fetch(`/toys/${toyId}/search-ebay`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            results.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Listings Found:</span>
                        <p class="text-lg font-semibold text-blue-600">${data.count.toLocaleString()}</p>
                    </div>
                    ${data.formatted_price ? `
                    <div>
                        <span class="text-sm font-medium text-gray-500">Average Price:</span>
                        <p class="text-lg font-semibold text-blue-600">${data.formatted_price}</p>
                    </div>
                    ` : ''}
                </div>
                <p class="text-xs text-gray-400">Last searched: ${data.last_searched}</p>
            `;
        } else {
            results.innerHTML = `<p class="text-sm text-red-600">Error: ${data.error}</p>`;
        }
    })
    .catch(error => {
        results.innerHTML = `<p class="text-sm text-red-600">Error: Failed to search eBay</p>`;
        console.error('Error:', error);
    })
    .finally(() => {
        btn.disabled = false;
        btnText.classList.remove('hidden');
        btnSpinner.classList.add('hidden');
    });
}
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evanchisholm/Developer/toydb/resources/views/toys/show.blade.php ENDPATH**/ ?>