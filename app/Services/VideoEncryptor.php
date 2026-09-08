<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class VideoEncryptor
{
    /**
     * Encrypt a video URL with timestamp
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public static function encrypt(string $url): string
    {
        try {
            $secretKey = config('video.secret_key');

            if (empty($secretKey)) {
                throw new Exception('VIDEO_SECRET_KEY not configured');
            }

            // Generate a random IV for each encryption
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));

            // Create payload: url::timestamp
            $timestamp = time();
            $payload = $url . '::' . $timestamp;

            // Encrypt the payload
            $encrypted = openssl_encrypt(
                $payload,
                'AES-256-CBC',
                $secretKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            // Combine IV and encrypted data, then base64 encode
            $combined = $iv . $encrypted;
            return base64_encode($combined);

        } catch (Exception $e) {
            Log::error('Video encryption failed', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);
            throw new Exception('Failed to encrypt video URL: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt a video URL and validate timestamp
     *
     * @param string $encrypted
     * @return string
     * @throws Exception
     */
    public static function decrypt(string $encrypted): string
    {
        try {
            $secretKey = config('video.secret_key');

            if (empty($secretKey)) {
                throw new Exception('VIDEO_SECRET_KEY not configured');
            }

            // Decode the base64 string
            $combined = base64_decode($encrypted);

            if ($combined === false) {
                throw new Exception('Invalid base64 encoding');
            }

            // Extract IV and encrypted data
            $ivLength = openssl_cipher_iv_length('AES-256-CBC');
            $iv = substr($combined, 0, $ivLength);
            $encryptedData = substr($combined, $ivLength);

            // Decrypt the data
            $decrypted = openssl_decrypt(
                $encryptedData,
                'AES-256-CBC',
                $secretKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }

            // Parse the payload
            $parts = explode('::', $decrypted);

            if (count($parts) !== 2) {
                throw new Exception('Invalid encrypted payload format');
            }

            [$url, $timestamp] = $parts;

            // Optional: Validate timestamp (URLs expire after X seconds)
            $currentTime = time();
            $urlAge = $currentTime - (int) $timestamp;
            $maxAge = config('video.max_age', 86400); // 24 hours default

            if ($urlAge > $maxAge) {
                throw new Exception('Video URL has expired');
            }

            return $url;

        } catch (Exception $e) {
            Log::error('Video decryption failed', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to decrypt video URL: ' . $e->getMessage());
        }
    }

    /**
     * Check if a string looks like an encrypted payload
     *
     * @param string $string
     * @return bool
     */
    public static function isEncrypted(string $string): bool
    {
        try {
            $decoded = base64_decode($string, true);
            if ($decoded === false) {
                return false;
            }

            $ivLength = openssl_cipher_iv_length('AES-256-CBC');
            return strlen($decoded) > $ivLength;
        } catch (Exception $e) {
            return false;
        }
    }
}
