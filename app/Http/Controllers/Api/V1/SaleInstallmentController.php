<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PaySaleInstallmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaySaleInstallmentRequest;
use App\Http\Resources\V1\SaleInstallmentResource;
use App\Models\SaleInstallment;
use DomainException;
use Illuminate\Http\JsonResponse;

class SaleInstallmentController extends Controller
{
    public function __construct(
        private PaySaleInstallmentAction $paySaleInstallmentAction
    ) {}

    public function markPaid(PaySaleInstallmentRequest $request, SaleInstallment $saleInstallment): JsonResponse
    {
        try {
            $updated = ($this->paySaleInstallmentAction)(
                $saleInstallment,
                $request->user(),
                (int) $request->validated('payment_method_id'),
                $request->validated('paid_date')
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new SaleInstallmentResource($updated))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}
