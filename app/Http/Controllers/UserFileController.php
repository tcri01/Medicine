<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserFileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $files = $user->files ?? collect();

        return view('profile.files', compact('files'));
    }

    public function upload(Request $request, FileService $FileService)
    {
        $request->validate([
            'file' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        $file = $request->file;
        Log::debug('upload', ['user' => $user, 'file' => $file]);

        $FileService->putFiles($user, $file);

        // $path = $request->file('file')->store('user_files/' . $user->id, 'public');

        // // 假設 HasFiles trait 已處理關聯
        // $user->files()->create([
        //     'path' => $path,
        //     'disk' => 'public',
        // ]);

        return redirect()->route('dashboard')->with('success', '檔案已上傳');
    }
}
