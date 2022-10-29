<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class Encryption
{
    // public static function signature(string $reference = null): string

    // {


public static function decryptString($encryptedData) {

$encryptedBin = hex2bin($encryptedData);

$iv = substr($encryptedBin, 0, 16);

$encryptedText = substr($encryptedBin, 16);

$key = substr(base64_encode(hash('sha256', config('services.mono.mono_key'), true)), 0, 32);

$algorithm = "aes-256-cbc";

$decryptedData = openssl_decrypt($encryptedText, $algorithm, $key, OPENSSL_RAW_DATA, $iv);


return $decryptedData;

}

}
