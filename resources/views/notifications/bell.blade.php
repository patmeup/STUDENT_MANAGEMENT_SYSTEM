{{-- Notification bell: included from layouts/topbar.blade.php --}}
<div class="notif-wrap" id="notifWrap">
    <button type="button" class="notif-btn" id="notifBtn" aria-label="Notifications" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <span class="notif-badge" id="notifBadge" hidden>0</span>
    </button>

    <div class="notif-panel" id="notifPanel" hidden>
        <div class="notif-head">
            <strong>Notifications</strong>
            <button type="button" id="notifReadAll" class="notif-link-btn">Mark all as read</button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty">Loading...</div>
        </div>
        <a href="{{ route('notifications.index') }}" class="notif-foot">View all notifications</a>
    </div>
</div>

<style>
    .notif-wrap { position: relative; margin-right: 22px; }
    .notif-btn { position: relative; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);
        color: #fff; width: 42px; height: 42px; border-radius: 50%; cursor: pointer; display: flex;
        align-items: center; justify-content: center; transition: background .15s; }
    .notif-btn:hover { background: rgba(255,255,255,0.22); }
    .notif-badge { position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 5px;
        background: #ef4444; color: #fff; border-radius: 9px; font-size: 10px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; border: 2px solid #0039A6; box-sizing: border-box; }
    .notif-badge[hidden], .notif-panel[hidden] { display: none; }
    .notif-panel { position: absolute; right: 0; top: 54px; width: 370px; max-width: 92vw; background: #fff; color: #1e293b;
        border-radius: 12px; box-shadow: 0 12px 32px rgba(0,0,0,.22); overflow: hidden; z-index: 1100; }
    .notif-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    .notif-link-btn { background: none; border: none; color: #0039A6; font-size: 12px; font-weight: 700; cursor: pointer; }
    .notif-link-btn:hover { text-decoration: underline; }
    .notif-list { max-height: 400px; overflow-y: auto; }
    .notif-item { display: block; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; position: relative; }
    .notif-item:hover { background: #f8fafc; }
    .notif-item.unread { background: #eff6ff; }
    .notif-item.unread::before { content: ''; position: absolute; left: 6px; top: 20px; width: 6px; height: 6px; border-radius: 50%; background: #0039A6; }
    .notif-title { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
    .notif-msg { font-size: 12px; color: #475569; line-height: 1.4; }
    .notif-time { font-size: 11px; color: #94a3b8; margin-top: 4px; }
    .notif-empty { padding: 28px 16px; text-align: center; color: #94a3b8; font-size: 13px; }
    .notif-foot { display: block; text-align: center; padding: 11px; font-size: 12px; font-weight: 700; color: #0039A6;
        text-decoration: none; background: #f8fafc; border-top: 1px solid #e2e8f0; }
    .notif-foot:hover { background: #eef2f7; }
</style>

<script>
(function () {
    const FEED_URL = @json(route('notifications.feed'));
    const READ_ALL_URL = @json(route('notifications.read_all'));
    const CSRF = @json(csrf_token());
    const POLL_MS = 30000;

    const btn = document.getElementById('notifBtn');
    const panel = document.getElementById('notifPanel');
    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const wrap = document.getElementById('notifWrap');

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function render(data) {
        const n = data.unread || 0;
        badge.hidden = n === 0;
        badge.textContent = n > 99 ? '99+' : n;

        if (!data.items.length) {
            list.innerHTML = '<div class="notif-empty">You have no notifications yet.</div>';
            return;
        }
        list.innerHTML = data.items.map(i => `
            <a href="${esc(i.open)}" class="notif-item ${i.read ? '' : 'unread'}">
                <div class="notif-title">${esc(i.title)}</div>
                <div class="notif-msg">${esc(i.message)}</div>
                <div class="notif-time">${esc(i.time)}</div>
            </a>`).join('');
    }

    async function load() {
        try {
            const res = await fetch(FEED_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) return;
            render(await res.json());
        } catch (e) { /* offline / session expired: ignore */ }
    }

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.hidden = !panel.hidden;
        btn.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) load();
    });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) panel.hidden = true; });

    document.getElementById('notifReadAll').addEventListener('click', async () => {
        await fetch(READ_ALL_URL, { method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        load();
    });

    load();
    setInterval(() => { if (!document.hidden) load(); }, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
})();
</script>
