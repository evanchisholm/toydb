<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'category',
        'description',
        'condition',
        'purchase_price',
        'estimated_value',
        'purchase_date',
        'manufacture_date',
        'serial_number',
        'in_box',
        'notes',
        'image_path',
        'ebay_listings_count',
        'ebay_average_price',
        'ebay_last_searched_at',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'ebay_average_price' => 'decimal:2',
        'purchase_date' => 'date',
        'manufacture_date' => 'date',
        'in_box' => 'boolean',
        'ebay_last_searched_at' => 'datetime',
    ];

    /**
     * Get the condition options
     */
    public static function getConditionOptions(): array
    {
        return [
            'Mint' => 'Mint',
            'Excellent' => 'Excellent',
            'Good' => 'Good',
            'Fair' => 'Fair',
            'Poor' => 'Poor',
        ];
    }

    /**
     * Get the category options
     */
    public static function getCategoryOptions(): array
    {
        return [
            'Action Figures' => 'Action Figures',
            'Dolls' => 'Dolls',
            'Vehicles' => 'Vehicles',
            'Plush Toys' => 'Plush Toys',
            'Board Games' => 'Board Games',
            'Collectibles' => 'Collectibles',
            'Vintage Toys' => 'Vintage Toys',
            'Other' => 'Other',
        ];
    }
}

