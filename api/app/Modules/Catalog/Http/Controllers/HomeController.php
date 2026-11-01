<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\TestimonialResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Program;
use App\Modules\Catalog\Models\Testimonial;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::orderBy('position')->get(['name', 'slug']),
        ]);
    }

    public function testimonials(): JsonResponse
    {
        return response()->json([
            'data' => TestimonialResource::collection(Testimonial::where('is_featured', true)->latest()->limit(6)->get()),
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = Cache::remember('home:stats', 600, fn () => [
            'alumni' => Enrollment::where('status', EnrollmentStatus::Completed)->count(),
            'learners' => Enrollment::distinct('user_id')->count('user_id'),
            'programs' => Program::published()->count(),
            'rating' => round((float) Program::published()->whereNotNull('rating')->avg('rating'), 1),
        ]);

        return response()->json(['data' => $stats]);
    }
}
