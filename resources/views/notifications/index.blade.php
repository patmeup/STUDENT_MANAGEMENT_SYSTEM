@extends('layouts.app')

@section('content')
<style>
    .nt-card { 
        background: #fff; 
        border-radius: 14px; 
        box-shadow: 0 4px 20px rgba(0,0,0,.05); 
        overflow: hidden; 
        width: 95%;
        max-width: 900px; 
        margin: 40px auto; 
    }
    
    .nt-top { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 24px 30px; 
        border-bottom: 1px solid #e2e8f0; 
        flex-wrap: wrap; 
        gap: 12px; 
    }
    
    .nt-top h2 { 
        margin: 0; 
        font-size: 22px; 
        color: #0f172a; 
        font-weight: 700;
    }
    
    .nt-tabs { 
        margin-top: 12px; 
        display: flex; 
        gap: 8px; 
    }
    
    .nt-tabs a { 
        display: inline-block; 
        padding: 8px 16px; 
        border-radius: 20px; 
        font-size: 14px; 
        font-weight: 600; 
        text-decoration: none; 
        color: #475569; 
        background: #f1f5f9; 
        transition: all 0.2s ease;
    }
    
    .nt-tabs a:hover { 
        background: #e2e8f0; 
    }
    
    .nt-tabs a.active { 
        background: #0039A6; 
        color: #fff; 
        box-shadow: 0 2px 6px rgba(0, 57, 166, 0.3);
    }
    
    .nt-btn { 
        background: #0039A6; 
        color: #fff; 
        border: none; 
        padding: 10px 18px; 
        border-radius: 8px; 
        font-size: 14px; 
        font-weight: 600; 
        cursor: pointer; 
        transition: background 0.2s ease; 
    }
    
    .nt-btn:hover { 
        background: #002D80; 
    }
    
    .nt-item { 
        display: flex; 
        align-items: flex-start; 
        gap: 16px; 
        padding: 20px 30px; 
        border-bottom: 1px solid #f1f5f9; 
        transition: background 0.2s ease;
    }
    
    .nt-item:hover { 
        background: #f8fafc; 
    }
    
    .nt-item.unread { 
        background: #f0f7ff; 
    }
    
    .nt-dot { 
        width: 10px; 
        height: 10px; 
        border-radius: 50%; 
        background: #cbd5e1; 
        margin-top: 6px; 
        flex-shrink: 0; 
    }
    
    .nt-item.unread .nt-dot { 
        background: #0039A6; 
        box-shadow: 0 0 0 4px rgba(0, 57, 166, 0.15);
    }
    
    .nt-body { 
        flex: 1; 
        min-width: 0; 
    }
    
    .nt-body a { 
        text-decoration: none; 
        color: inherit; 
        display: block; 
    }
    
    .nt-title { 
        font-size: 15px; 
        font-weight: 700; 
        color: #0f172a; 
        margin-bottom: 4px;
    }
    
    .nt-msg { 
        font-size: 14px; 
        color: #475569; 
        line-height: 1.5; 
    }
    
    .nt-time { 
        font-size: 12px; 
        color: #94a3b8; 
        margin-top: 8px; 
        font-weight: 500;
    }
    
    .nt-del { 
        background: none; 
        border: none; 
        color: #94a3b8; 
        cursor: pointer; 
        font-size: 20px; 
        line-height: 1; 
        padding: 6px; 
        border-radius: 6px;
        transition: all 0.2s ease; 
    }
    
    .nt-del:hover { 
        color: #ef4444; 
        background: #fee2e2;
    }
    
    .nt-empty { 
        padding: 80px 24px; 
        text-align: center; 
        color: #64748b; 
        font-size: 15px;
        font-weight: 500;
    }
    
    .nt-pager { 
        display: flex; 
        justify-content: space-between; 
        padding: 18px 30px; 
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    
    .nt-pager a { 
        color: #0039A6; 
        font-weight: 700; 
        font-size: 14px; 
        text-decoration: none; 
        transition: color 0.2s ease;
    }
    
    .nt-pager a:hover { 
        color: #002D80; 
    }
</style>

<div class="nt-card">
    <div class="nt-top">
        <div>
            <h2>Notifications</h2>
            <div class="nt-tabs">
                <a href="{{ route('notifications.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}">All</a>
                
                @if($unreadCount > 0)
                    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'active' : '' }}">Unread ({{ $unreadCount }})</a>
                @else
                    <a href="javascript:void(0)" class="{{ $filter === 'unread' ? 'active' : '' }}" style="opacity: 0.5; cursor: not-allowed;">Unread (0)</a>
                @endif
            </div>
        </div>
        
        <form method="POST" action="{{ route('notifications.read_all') }}">
            @csrf
            @if($unreadCount > 0)
                <button type="submit" class="nt-btn">Mark all as read</button>
            @else
                <button type="button" class="nt-btn" style="background: #cbd5e1; cursor: not-allowed; color: #64748b;" disabled>Mark all as read</button>
            @endif
        </form>
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
        <div class="nt-empty">
            {{ $filter === 'unread' ? 'No unread notifications.' : 'You have no notifications yet.' }}
        </div>
    @endforelse

    @if($notifications->previousPageUrl() || $notifications->nextPageUrl())
        <div class="nt-pager">
            <span>@if($notifications->previousPageUrl())<a href="{{ $notifications->previousPageUrl() }}">&larr; Newer</a>@endif</span>
            <span>@if($notifications->nextPageUrl())<a href="{{ $notifications->nextPageUrl() }}">Older &rarr;</a>@endif</span>
        </div>
    @endif
</div>
@endsection