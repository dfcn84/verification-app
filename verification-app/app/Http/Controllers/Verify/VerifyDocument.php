<?php

namespace App\Http\Controllers\Verify;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class VerifyDocument
{
    
    public function __invoke(Request $request, StoreVerificationResult $storeVerificationResult, UploadedFile $uploadedFile): bool | Verification
    {
        $jsonDecode = json_decode($uploadedFile->get(), true);
        $response = new Verification;

        if (!$this->__hasValidRecipient($jsonDecode))
        {
            $response->data = ['result' => $this->__getVerificationResult(VerificationResult::InvalidRecipent)];
            $response->errorCode = Response::HTTP_OK;
            return $response;
        }

        if (!$this->__hasValidIssuer($jsonDecode))
        {
            $response->data = ['result' => $this->__getVerificationResult(VerificationResult::InvalidIssuer)];
            $response->errorCode = Response::HTTP_OK;
            return $response;
        }

        if (!$this->__hasValidSignature($jsonDecode))
        {
            $response->data = ['result' => $this->__getVerificationResult(VerificationResult::InvalidSignature)];
            $response->errorCode = Response::HTTP_OK;
            return $response;
        }

        $fileType = $uploadedFile->getClientOriginalExtension();
        $storeVerificationResult(
            [
                'user_id' => auth()->user()->id,
                'file_type' => $fileType,
                'result' => $this->__getVerificationResult(VerificationResult::Verified),
            ]
        );
        $response->data = [
            'issuer_name' => $jsonDecode['data']['issuer']['name'],
            'result' => $this->__getVerificationResult(VerificationResult::Verified),
        ];
        $response->errorCode = Response::HTTP_OK;

        return $response;
    }

    private function __getVerificationResult(VerificationResult $verificationResult)
    {
        return $verificationResult->value;
    }

    private function __hasValidRecipient(array $content): bool
    {
        if (
            !isset($content['data']) ||
            !isset($content['data']['recipient']) ||
            !isset($content['data']['recipient']['name']) || 
            !isset($content['data']['recipient']['email'])
        ) return false;

        return true;
    }

    private function __hasValidIssuer(array $content): bool
    {
        if (
            !isset($content['data']) ||
            !isset($content['data']['issuer']) ||
            !isset($content['data']['issuer']['name']) || 
            !isset($content['data']['issuer']['identityProof']) ||
            !isset($content['data']['issuer']['identityProof']['type']) ||
            !isset($content['data']['issuer']['identityProof']['key']) ||
            !isset($content['data']['issuer']['identityProof']['location']) ||
            !$this->__hasDnsTxt($content['data']['issuer']['identityProof']['key'], $content['data']['issuer']['identityProof']['location'])
        ) return false;

        return true;
    }

    private function __hasValidSignature(array $content): bool | string
    {
        if (
            !isset($content['signature']) ||
            !isset($content['signature']['targetHash']) ||
            !isset($content['data'])
        ) return false;
        
        $dottedData = Arr::dot($content['data']);
        $hashingAlgo = 'sha256';
        $hashedItems = [];

        foreach ($dottedData as $key => $value)
        {
            $hashedItem = hash($hashingAlgo, json_encode([ $key => $value ]));
            array_push($hashedItems, $hashedItem);
        }

        sort($hashedItems);
        $hashedHashedItems = hash($hashingAlgo, json_encode($hashedItems));

        if ($hashedHashedItems !== $content['signature']['targetHash']) return false;

        return true;        
    }

    private function __hasDnsTxt($key, $location) {
        $httpQuery = http_build_query([
            'name' => $location,
            'type' => 'TXT',
        ]);
        $response = Http::withUrlParameters([
            'endpoint' => 'https://dns.google/resolve',
            'query' => $httpQuery,
        ])->get('{+endpoint}?' . $httpQuery);
        
        if ($response->ok())
        {
            $responseBody = json_decode($response->body());
            if (isset($responseBody->Answer))
            {

                foreach ($responseBody->Answer as $answer)
                {

                    if ($answer->name === $location . '.')
                    {
                        $answerData = explode(';', str_replace('; ', ';', $answer->data));
                        foreach ($answerData as $dataAttribute)
                        {
                            if (substr($dataAttribute, 0, 2) === 'p=' && substr($dataAttribute, 2) === $key) return true;
                        }
                    }
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
}