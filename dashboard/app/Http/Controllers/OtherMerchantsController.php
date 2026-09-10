<?php

namespace App\Http\Controllers;

use App\Models\OtherMerchants;
use App\Service\GooglePlacesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OtherMerchantsController extends Controller
{
    private GooglePlacesService $service;

    public function __construct(GooglePlacesService $service)
    {
        $this->service = $service;
    }

    public function findPlace(Request $request): JsonResponse
    {
        $searchText = $request->get('searchText') ?? '';

        if (!$searchText) {
            return new JsonResponse(['error' => 'Bad Request'], 400);
        }

        $places = $this->service->findPlaceNew($searchText);

        // filter out any companies that exist in the db
        $company_names = DB::table('users')->join('users_roles', 'users.id', '=', 'users_roles.user_id')
        ->where('users_roles.role_id', '=', 3)->select('first_name')->distinct()->get()->toArray();

        $company_names = array_column($company_names, 'first_name');

        $places = array_filter($places, function($place) use ($company_names) {
            return !in_array($place['displayName']['text'], $company_names);
        });

        //map checkbox values onto places data
        $other_merchants = OtherMerchants::all()->keyBy('place_id')->toArray();
        $other_merchants_ids = array_column($other_merchants, 'place_id');

        foreach($places as $index => $place) {
            $place['called'] = in_array($place['id'], $other_merchants_ids) && $other_merchants[$place['id']]['called'];
            $place['onboarded'] = in_array($place['id'], $other_merchants_ids) && $other_merchants[$place['id']]['called'];

            $places[$index] = $place;
        }

        return new JsonResponse($places);
    }

    public function update(Request $request): JsonResponse
    {
        $place_id = $request->get('place_id') ?? '';
        $data = $request->get('data') ?? [];

        if (empty($place_id) || empty($data)) {
            return new JsonResponse(['error' => 'Bad Request'], 400);
        }

        $insert = ['place_id' => $place_id];
        $update = [];

        foreach($data as $key => $value) {
            $insert[$key] = $value;
            $update[$key] = $value;
        }

        $updated = OtherMerchants::upsert($insert, uniqueBy: ['place_id'], update: $update);

        return new JsonResponse(['updated' => $updated]);
    }
}


