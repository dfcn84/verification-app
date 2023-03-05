<?php
namespace App\Http\Controllers\Verify;

class Verification 
{
    public function __construct(
        public array $data = [],
        public int $errorCode = 0,
    ) {}
}