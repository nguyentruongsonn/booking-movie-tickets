<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PayOS\PayOS;
use Exception;

class PaymentController extends Controller
{
    private function getPayOS()
    {
        $payOS = new PayOS(
            env('PAYOS_CLIENT_ID'),
            env('PAYOS_API_KEY'),
            env('PAYOS_CHECKSUM_KEY'),
        );
        return $payOS;
    }

    public function createPayment(Request $request)
    {
        $payOS = $this->getPayOS();
        $amount = (int)$request->amount;
        if($amount<=0)
            {
                return response()->json(['status'=>'error','message'=>'So tien khong hop le'],400);
            }
        $orderCode = intval(substr(time(),-6));
        $paymentData = [
            'orderCode' =>$orderCode,
            'amount'=>$amount,
            'description'=>'Thanh toan ve xem phim',
            'cancelUrl'=>env('APP_URL').'/index',
            'returnUrl'=>env('APP_URL').'/index',
            'items'=>[[
                'name'=>'Ve xem phim',
                'quantity'=>1,
                'price'=>$amount
            ]]
        ];

        try{
            $response = $payOS->createPaymentLink($paymentData);
            return response()->json([
                'status'=>'success',
                'checkoutUrl'=>$response['checkoutUrl'],
                'orderCode'=>$orderCode
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'=>'error',
                'message'=>$e->getMessage()
            ], 500);
        }
    }

}
