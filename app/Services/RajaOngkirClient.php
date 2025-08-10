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
    public function cost(int $origin, int $destination, int $weight, string $couriers) {
        $res = Http::asForm()
            ->withHeaders($this->headers())
            ->post("$this->base/calculate/domestic-cost", [
                'origin'      => $origin,
                'destination' => $destination,
                'weight'      => $weight,
                'couriers'    => $couriers, // "jne,pos,tiki"
            ]);

        return $this->ok($res);
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
