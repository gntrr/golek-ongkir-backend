<?php

namespace App\Http\Controllers;

use App\Http\Requests\{CitiesRequest, DistrictsRequest, SearchRequest, CostRequest};
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class RajaOngkirController extends Controller
{
    public function __construct(private \App\Services\RajaOngkirClient $client) {}

    public function provinces() {
        return response()->json(
            Cache::remember('ko:provinces', now()->addDay(), fn() => $this->client->provinces())
        );
    }

    public function cities(CitiesRequest $r) {
        $id = (int)$r->input('province');
        return response()->json(
            Cache::remember("ko:cities:$id", now()->addDay(), fn() => $this->client->cities($id))
        );
    }

    public function districts(DistrictsRequest $r) {
        $id = (int)$r->input('city');
        return response()->json(
            Cache::remember("ko:districts:$id", now()->addDay(), fn() => $this->client->districts($id))
        );
    }

    public function search(SearchRequest $r) {
        $term = $r->string('search')->toString();
        $cacheKey = 'ko:search:'.md5($term);

        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($term) {
            return $this->client->search($term);
        });

        return response()->json($data);
    }

    public function cost(CostRequest $r) {
        $origin      = (int)$r->input('origin');
        $destination = (int)$r->input('destination');
        $weight      = (int)$r->input('weight');
        $couriers    = $r->input('courier'); // "jne,pos,tiki"

        $key = "ko:cost:".md5("$origin|$destination|$weight|$couriers");

        return response()->json(
            Cache::remember($key, now()->addMinutes(10),
                fn() => $this->client->cost($origin,$destination,$weight,$couriers)
            )
        );
    }

    public function track(Request $r) {
        $courier = (string) $r->input('courier');
        $waybill = (string) ($r->input('waybill') ?? $r->input('awb'));
        $last5   = $r->input('last_phone_number'); // optional, khusus jne

        return response()->json(
            $this->client->track($courier, $waybill, $last5)
        );
    }
}
