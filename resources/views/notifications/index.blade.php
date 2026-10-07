@extends('layouts.app')

@section('content')
<style>
    .nt-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden; max-width:900px; }
    .nt-top { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid #e2e8f0; flex-wrap:wrap; gap:12px; }
    .nt-top h2 { margin:0; font-size:20px; color:#0f172a; }
    .nt-tabs a { display:inline-block; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; color:#475569; margin-right:6px; background:#f1f5f9; }
    .nt-tabs a.active { background:#0039A6; color:#fff; }
    .nt-btn { background:#0039A6; color:#fff; border:none; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }
    .nt-item { display:flex; align-items:flex-start; gap:14px; padding:16px 24px; border-bottom:1px solid #f1f5f9; }
    .nt-item.unread { background:#eff6ff; }
    .nt-dot { width:9px; height:9px; border-radius:50%; background:#cbd5e1; margin-top:6px; flex-shrink:0; }
    .nt-item.unread .nt-dot { background:#0039A6; }
    .nt-body { flex:1; min-width:0; }
    .nt-body a { text-decoration:none; color:inherit; display:block; }
    .nt-title { font-size:14px; font-weight:700; color:#0f172a; }
    .nt-msg { font-size:13px; color:#475569; margin-top:2px; line-height:1.45; }
    .nt-time { font-size:12px; color:#94a3b8; margin-top:5px; }
    .nt-del { background:none; border:none; color:#94a3b8; cursor:pointer; font-size:18px; line-height:1; padding:2px 6px; }
    .nt-del:hover { color:#ef4444; }
    .nt-empty { padding:50px 24px; text-align:center; color:#94a3b8; }
    .nt-pager { display:flex; justify-content:space-between; padding:14px 24px; }
    .nt-pager a { color:#0039A6; font-weight:700; font-size:13px; text-decoration:none; }
</style>

<div class="nt-card">
    <div class="nt-top">
        <div>
            <h2>Notifications</h2>
            <div class="nt-tabs" style="margin-top:10px;">
                <a href="{{ route('notifications.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}">All</a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'active' : '' }}">Unread ({{ $unreadCount }})</a>
            </div>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                <button type="submit" class="nt-btn">Mark all as read</button>
            </form>
        @endif
    </div>

    @forelse($notifications as $n)
        <div class="nt-item {{ $n->read_at ? '' : 'unread' }}">
            <span class="nt-dot"></span>
            <div class="nt-body">
                <a href="{{ route('notifications.open', $n->id) }}">
                    <div class="nt-title">{{ $n->title }}</div>
                    <div class="nt-msg">{{ $n->message }}</div>
                    <div class="nt-time">{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</div>
                </a>
            </div>
            <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">
                @csrf @method('DELETE')
                <button type="submit" class="nt-del" title="Delete">&times;</button>
            </form>
        </div>
    @empty
        <div class="nt-empty">{{ $filter === 'unread' ? 'No unread notifications.' : 'You have no notifications yet.' }}</div>
    @endforelse

    @if($notifications->previousPageUrl() || $notifications->nextPageUrl())
        <div class="nt-pager">
            <span>@if($notifications->previousPageUrl())<a href="{{ $notifications->previousPageUrl() }}">&larr; Newer</a>@endif</span>
            <span>@if($notifications->nextPageUrl())<a href="{{ $notifications->nextPageUrl() }}">Older &rarr;</a>@endif</span>
        </div>
    @endif
</div>
@endsection
