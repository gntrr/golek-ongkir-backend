<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RajaOngkirClient
{
    protected string $base;
    protected array $keys = [];

    public function __construct()
    {
        $this->base = rtrim(config('services.rajaongkir.base'), '/');
        $keysEnv = config('services.rajaongkir.keys'); // comma-separated
        $single  = config('services.rajaongkir.key');

        $keys = [];
        if (is_string($keysEnv) && trim($keysEnv) !== '') {
            $keys = array_map('trim', explode(',', $keysEnv));
        }
        if (empty($keys) && is_string($single) && trim($single) !== '') {
            $keys = [trim($single)];
        }

        // unique & non-empty
        $this->keys = array_values(array_filter(array_unique($keys)));
    }

    protected function headers(string $key): array
    {
        // Sesuaikan kalau Komerce memakai header lain (mis. 'X-API-KEY')
        return ['key' => $key];
    }

    private function ok($res)
    {
        return $res->ok()
            ? ['error'=>false,'data'=>$res->json()]
            : ['error'=>true,'status'=>$res->status(),'message'=>$res->json('message') ?? $res->body()];
    }

    private function limitedCacheKey(string $key): string
    {
        return 'ro:key:limited:'.md5($key);
    }

    private function isMarkedLimited(string $key): bool
    {
        return (bool) Cache::get($this->limitedCacheKey($key), false);
    }

    private function markLimited(string $key, int $minutes = 720): void // default 12 jam
    {
        Cache::put($this->limitedCacheKey($key), true, now()->addMinutes($minutes));
    }

    private function isRateLimitedResponse(\Illuminate\Http\Client\Response $res): bool
    {
        if ($res->status() === 429) return true;
        $msg = '';
        $json = $res->json();
        if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
            $msg = strtolower($json['message']);
        } else {
            $msg = strtolower((string) $res->body());
        }
        return str_contains($msg, 'limit') || str_contains($msg, 'quota');
    }

    /**
     * Execute HTTP call with key failover on rate limit.
     * The callback receives the current key and must return a Response.
     */
    private function withFailover(callable $fn): \Illuminate\Http\Client\Response
    {
        $candidates = array_values(array_filter($this->keys, fn($k) => !$this->isMarkedLimited($k)));
        $candidates = !empty($candidates) ? $candidates : $this->keys; // jika semua ditandai limited, coba tetap urutan asli

        $last = null;
        foreach ($candidates as $key) {
            $res = $fn($key);
            $last = $res;
            if ($this->isRateLimitedResponse($res)) {
                $this->markLimited($key);
                continue; // coba key berikutnya
            }
            return $res; // bukan rate limit => kembalikan segera (ok/4xx selain limit)
        }
        // semua key rate limited atau tidak ada key => kembalikan respons terakhir
        return $last ?? response()->noContent(429);
    }

    // Step-by-step
    public function provinces() {
        $res = $this->withFailover(fn($key) =>
            Http::withHeaders($this->headers($key))->get("$this->base/destination/province")
        );
        return $this->ok($res);
    }

    public function cities(int $provinceId) {
        $res = $this->withFailover(fn($key) =>
            Http::withHeaders($this->headers($key))->get("$this->base/destination/city/$provinceId")
        );
        return $this->ok($res);
    }

    public function districts(int $cityId) {
        $res = $this->withFailover(fn($key) =>
            Http::withHeaders($this->headers($key))->get("$this->base/destination/district/$cityId")
        );
        return $this->ok($res);
    }

    // Direct search
    public function search(string $term) {
        $res = $this->withFailover(fn($key) =>
            Http::withHeaders($this->headers($key))
                ->get("$this->base/destination/domestic-destination", [ 'search' => $term ])
        );
        return $this->ok($res);
    }

    // Calculate domestic
    public function cost(int $origin, int $destination, int $weight, string $courier)
    {
        $url = "{$this->base}/calculate/district/domestic-cost"; // <-- perbaiki path

        $res = $this->withFailover(fn($key) =>
            Http::asForm()
                ->withHeaders(['key' => $key, 'Accept' => 'application/json'])
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 500)
                ->withOptions([
                    'curl' => [ CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4 ],
                ])
                ->post($url, [
                    'origin'      => $origin,
                    'destination' => $destination,
                    'weight'      => $weight,
                    'courier'     => $courier, // gunakan ':' untuk multi (jne:pos:jnt)
                ])
        );

        return $this->ok($res);
    }


    // Tracking (opsional)
    public function track(string $courier, string $waybill, ?string $lastPhone5 = null) {
    // Kirim kedua field (waybill & awb) untuk kompatibilitas dengan variasi upstream
    $payload = ['courier'=>$courier, 'waybill'=>$waybill, 'awb'=>$waybill];
        if ($courier === 'jne' && $lastPhone5) $payload['last_phone_number'] = $lastPhone5;

        $res = $this->withFailover(fn($key) =>
            Http::asForm()->withHeaders($this->headers($key))
                ->post("$this->base/track/waybill", $payload)
        );

        if ($res->ok()) {
            $json = $res->json();
            // Kembalikan langsung objek 'data' (tanpa 'meta') sesuai permintaan
            return is_array($json) && array_key_exists('data', $json) ? $json['data'] : $json;
        }

        return ['error'=>true,'status'=>$res->status(),'message'=>$res->json('message') ?? $res->body()];
    }
}
