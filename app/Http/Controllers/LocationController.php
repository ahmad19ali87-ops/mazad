<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getRegions(int $governorateId): JsonResponse
    {
        $regions = Region::where('governorate_id', $governorateId)
            ->orderBy('sort_order')
            ->select('id', 'name_ar', 'name_en')
            ->get();

        return response()->json($regions);
    }

    public function getDistricts(int $regionId): JsonResponse
    {
        $districts = District::where('region_id', $regionId)
            ->orderBy('sort_order')
            ->select('id', 'name_ar', 'name_en')
            ->get();

        return response()->json($districts);
    }
}

