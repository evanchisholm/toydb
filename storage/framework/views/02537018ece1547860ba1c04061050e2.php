<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-900">My Toy Collection</h1>
            <a href="<?php echo e(route('toys.export-csv', request()->query())); ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </a>
        </div>
        
        <!-- Search and Filters -->
        <form method="GET" action="<?php echo e(route('toys.index')); ?>" class="bg-white p-6 rounded-lg shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="<?php echo e(request('search')); ?>" 
                           placeholder="Search toys..." 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e(request('category') == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                    <select name="condition" id="condition" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Conditions</option>
                        <?php $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e(request('condition') == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Toys Grid -->
    <?php if($toys->count() > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php $__currentLoopData = $toys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $toy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition overflow-hidden">
                    <?php if($toy->image_path): ?>
                        <div class="w-full h-48 overflow-hidden bg-gray-100 flex items-center justify-center">
                            <img src="<?php echo e(asset('storage/' . $toy->image_path)); ?>" alt="<?php echo e($toy->name); ?>" 
                                 class="max-w-full max-h-full object-contain">
                        </div>
                    <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                            <span class="text-6xl">🎮</span>
                        </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate"><?php echo e($toy->name); ?></h3>
                        <div class="space-y-1 text-sm text-gray-600 mb-3">
                            <?php if($toy->brand): ?>
                                <p><span class="font-medium">Brand:</span> <?php echo e($toy->brand); ?></p>
                            <?php endif; ?>
                            <?php if($toy->category): ?>
                                <p><span class="font-medium">Category:</span> <?php echo e($toy->category); ?></p>
                            <?php endif; ?>
                            <p><span class="font-medium">Condition:</span> 
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php if($toy->condition == 'Mint'): ?> bg-green-100 text-green-800
                                    <?php elseif($toy->condition == 'Excellent'): ?> bg-blue-100 text-blue-800
                                    <?php elseif($toy->condition == 'Good'): ?> bg-yellow-100 text-yellow-800
                                    <?php else: ?> bg-gray-100 text-gray-800
                                    <?php endif; ?>">
                                    <?php echo e($toy->condition); ?>

                                </span>
                            </p>
                            <?php if($toy->estimated_value): ?>
                                <p><span class="font-medium">Value:</span> $<?php echo e(number_format($toy->estimated_value, 2)); ?></p>
                            <?php endif; ?>
                            <?php if($toy->ebay_listings_count !== null): ?>
                                <p class="text-xs text-blue-600">
                                    <span class="font-medium">eBay:</span> <?php echo e(number_format($toy->ebay_listings_count)); ?> listings
                                    <?php if($toy->ebay_average_price): ?>
                                        • Avg: $<?php echo e(number_format($toy->ebay_average_price, 2)); ?>

                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <a href="<?php echo e(route('toys.show', $toy)); ?>" 
                               class="flex-1 text-center bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-2 rounded text-sm font-medium transition">
                                View
                            </a>
                            <a href="<?php echo e(route('toys.edit', $toy)); ?>" 
                               class="flex-1 text-center bg-gray-50 text-gray-600 hover:bg-gray-100 px-3 py-2 rounded text-sm font-medium transition">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <?php echo e($toys->links()); ?>

        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <p class="text-gray-500 text-lg mb-4">No toys found in your collection.</p>
            <a href="<?php echo e(route('toys.create')); ?>" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition">
                Add Your First Toy
            </a>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evanchisholm/Developer/toydb/resources/views/toys/index.blade.php ENDPATH**/ ?>