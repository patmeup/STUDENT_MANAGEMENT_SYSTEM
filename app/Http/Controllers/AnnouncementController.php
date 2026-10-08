<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:100',
            'announcement_image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($request->hasFile('announcement_image')) {
            $file = $request->file('announcement_image');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('announcements', $fileName, 'public');
            DB::table('announcements')->insert([
                'title' => $request->title,
                'image_path' => 'announcements/' . $fileName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return redirect()->back()->with('success', 'Announcement banner uploaded successfully!');
        }
        return redirect()->back()->with('error', 'Failed to upload image.');
    }

    public function destroy($id)
    {
        $announcement = DB::table('announcements')->where('id', $id)->first();

        if ($announcement) {
            if (Storage::disk('public')->exists($announcement->image_path)) {
                Storage::disk('public')->delete($announcement->image_path);
            }

            DB::table('announcements')->where('id', $id)->delete();
            return redirect()->back()->with('success', 'Announcement removed successfully.');
        }

        return redirect()->back()->with('error', 'Announcement record not found.');
    }

    public function reorder(Request $request)
    {
        $order = $request->input('order');

        if (is_array($order)) {
            foreach ($order as $index => $id) {
                DB::table('announcements')->where('id', $id)->update([
                    'display_order' => $index + 1
                ]);
            }
            return response()->json(['status' => 'success', 'message' => 'Order updated!']);
        }

        return response()->json(['status' => 'error'], 400);
    }
}
