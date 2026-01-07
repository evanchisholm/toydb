<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayService
{
    private $appId;
    private $isSandbox;
    private $baseUrl;

    public function __construct()
    {
        $this->appId = env('EBAY_APP_ID');
        $this->isSandbox = env('EBAY_SANDBOX', false);
        
        // Use sandbox endpoint if sandbox mode is enabled
        if ($this->isSandbox) {
            $this->baseUrl = 'https://svcs.sandbox.ebay.com/services/search/FindingService/v1';
        } else {
            $this->baseUrl = 'https://svcs.ebay.com/services/search/FindingService/v1';
        }
    }

    /**
     * Search eBay for items matching the toy
     */
    public function searchItems(string $query, ?string $brand = null): array
    {
        if (!$this->appId) {
            return [
                'success' => false,
                'error' => 'eBay App ID not configured. Please set EBAY_APP_ID in your .env file.',
                'count' => 0,
                'average_price' => null,
            ];
        }

        // Build search query
        $searchQuery = $query;
        if ($brand) {
            $searchQuery = "{$brand} {$query}";
        }

        try {
            $params = [
                'OPERATION-NAME' => 'findItemsByKeywords',
                'SERVICE-VERSION' => '1.0.0',
                'SECURITY-APPNAME' => $this->appId,
                'RESPONSE-DATA-FORMAT' => 'JSON',
                'REST-PAYLOAD' => '',
                'keywords' => $searchQuery,
                'paginationInput.entriesPerPage' => 100,
                'sortOrder' => 'PricePlusShippingLowest',
            ];

            // Add item filters only for production (sandbox may have limited support)
            if (!$this->isSandbox) {
                $params['itemFilter(0).name'] = 'ListingType';
                $params['itemFilter(0).value(0)'] = 'AuctionWithBIN';
                $params['itemFilter(0).value(1)'] = 'FixedPrice';
                $params['itemFilter(0).value(2)'] = 'StoreInventory';
            }

            $response = Http::timeout(10)->get($this->baseUrl, $params);

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('eBay API Error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'sandbox' => $this->isSandbox,
                    'url' => $this->baseUrl,
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to connect to eBay API. Status: ' . $response->status() . ($this->isSandbox ? ' (Sandbox Mode)' : ''),
                    'count' => 0,
                    'average_price' => null,
                ];
            }

            $data = $response->json();

            if (!isset($data['findItemsByKeywordsResponse'][0])) {
                return [
                    'success' => false,
                    'error' => 'Invalid response from eBay API',
                    'count' => 0,
                    'average_price' => null,
                ];
            }

            $responseData = $data['findItemsByKeywordsResponse'][0];
            $ack = $responseData['ack'][0] ?? 'Failure';

            if ($ack !== 'Success') {
                $errorMessage = 'Unknown error';
                if (isset($responseData['errorMessage'][0]['error'][0]['message'][0])) {
                    $errorMessage = $responseData['errorMessage'][0]['error'][0]['message'][0];
                } elseif (isset($responseData['errorMessage'][0]['error'][0]['message'])) {
                    $errorMessage = is_array($responseData['errorMessage'][0]['error'][0]['message']) 
                        ? $responseData['errorMessage'][0]['error'][0]['message'][0]
                        : $responseData['errorMessage'][0]['error'][0]['message'];
                }
                
                Log::error('eBay API Response Error', [
                    'ack' => $ack,
                    'error' => $errorMessage,
                    'response' => $responseData,
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage . ($this->isSandbox ? ' (Sandbox Mode)' : ''),
                    'count' => 0,
                    'average_price' => null,
                ];
            }

            $searchResult = $responseData['searchResult'][0] ?? [];
            $items = $searchResult['item'] ?? [];
            $totalEntries = (int) ($searchResult['@count'] ?? 0);

            // Calculate average price
            $prices = [];
            foreach ($items as $item) {
                if (isset($item['sellingStatus'][0]['currentPrice'][0]['__value__'])) {
                    $price = (float) $item['sellingStatus'][0]['currentPrice'][0]['__value__'];
                    $prices[] = $price;
                }
            }

            $averagePrice = count($prices) > 0 ? round(array_sum($prices) / count($prices), 2) : null;

            return [
                'success' => true,
                'count' => $totalEntries,
                'average_price' => $averagePrice,
                'items' => $items,
            ];

        } catch (\Exception $e) {
            Log::error('eBay Search Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while searching eBay: ' . $e->getMessage(),
                'count' => 0,
                'average_price' => null,
            ];
        }
    }
}

