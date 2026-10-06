<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\PaymentMethodCatalog;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function __invoke(PaymentMethodCatalog $paymentMethods): JsonResponse
    {
        return response()->json([
            'data' => $paymentMethods->all(),
        ]);
    }
}
