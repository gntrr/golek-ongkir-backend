<?php

namespace App\Http\Controllers;

use App\Http\Requests\CitiesRequest;
use App\Http\Requests\CostRequest;
use App\Services\RajaOngkirClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class RajaOngkirController extends Controller
{
    //
    public function __construct(private RajaOngkirClient $client) {}

    public function provinces(): JsonResponse
    {
        $data = Cache::remember('ro:provinces', now()->addDay(), function () {
            return $this->client->provinces();
        });

        return response()->json($this->ok($data));
    }

    public function cities(CitiesRequest $req): JsonResponse
    {
        $province = $req->string('province')->toString();
        $q        = $req->string('q')->toString();

        $key = 'ro:cities:' . md5($province.'|'.$q);

        $data = Cache::remember($key, now()->addDay(), function () use ($province, $q) {
            $params = [];
            if ($province !== '') $params['province'] = $province;
            if ($q !== '')        $params['q']        = $q; // supported by your proxy doc
            return $this->client->cities($params);
        });

        return response()->json($this->ok($data));
    }

    public function cost(CostRequest $req): JsonResponse
    {
        $origin      = $req->string('origin')->toString();
        $destination = $req->string('destination')->toString();
        $weight      = $req->integer('weight');
        $courier     = $req->string('courier')->toString();

        $key = 'ro:cost:' . md5("$origin|$destination|$weight|$courier");

        $data = Cache::remember($key, now()->addMinutes(10), function () use ($origin, $destination, $weight, $courier) {
            return $this->client->cost($origin, $destination, $weight, $courier);
        });

        return response()->json($this->ok($data));
    }

    /** Normalisasi response sederhana */
    private function ok($payload): array
    {
        return [
            'success' => true,
            'data'    => $payload['rajaongkir']['results'] ?? $payload['rajaongkir'] ?? $payload,
        ];
    }
}
