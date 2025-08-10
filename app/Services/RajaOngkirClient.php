<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RajaOngkirClient
{
    protected string $base;
    protected string $key;

    public function __construct()
    {
        $this->base = rtrim(config('services.rajaongkir.base'), '/');
        $this->key  = config('services.rajaongkir.key');
    }

    protected function headers(): array
    {
        // Sesuaikan kalau Komerce memakai header lain (mis. 'X-API-KEY')
        return ['key' => $this->key];
    }

    private function ok($res)
    {
        return $res->ok()
            ? ['error'=>false,'data'=>$res->json()]
            : ['error'=>true,'status'=>$res->status(),'message'=>$res->json('message') ?? $res->body()];
    }

    // Step-by-step
    public function provinces() {
        $res = Http::withHeaders($this->headers())->get("$this->base/destination/province");
        return $this->ok($res);
    }

    public function cities(int $provinceId) {
        $res = Http::withHeaders($this->headers())->get("$this->base/destination/city/$provinceId");
        return $this->ok($res);
    }

    public function districts(int $cityId) {
        $res = Http::withHeaders($this->headers())->get("$this->base/destination/district/$cityId");
        return $this->ok($res);
    }

    // Direct search
    public function search(string $term) {
        $res = Http::withHeaders($this->headers())
        ->get("$this->base/destination/domestic-destination", [
            'search' => $term,
        ]);
        return $this->ok($res);
    }

    // Calculate domestic
    public function cost(int $origin, int $destination, int $weight, string $courier)
    {
        $url = "{$this->base}/calculate/district/domestic-cost"; // <-- perbaiki path

        $res = Http::asForm()
            ->withHeaders(['key' => $this->key, 'Accept' => 'application/json'])
            ->connectTimeout(5)         // waktu nunggu konek
            ->timeout(20)               // total timeout
            ->retry(2, 500)             // retry 2x jeda 500ms
            ->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // paksa IPv4
                ],
            ])
            ->post($url, [
                'origin'      => $origin,
                'destination' => $destination,
                'weight'      => $weight,
                'courier'     => $courier,      // pakai ':' untuk multi (jne:pos:jnt)
                // 'price'    => 'lowest',      // opsional, kalau mau
            ]);

        return $res->ok()
            ? $res->json()
            : ['error'=>true,'status'=>$res->status(),'message'=>$res->body()];
    }


    // Tracking (opsional)
    public function track(string $courier, string $waybill, ?string $lastPhone5 = null) {
        $payload = ['courier'=>$courier, 'waybill'=>$waybill];
        if ($courier === 'jne' && $lastPhone5) $payload['last_phone_number'] = $lastPhone5;

        $res = Http::asForm()->withHeaders($this->headers())
              ->post("$this->base/track/waybill", $payload);

        return $this->ok($res);
    }
}
