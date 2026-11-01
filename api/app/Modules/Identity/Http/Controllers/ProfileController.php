<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController
{
    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'headline' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'portfolio_url' => ['nullable', 'url:https,http', 'max:255'],
            'linkedin_url' => ['nullable', 'url:https,http', 'max:255'],
        ]);

        $request->user()->update($data);

        return new UserResource($request->user());
    }

    /**
     * AUTH-03: CV PDF maks. 5 MB, disimpan privat dengan nama acak.
     */
    public function uploadCv(Request $request): UserResource
    {
        $request->validate(['cv' => ['required', 'file', 'mimes:pdf', 'max:5120']]);

        $user = $request->user();

        if ($user->cv_path) {
            Storage::disk('local')->delete($user->cv_path);
        }

        $user->update(['cv_path' => $request->file('cv')->store('cv', 'local')]);

        return new UserResource($user);
    }
}
