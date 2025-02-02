<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function store(Request $request)
    {
        Log::channel('pwa')->info('PWA Debug Log', [
            'user_id' => auth()->id(),
            'role' => auth()->user()->role ?? 'none',
            'path' => $request->path,
            'is_pwa' => $request->is_pwa,
            'timestamp' => now(),
            'debug_info' => $request->debug_info
        ]);

        return response()->json(['status' => 'logged']);
    }
} 