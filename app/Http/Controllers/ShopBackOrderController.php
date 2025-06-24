<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ShopBackHmacService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShopBackOrderController extends Controller
{
    private ShopBackHmacService $hmacService;
    private string $baseUrl;

    public function __construct(ShopBackHmacService $hmacService)
    {
        $this->hmacService = $hmacService;
        $this->baseUrl = config('services.shopback.base_url');
    }

    public function createDynamicQrOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'posId' => 'required|string',
            'country' => 'required|string|size:2',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'referenceId' => 'required|string',
            'qrType' => 'required|string',
            'partner' => 'nullable|array',
            'partner.merchantId' => 'required_with:partner|string',
            'partner.merchantCategoryCode' => 'nullable|numeric',
            'partner.merchantTradingName' => 'nullable|string',
            'partner.merchantEntityId' => 'nullable|string',
            'orderMetadata' => 'nullable|array',
            'orderMetadata.terminalReference' => 'nullable|string',
            'orderMetadata.merchantOrderReference' => 'nullable|string',
            'webhookUrl' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Invalid request parameters: ' . $validator->errors()->first()
            ], 400);
        }

        $validated = $validator->validated();

        // Generate HMAC signature
        $endpoint = $this->baseUrl . '/v1/instore/order/create';
        $signatureData = $this->hmacService->generateSignature(
            'POST',
            $endpoint,
            $validated,
            'application/json',
        );

        // Prepare headers
        $headers = [
            'Authorization' => $signatureData['authorization'],
            'Date' => $signatureData['date']
        ];

        try {
            $response = Http::loggable()
                ->withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . '/v1/instore/order/create', $validated);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('ShopBack API Error - Create Order', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl . '/v1/instore/order/create'
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to connect to ShopBack API: ' . $e->getMessage()
            ], 500);
        }
    }

    public function scanConsumerQr(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'posId' => 'required|string',
            'country' => 'required|string|size:2',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'referenceId' => 'required|string',
            'consumerQrPayload' => 'required|string',
            'partner' => 'nullable|array',
            'partner.merchantId' => 'required_with:partner|string',
            'partner.merchantCategoryCode' => 'nullable|numeric',
            'partner.merchantTradingName' => 'nullable|string',
            'partner.merchantEntityId' => 'nullable|string',
            'orderMetadata' => 'nullable|array',
            'orderMetadata.terminalReference' => 'nullable|string',
            'orderMetadata.merchantOrderReference' => 'nullable|string',
            'webhookUrl' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Invalid request parameters: ' . $validator->errors()->first()
            ], 400);
        }

        $validated = $validator->validated();

        // Generate HMAC signature
        $endpoint = $this->baseUrl . '/v1/instore/order/scan';
        $signatureData = $this->hmacService->generateSignature(
            'POST',
            $endpoint,
            $validated,
            'application/json'
        );

        // Prepare headers
        $headers = [
            'Authorization' => $signatureData['authorization'],
            'Date' => $signatureData['date']
        ];

        try {
            $response = Http::loggable()
                ->withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . '/v1/instore/order/scan', $validated);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('ShopBack API Error - Scan QR', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl . '/v1/instore/order/scan'
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to connect to ShopBack API: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOrderStatus(string $referenceId): JsonResponse
    {
        if (empty($referenceId)) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Reference ID is required'
            ], 400);
        }

        // Generate HMAC signature
        $endpoint = $this->baseUrl . '/v1/instore/order/' . $referenceId;
        $signatureData = $this->hmacService->generateSignature(
            'GET',
            $endpoint,
            [],
            'application/json'
        );

        // Prepare headers
        $headers = [
            'Authorization' => $signatureData['authorization'],
            'Date' => $signatureData['date']
        ];

        try {
            $response = Http::loggable()
                ->withHeaders($headers)
                ->timeout(30)
                ->get($this->baseUrl . '/v1/instore/order/' . $referenceId);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('ShopBack API Error - Get Order Status', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl . '/v1/instore/order/' . $referenceId,
                'referenceId' => $referenceId
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to connect to ShopBack API: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refundOrder(Request $request, string $referenceId): JsonResponse
    {
        if (empty($referenceId)) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Reference ID is required'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string',
            'referenceId' => 'required|string',
            'posId' => 'nullable|string',
            'refundMetadata' => 'nullable|array',
            'refundMetadata.terminalReference' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Invalid request parameters: ' . $validator->errors()->first()
            ], 400);
        }

        $validated = $validator->validated();

        // Generate HMAC signature
        $endpoint = $this->baseUrl . '/v1/instore/order/' . $referenceId . '/refund';
        $signatureData = $this->hmacService->generateSignature(
            'POST',
            $endpoint,
            $validated,
            'application/json'
        );

        // Prepare headers
        $headers = [
            'Authorization' => $signatureData['authorization'],
            'Date' => $signatureData['date']
        ];

        try {
            $response = Http::loggable()
                ->withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . '/v1/instore/order/' . $referenceId . '/refund', $validated);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('ShopBack API Error - Refund Order', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl . '/v1/instore/order/' . $referenceId . '/refund',
                'referenceId' => $referenceId
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to connect to ShopBack API: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelOrder(Request $request, string $referenceId): JsonResponse
    {
        if (empty($referenceId)) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Reference ID is required'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 400,
                'message' => 'Invalid request parameters: ' . $validator->errors()->first()
            ], 400);
        }

        $validated = $validator->validated();

        // Generate HMAC signature
        $endpoint = $this->baseUrl . '/v1/instore/order/' . $referenceId . '/cancel';
        $signatureData = $this->hmacService->generateSignature(
            'POST',
            $endpoint,
            $validated,
            'application/json'
        );

        // Prepare headers
        $headers = [
            'Authorization' => $signatureData['authorization'],
            'Date' => $signatureData['date']
        ];

        try {
            $response = Http::loggable()
                ->withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . '/v1/instore/order/' . $referenceId . '/cancel', $validated);

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('ShopBack API Error - Cancel Order', [
                'error' => $e->getMessage(),
                'endpoint' => $this->baseUrl . '/v1/instore/order/' . $referenceId . '/cancel',
                'referenceId' => $referenceId
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'Failed to connect to ShopBack API: ' . $e->getMessage()
            ], 500);
        }
    }
}
