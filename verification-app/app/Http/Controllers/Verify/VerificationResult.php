<?php

namespace App\Http\Controllers\Verify;

enum VerificationResult: string {
    case Verified = 'verified';
    case InvalidRecipent = 'invalid_recipient';
    case InvalidIssuer = 'invalid_issuer';
    case InvalidSignature = 'invalid_signature';
}
