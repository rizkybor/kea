<?php

namespace App\Modules\Career\Http\Controllers;

use App\Modules\Career\Models\CareerAssessment;
use App\Modules\Career\Recommendation\CareerPathRecommender;
use App\Modules\Career\Recommendation\Questionnaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mission Based Learning (LMS-08). Kuesioner bisa diisi tanpa login; hasil disimpan bila user login.
 */
class CareerAssessmentController
{
    public function questions(): JsonResponse
    {
        return response()->json(['data' => Questionnaire::questions()]);
    }

    public function store(Request $request, CareerPathRecommender $recommender): JsonResponse
    {
        $answers = $request->validate(Questionnaire::rules())['answers'];
        $result = $recommender->recommend($answers);

        $user = $request->user('sanctum');
        if ($user) {
            CareerAssessment::create([
                'user_id' => $user->id,
                'target_track' => $result['tracks'][0] ?? null,
                'answers' => $answers,
                'recommendations' => $result,
            ]);
        }

        return response()->json(['data' => ['answers' => $answers, ...$result, 'saved' => $user !== null]]);
    }

    public function latest(Request $request): JsonResponse
    {
        $assessment = CareerAssessment::where('user_id', $request->user()->id)->latest()->first();

        return response()->json(['data' => $assessment ? [
            'answers' => $assessment->answers,
            ...$assessment->recommendations,
            'created_at' => $assessment->created_at->toIso8601String(),
        ] : null]);
    }
}
