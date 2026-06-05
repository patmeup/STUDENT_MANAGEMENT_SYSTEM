<div class="topbar">
    <div class="topbar-left">
        <img src="{{ asset('images/au_logo.png') }}" class="logo-img">
        <strong>Arellano University - College</strong>
    </div>

    <div class="topbar-right">
        @auth
            <div class="user-info">
                <div class="name">
                    {{ Auth::user()->first_name ?? Auth::user()->name }}
                    @if(!empty(Auth::user()->middle_name))
                        {{ Auth::user()->middle_name }}
                    @endif
                    {{ Auth::user()->last_name ?? '' }}
                </div>

                <div class="role-badge">
                    @if(isset(Auth::user()->student_id))
                        STUDENT
                    @else
                        @php $role = Auth::user()->role; @endphp
                        @if($role == 'super_admin') ADMIN
                        @elseif($role == 'registrar') REGISTRAR
                        @elseif($role == 'dept_head') DEPARTMENT HEAD
                        @elseif($role == 'faculty') PROFESSOR
                        @else {{ strtoupper($role ?? 'USER') }}
                        @endif
                    @endif
                </div>
            </div>

            <div class="avatar">
                <img src="https://i.pravatar.cc/40?u={{ Auth::user()->admin_id ?? Auth::user()->student_id }}" alt="Profile">
            </div>
        @endauth
    </div>
</div>

<style>

    .topbar {
        position: fixed;
        top: 0;
        left: 0; 
        width: 100%; 
        height: 70px;
        background: #0039A6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
        z-index: 1000;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .topbar-left strong {
        color: white;
        font-size: 20px;
        letter-spacing: -0.5px;
    }

    .logo-img {
        height: 48px;
        width: auto;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: center; 
        line-height: 1.2;  
        justify-content: center;
    }

    .user-info .name {
        font-weight: 600;
        font-size: 14px;
        color: white;
        text-align: center;
    }

    .role-badge {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 3px 12px; 
        border-radius: 50px; 
        margin-top: 4px;
        background: rgba(255, 255, 255, 0.2); 
        color: white;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        height: 18px; 
        white-space: nowrap;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .avatar img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255,255,255,0.3);
        background: #eee;
    }
</style>