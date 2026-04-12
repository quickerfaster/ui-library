<?php

namespace QuickerFaster\UILibrary\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;




class TempImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        $file = $request->file('image');
        $tempId = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('temp/crops', $tempId, 'public');

        return response()->json([
            'success' => true,
            'temp_id' => $tempId,
        ]);
    }
}