<div class="topbar">
    <div class="topbar-left">
        <img src="{{ asset('images/au_logo.png') }}" class="logo-img">
        <strong>Arellano University - College</strong>
    </div>

    <div class="topbar-right">
        @if(session()->has('employee_id') || Auth::check())
            @php
                $user = Auth::user() ?? \App\Models\User::where('employee_id', session('employee_id'))->first();
                $role = session('user_role') ?? ($user->role ?? 'user');
            @endphp

            @if($user)
                <div class="user-profile-wrapper">
                    
                    {{-- Gagawa tayo ng column container para sa Pangalan at Badge para magka-patong sila --}}
                    <div class="user-details-column">
                        {{-- 1. Pangalan sa Itaas (Surname First) --}}
                        <div class="name">
                            {{ strtoupper($user->last_name) }}, {{ strtoupper($user->first_name) }}
                            @if(!empty($user->middle_name))
                                {{ strtoupper(substr($user->middle_name, 0, 1)) }}.
                            @endif
                        </div>

                        {{-- 2. Role Badge sa Ibaba pero Naka-center sa Pangalan --}}
                        <div class="role-badge badge-{{ $role }}">
                            @if($role == 'super_admin') SUPER ADMIN
                            @elseif($role == 'system_admin') SYSTEM ADMIN
                            @elseif($role == 'registrar') REGISTRAR
                            @elseif($role == 'dept_sec') DEPARTMENT SECRETARY
                            @elseif($role == 'professor') PROFESSOR
                            @elseif($role == 'dean') DEAN
                            @else {{ strtoupper($role) }}
                            @endif
                        </div>
                    </div>

                    {{-- 3. Avatar Icon sa dulong kanan --}}
                    <div class="avatar">
                        <img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $user->first_name }}+{{ $user->last_name }}&backgroundType=solid&backgroundColor=cbd5e1" alt="Profile">
                    </div>

                </div>
            @endif
        @endif
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
    }

    /* Main Container para sa buong profile area */
    .user-profile-wrapper {
        display: flex;
        align-items: center;
        gap: 15px; /* Distansya mula sa dulo ng pangalan/badge patungo sa avatar */
    }

    /* Ito ang sikreto: Ginawa nating column at ini-center ang mga items */
    .user-details-column {
        display: flex;
        flex-direction: column;
        align-items: center; /* Hinihila nito ang badge sa eksaktong gitna sa ilalim ng pangalan */
        justify-content: center;
        gap: 4px; /* Maliit na patlang sa pagitan ng pangalan at badge */
    }

    .user-details-column .name {
        font-weight: 700;
        font-size: 14px;
        color: white;
        letter-spacing: 0.3px;
        text-align: center; /* Sinisigurong naka-center ang text distribution */
    }

    /* Role Badge Design Settings */
    .role-badge {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 3px 14px; 
        border-radius: 50px; 
        color: white;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        height: 19px; 
        white-space: nowrap;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-sizing: border-box;
    }

    .role-badge.badge-super_admin { background: #1e293b; border-color: #334155; }
    .role-badge.badge-system_admin { background: #000000; border-color: #1e293b; } 
    .role-badge.badge-dept_sec { background: #ec4899; border-color: #f472b6; }    
    .role-badge.badge-professor { background: #64748b; border-color: #475569; }   
    .role-badge.badge-registrar { background: #0284c7; border-color: #0ea5e9; }   

    .avatar img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255,255,255,0.4);
        background: #eee;
        display: block;
    }
</style>