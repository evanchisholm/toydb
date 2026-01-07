<?php

namespace App\Http\Controllers;

use App\Models\Toy;
use App\Services\EbayService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class ToyController extends Controller
{
    /**
     * Display a listing of the toys.
     */
    public function index(Request $request): View
    {
        $query = Toy::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by condition
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $toys = $query->paginate(12);

        return view('toys.index', [
            'toys' => $toys,
            'categories' => Toy::getCategoryOptions(),
            'conditions' => Toy::getConditionOptions(),
        ]);
    }

    /**
     * Show the form for creating a new toy.
     */
    public function create(): View
    {
        return view('toys.create', [
            'categories' => Toy::getCategoryOptions(),
            'conditions' => Toy::getConditionOptions(),
        ]);
    }

    /**
     * Store a newly created toy in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|string|in:Mint,Excellent,Good,Fair,Poor',
            'purchase_price' => 'nullable|numeric|min:0',
            'estimated_value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'manufacture_date' => 'nullable|date',
            'serial_number' => 'nullable|string|max:255',
            'in_box' => 'boolean',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('toys', 'public');
            $validated['image_path'] = $imagePath;
        }

        $validated['in_box'] = $request->has('in_box');

        Toy::create($validated);

        return redirect()->route('toys.index')
            ->with('success', 'Toy added successfully!');
    }

    /**
     * Display the specified toy.
     */
    public function show(Toy $toy): View
    {
        return view('toys.show', compact('toy'));
    }

    /**
     * Show the form for editing the specified toy.
     */
    public function edit(Toy $toy): View
    {
        return view('toys.edit', [
            'toy' => $toy,
            'categories' => Toy::getCategoryOptions(),
            'conditions' => Toy::getConditionOptions(),
        ]);
    }

    /**
     * Update the specified toy in storage.
     */
    public function update(Request $request, Toy $toy): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|string|in:Mint,Excellent,Good,Fair,Poor',
            'purchase_price' => 'nullable|numeric|min:0',
            'estimated_value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'manufacture_date' => 'nullable|date',
            'serial_number' => 'nullable|string|max:255',
            'in_box' => 'boolean',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($toy->image_path) {
                \Storage::disk('public')->delete($toy->image_path);
            }
            $imagePath = $request->file('image')->store('toys', 'public');
            $validated['image_path'] = $imagePath;
        }

        $validated['in_box'] = $request->has('in_box');

        $toy->update($validated);

        return redirect()->route('toys.index')
            ->with('success', 'Toy updated successfully!');
    }

    /**
     * Remove the specified toy from storage.
     */
    public function destroy(Toy $toy): RedirectResponse
    {
        // Delete image if exists
        if ($toy->image_path) {
            \Storage::disk('public')->delete($toy->image_path);
        }

        $toy->delete();

        return redirect()->route('toys.index')
            ->with('success', 'Toy deleted successfully!');
    }

    /**
     * Search eBay for the toy
     */
    public function searchEbay(Toy $toy, EbayService $ebayService): JsonResponse
    {
        $searchQuery = $toy->name;
        if ($toy->brand) {
            $searchQuery = "{$toy->brand} {$toy->name}";
        }

        $result = $ebayService->searchItems($searchQuery, $toy->brand);

        if ($result['success']) {
            $toy->update([
                'ebay_listings_count' => $result['count'],
                'ebay_average_price' => $result['average_price'],
                'ebay_last_searched_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'count' => $result['count'],
                'average_price' => $result['average_price'],
                'formatted_price' => $result['average_price'] ? '$' . number_format($result['average_price'], 2) : null,
                'last_searched' => now()->diffForHumans(),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to search eBay',
        ], 400);
    }

    /**
     * Export toys inventory as CSV
     */
    public function exportCsv(Request $request)
    {
        $toys = Toy::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $toys->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $toys->where('category', $request->category);
        }

        if ($request->filled('condition')) {
            $toys->where('condition', $request->condition);
        }

        $toys = $toys->orderBy('name')->get();

        $filename = 'toys-inventory-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($toys) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Name',
                'Brand',
                'Category',
                'Description',
                'Condition',
                'Purchase Price',
                'Estimated Value',
                'eBay Average Price',
                'eBay Listings Count',
                'Purchase Date',
                'Manufacture Date',
                'Serial Number',
                'In Original Box',
                'Notes',
                'Created At',
                'Updated At',
            ]);

            // Data rows
            foreach ($toys as $toy) {
                fputcsv($file, [
                    $toy->name,
                    $toy->brand ?? '',
                    $toy->category ?? '',
                    $toy->description ?? '',
                    $toy->condition,
                    $toy->purchase_price ? number_format($toy->purchase_price, 2) : '',
                    $toy->estimated_value ? number_format($toy->estimated_value, 2) : '',
                    $toy->ebay_average_price ? number_format($toy->ebay_average_price, 2) : '',
                    $toy->ebay_listings_count ?? '',
                    $toy->purchase_date ? $toy->purchase_date->format('Y-m-d') : '',
                    $toy->manufacture_date ? $toy->manufacture_date->format('Y-m-d') : '',
                    $toy->serial_number ?? '',
                    $toy->in_box ? 'Yes' : 'No',
                    $toy->notes ?? '',
                    $toy->created_at->format('Y-m-d H:i:s'),
                    $toy->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}

