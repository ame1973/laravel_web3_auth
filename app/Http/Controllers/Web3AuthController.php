<?php

namespace App\Http\Controllers;

use App\Models\User;
use Elliptic\EC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class Web3AuthController extends Controller {
    public function authenticate(Request $request) {
        $nonce = session()->get('metamask-nonce');
        $message = $this->getSignatureMessage($nonce);
        $address = Str::lower($request->address);

        $this->verifySignature(
            $message,
            $request->signature,
            $request->address
        );

        $user = User::firstOrCreate(
            [
                'eth_address' => $address
            ],
            [
                'name' => $address,
                'email' => $address.'@temp.com',
                'password' => Hash::make('password'),
                'eth_address' => $address
            ]
        );

        Auth::login($user);

        session()->forget('metamask-nonce');
    }

    public function signature(Request $request) {
        // Generate some random nonce
        $code = Str::random(8);

        // Save in session
        session()->put('metamask-nonce', $code);

        // Create message with nonce
        return $this->getSignatureMessage($code);

    }

    private function getSignatureMessage($code) {
        return __("I have read and accept the terms and conditions.\nPlease sign me in.\n\nSecurity code (you can ignore this): :nonce", [
            'nonce' => $code
        ]);
    }

    public static function verifySignature($message, $signature, $address): bool {
        $hash = Keccak::hash(sprintf("\x19Ethereum Signed Message:\n%s%s", strlen($message), $message), 256);

        $sign = [
            "r" => substr($signature, 2, 64),
            "s" => substr($signature, 66, 64)
        ];

        $recId = ord(hex2bin(substr($signature, 130, 2))) - 27;

        if ($recId !== ($recId & 1)) {
            throw new \RuntimeException("Invalid Hex");
        }

        $publicKey = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recId);

        if ((string)Str::of($address)->after('0x')->lower() !=
            substr(Keccak::hash(substr(hex2bin($publicKey->encode('hex')), 1), 256), 24)) {

            throw new \RuntimeException("Invalid Signature Hash");
        }

        return true;
    }
}
