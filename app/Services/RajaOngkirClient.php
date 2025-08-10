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
        return ['key' => $this->key];
    }

    public function provinces()
    {
        return Http::withHeaders($this->headers())
            ->get("{$this->base}/province")
            ->throw()
            ->json();
    }

    public function cities(array $params)
    {
        return Http::withHeaders($this->headers())
            ->get("{$this->base}/city", $params)
            ->throw()
            ->json();
    }

    public function cost(string $origin, string $destination, int $weight, string $courier)
    {
        // RajaOngkir expects x-www-form-urlencoded
        $payload = compact('origin','destination','weight','courier');

        return Http::asForm()
            ->withHeaders($this->headers())
            ->post("{$this->base}/cost", $payload)
            ->throw()
            ->json();
    }
}
