<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Actions\JoinLiveSession;
use App\Modules\Learning\Actions\SubmitAssignment;
use App\Modules\Learning\Actions\UpdateLessonProgress;
use App\Modules\Learning\Http\Resources\LiveSessionResource;
use App\Modules\Learning\Http\Resources\SubmissionResource;
use App\Modules\Learning\Models\Discussion;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;
use App\Modules\Learning\Video\BunnyStream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Endpoint Ruang Kelas, selalu di-scope ke enrollment supaya akses & cohort jelas.
 */
class LessonController
{
    public function show(Request $request, Enrollment $enrollment, Lesson $lesson, BunnyStream $bunny): JsonResponse
    {
        $this->authorizeLesson($enrollment, $lesson);
        $lesson->load('section');

        $ordered = Lesson::query()
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->where('sections.program_id', $enrollment->program_id)
            ->orderBy('sections.position')->orderBy('lessons.position')
            ->pluck('lessons.id')
            ->values();
        $index = $ordered->search($lesson->id);

        $progress = $enrollment->lessonProgress()->where('lesson_id', $lesson->id)->first();
        $submission = $enrollment->submissions()->where('lesson_id', $lesson->id)->first();
        $liveSession = $enrollment->cohort_id
            ? LiveSession::with('mentor')->where('cohort_id', $enrollment->cohort_id)->where('lesson_id', $lesson->id)->first()
            : null;

        $dueAt = $lesson->assignmentDueAt($enrollment->cohort);

        return response()->json(['data' => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'section_title' => $lesson->section->title,
            'type' => $lesson->type->value,
            'kind_label' => $lesson->kindLabel(),
            'duration_minutes' => $lesson->duration_minutes,
            'summary' => $lesson->summary,
            'resource_url' => $lesson->resource_url,
            'playback' => $bunny->playback($lesson->video_id) + ['watermark' => $request->user()->email],
            'progress' => [
                'completed' => $progress?->completed_at !== null,
                'last_position_sec' => $progress?->last_position_sec ?? 0,
            ],
            'assignment' => $lesson->hasAssignment() ? [
                'title' => $lesson->assignment_title,
                'instructions' => $lesson->assignment_instructions,
                'due_at' => $dueAt?->toIso8601String(),
                'is_final_project' => $lesson->is_final_project,
            ] : null,
            'submission' => $submission ? new SubmissionResource($submission) : null,
            'live_session' => $liveSession ? new LiveSessionResource($liveSession) : null,
            'prev_lesson_id' => $index > 0 ? $ordered[$index - 1] : null,
            'next_lesson_id' => $ordered[$index + 1] ?? null,
        ]]);
    }

    public function progress(Request $request, Enrollment $enrollment, Lesson $lesson, UpdateLessonProgress $update): JsonResponse
    {
        $this->authorizeLesson($enrollment, $lesson, 'learn');
        $data = $request->validate([
            'position_sec' => ['nullable', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $update->handle($enrollment, $lesson, $data['position_sec'] ?? null, isset($data['completed']) ? (bool) $data['completed'] : null);

        $enrollment->refresh();

        return response()->json(['data' => [
            'progress_pct' => $enrollment->progress_pct,
            'status' => $enrollment->status->value,
        ]]);
    }

    public function submit(Request $request, Enrollment $enrollment, Lesson $lesson, SubmitAssignment $submit): SubmissionResource
    {
        $this->authorizeLesson($enrollment, $lesson, 'learn');
        $data = $request->validate([
            'link_url' => ['nullable', 'required_without:file', 'url:https,http', 'max:255'],
            'file' => ['nullable', 'required_without:link_url', 'file', 'max:20480', 'mimes:pdf,zip,doc,docx,ppt,pptx,png,jpg,jpeg,fig'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return new SubmissionResource(
            $submit->handle($enrollment, $lesson, $data['link_url'] ?? null, $request->file('file'), $data['note'] ?? null)
        );
    }

    public function discussions(Enrollment $enrollment, Lesson $lesson): JsonResponse
    {
        $this->authorizeLesson($enrollment, $lesson);

        $threads = Discussion::with(['user', 'replies.user'])
            ->where('lesson_id', $lesson->id)
            ->where('cohort_id', $enrollment->cohort_id)
            ->whereNull('parent_id')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['data' => $threads->map(fn (Discussion $d) => $this->presentDiscussion($d))]);
    }

    public function postDiscussion(Request $request, Enrollment $enrollment, Lesson $lesson): JsonResponse
    {
        $this->authorizeLesson($enrollment, $lesson, 'learn');
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        if (isset($data['parent_id'])) {
            $parentExists = Discussion::whereKey($data['parent_id'])
                ->where('lesson_id', $lesson->id)
                ->where('cohort_id', $enrollment->cohort_id)
                ->whereNull('parent_id')
                ->exists();
            abort_unless($parentExists, 422, 'Thread tidak ditemukan.');
        }

        $discussion = Discussion::create([
            'lesson_id' => $lesson->id,
            'cohort_id' => $enrollment->cohort_id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ])->load('user', 'replies');

        return response()->json(['data' => $this->presentDiscussion($discussion)], 201);
    }

    public function joinLive(Enrollment $enrollment, LiveSession $liveSession, JoinLiveSession $join): JsonResponse
    {
        Gate::authorize('learn', $enrollment);
        abort_unless($liveSession->cohort_id === $enrollment->cohort_id, 404);

        return response()->json(['data' => ['join_url' => $join->handle($enrollment, $liveSession)]]);
    }

    private function authorizeLesson(Enrollment $enrollment, Lesson $lesson, string $ability = 'view'): void
    {
        Gate::authorize($ability, $enrollment);

        abort_unless($lesson->section()->where('program_id', $enrollment->program_id)->exists(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDiscussion(Discussion $discussion): array
    {
        return [
            'id' => $discussion->id,
            'body' => $discussion->body,
            'author' => $discussion->user->name,
            'is_mentor' => $discussion->user->hasRole('mentor'),
            'created_at' => $discussion->created_at->toIso8601String(),
            'replies' => $discussion->replies->map(fn (Discussion $reply) => [
                'id' => $reply->id,
                'body' => $reply->body,
                'author' => $reply->user->name,
                'is_mentor' => $reply->user->hasRole('mentor'),
                'created_at' => $reply->created_at->toIso8601String(),
            ]),
        ];
    }
}
