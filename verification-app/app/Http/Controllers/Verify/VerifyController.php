<?php

namespace App\Http\Controllers\Verify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class VerifyController extends Controller
{
    public function store(Request $request, StoreVerificationResult $storeVerificationResult, VerifyDocument $verifyDocument): JsonResponse
    {   
        $inputName = 'uploadedFile';
        $response = [];

        if (!$request->hasFile($inputName))
        {
            return response()->json($response, Response::HTTP_OK);
        }

        $uploadedFile = $request->file($inputName);
        $verificationResult = $verifyDocument($request, $storeVerificationResult, $uploadedFile);

        return response()->json($verificationResult->data, $verificationResult->errorCode);
        
    }
}