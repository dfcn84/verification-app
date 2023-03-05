<?php
namespace App\Http\Controllers\Verify;

use DB;

class StoreVerificationResult
{
    public function __invoke($verificationResult): bool
    {   
        $tableName = 'verification_results';
        if (
            !isset($verificationResult) ||
            !isset($verificationResult['user_id']) ||
            !isset($verificationResult['result']) ||
            !isset($verificationResult['file_type'])
        ) return false;

        $data = [
            'user_id' => $verificationResult['user_id'],
            'result' => $verificationResult['result'],
            'file_type' => $verificationResult['file_type'],
            'timestamp' => date("c"),
        ];
        try {
            $results = DB::table($tableName)->insert($data);
        }
        catch (\Illuminate\Database\QueryException $e) {
            return false;
        }

        return true;
    }
}
