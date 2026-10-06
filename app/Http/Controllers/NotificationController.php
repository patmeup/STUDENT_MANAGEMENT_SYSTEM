<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /** [recipient_type, recipient_id] for whoever is logged in (session-based auth). */
    private function me(): array
    {
        if (session('user_role') === 'student') {
            return ['student', (string) session('student_id')];
        }
        return ['employee', (string) session('user_id')];
    }

    private function mine()
    {
        [$type, $id] = $this->me();
        return DB::table('notifications')->where('recipient_type', $type)->where('recipient_id', $id);
    }

    /** JSON polled by the bell every ~30s. */
    public function feed()
    {
        $items = $this->mine()->orderByDesc('id')->limit(10)->get()->map(function ($n) {
            return [
                'id'      => $n->id,
                'type'    => $n->type,
                'title'   => $n->title,
                'message' => $n->message,
                'read'    => $n->read_at !== null,
                'time'    => Carbon::parse($n->created_at)->diffForHumans(),
                'open'    => route('notifications.open', $n->id, false),
            ];
        });

        return response()->json([
            'unread' => $this->mine()->whereNull('read_at')->count(),
            'items'  => $items,
        ]);
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $q = $this->mine()->orderByDesc('id');
        if ($filter === 'unread') {
            $q->whereNull('read_at');
        }

        $notifications = $q->simplePaginate(15)->withQueryString();
        $unreadCount   = $this->mine()->whereNull('read_at')->count();

        return view('notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    /** Mark one as read, then go to its target page. */
    public function open($id)
    {
        $n = $this->mine()->where('id', $id)->first();
        if (!$n) {
            return redirect()->route('notifications.index');
        }

        DB::table('notifications')->where('id', $id)->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return redirect($n->url ?: route('notifications.index', [], false));
    }

    public function readAll(Request $request)
    {
        $this->mine()->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->mine()->where('id', $id)->delete();
        return redirect()->back();
    }
}
