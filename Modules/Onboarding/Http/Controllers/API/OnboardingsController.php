<?php

namespace Modules\Onboarding\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Modules\Onboarding\Models\Onboarding;
use Illuminate\Http\Request;
use Modules\Onboarding\Transformers\OnboardingResource;

class OnboardingsController extends Controller
{
  // api controller logic
  public function onboardingDataList(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', null);
    $page = $request->input('page', 1);

    $onboardings = Onboarding::where('status', 1)->paginate($perPage);

    $responseData = OnboardingResource::collection($onboardings);


    return ApiResponse::success($responseData, __('messages.onboardings_list'), 200);
  }
}
