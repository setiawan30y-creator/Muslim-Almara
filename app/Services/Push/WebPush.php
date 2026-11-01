<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pengirim Web Push tanpa pustaka pihak ketiga (cukup ekstensi OpenSSL):
 * - Enkripsi payload: RFC 8291 (aes128gcm) — hanya browser pelanggan yang dapat membuka isinya.
 * - Autentikasi server: VAPID / RFC 8292 (JWT ES256).
 * Kunci dibuat sekali dengan: php artisan almara:push:keys
 * @2026-Alifiandra
 */
class WebPush
{
    /** Layanan push resmi peramban — mencegah server dipakai mengirim permintaan ke alamat sembarang (SSRF). */
    public const ALLOWED_HOSTS = [
        'fcm.googleapis.com', 'android.googleapis.com', 'updates.push.services.mozilla.com', 'push.services.mozilla.com',
        'web.push.apple.com', 'api.push.apple.com',
    ];
    public const ALLOWED_SUFFIXES = ['.push.apple.com', '.notify.windows.com', '.push.services.mozilla.com'];

    private const EC_PRIV_PREFIX = '30770201010420';
    private const EC_PRIV_MIDDLE = 'a00a06082a8648ce3d030107a144034200';
    private const EC_PUB_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public static function b64u(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64uDecode(string $s): string
    {
        $bin = base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4), true);
        if ($bin === false) {
            throw new RuntimeException('Base64url tidak valid.');
        }

        return $bin;
    }

    public static function isAllowedEndpoint(string $url): bool
    {
        $p = parse_url($url);
        if (($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['port'])) {
            return false;
        }
        $host = strtolower($p['host']);
        if (in_array($host, self::ALLOWED_HOSTS, true)) {
            return true;
        }
        foreach (self::ALLOWED_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{public: string, private: string} base64url — publik 65 byte (0x04||X||Y), privat 32 byte (d) */
    public static function generateKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if (! $key) {
            throw new RuntimeException('OpenSSL tidak dapat membuat kunci EC P-256.');
        }
        $ec = openssl_pkey_get_details($key)['ec'];

        return [
            'public' => self::b64u("\x04".self::pad32($ec['x']).self::pad32($ec['y'])),
            'private' => self::b64u(self::pad32($ec['d'])),
        ];
    }

    private static function pad32(string $bin): string
    {
        return str_pad(substr($bin, -32), 32, "\0", STR_PAD_LEFT);
    }

    public static function privateKeyPem(string $d, string $publicUncompressed): string
    {
        $der = hex2bin(self::EC_PRIV_PREFIX).$d.hex2bin(self::EC_PRIV_MIDDLE).$publicUncompressed;

        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END EC PRIVATE KEY-----\n";
    }

    public static function publicKeyPem(string $publicUncompressed): string
    {
        $der = hex2bin(self::EC_PUB_PREFIX).$publicUncompressed;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    public function configured(): bool
    {
        return (bool) (config('almara.push.public_key') && config('almara.push.private_key'));
    }

    public function publicKey(): ?string
    {
        return config('almara.push.public_key') ?: null;
    }

    /**
     * Enkripsi RFC 8291. @return string badan permintaan (header aes128gcm + ciphertext)
     * @param string $uaPublic kunci p256dh pelanggan (65 byte), $authSecret 16 byte
     */
    public static function encrypt(string $payload, string $uaPublic, string $authSecret, ?string $salt = null, $asKey = null): string
    {
        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04" || strlen($authSecret) !== 16) {
            throw new RuntimeException('Kunci langganan push tidak valid.');
        }
        $asKey ??= openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($asKey)['ec'];
        $asPublic = "\x04".self::pad32($ec['x']).self::pad32($ec['y']);

        $peer = openssl_pkey_get_public(self::publicKeyPem($uaPublic));
        $ecdh = $peer ? openssl_pkey_derive($peer, $asKey, 32) : false;
        if ($ecdh === false) {
            throw new RuntimeException('ECDH gagal.');
        }
        $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0".$uaPublic.$asPublic, $authSecret);
        $salt ??= random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $tag = '';
        $cipher = openssl_encrypt($payload."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cipher === false) {
            throw new RuntimeException('Enkripsi gagal.');
        }

        return $salt.pack('N', 4096).chr(65).$asPublic.$cipher.$tag;
    }

    /** JWT VAPID (ES256) untuk origin layanan push. */
    public function vapidHeader(string $endpoint): string
    {
        $p = parse_url($endpoint);
        $aud = $p['scheme'].'://'.$p['host'];
        $pub = self::b64uDecode((string) config('almara.push.public_key'));
        $priv = self::b64uDecode((string) config('almara.push.private_key'));

        $head = self::b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64u(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => (string) config('almara.push.subject')]));
        $data = $head.'.'.$claims;
        if (! openssl_sign($data, $der, self::privateKeyPem($priv, $pub), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Tanda tangan VAPID gagal.');
        }

        return 'vapid t='.$data.'.'.self::b64u(self::derToRaw($der)).', k='.self::b64u($pub);
    }

    /** Tanda tangan ECDSA DER → r||s (64 byte) sesuai JWS. */
    public static function derToRaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7F;
        }
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            $len = ord($der[$pos + 1]);
            $int = substr($der, $pos + 2, $len);
            $out .= self::pad32(ltrim($int, "\0"));
            $pos += 2 + $len;
        }

        return $out;
    }

    /**
     * Kirim notifikasi. @return int status HTTP (201/200 berhasil; 404/410 langganan kedaluwarsa)
     * @param array<string, mixed> $message {title, body, url, tag}
     */
    public function send(string $endpoint, string $p256dh, string $auth, array $message, int $ttl = 1800): int
    {
        if (! $this->configured() || ! self::isAllowedEndpoint($endpoint)) {
            return 0;
        }
        $body = self::encrypt(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::b64uDecode($p256dh), self::b64uDecode($auth));

        $res = Http::timeout(10)->withHeaders([
            'Authorization' => $this->vapidHeader($endpoint),
            'Content-Encoding' => 'aes128gcm',
            'Content-Type' => 'application/octet-stream',
            'TTL' => (string) $ttl,
            'Urgency' => 'high',
        ])->withBody($body, 'application/octet-stream')->post($endpoint);

        return $res->status();
    }
}
