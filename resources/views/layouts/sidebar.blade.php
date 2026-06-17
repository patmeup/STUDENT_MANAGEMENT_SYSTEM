<nav class="sidebar">
    <div class="sidebar-content">
        <ul class="sidebar-menu">

            @if(session('user_role') === 'super_admin')
                @php
                    $currentUser = \App\Models\User::where('employee_id', session('employee_id'))->first();
                    $isAccountLocked = ($currentUser && !\Illuminate\Support\Str::startsWith($currentUser->password, '$2y$'));
                @endphp

                <li>
                    <a href="{{ route('super_admin.dashboard') }}" class="{{ Request::is('super_admin/dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    @if($isAccountLocked)
                        <a href="javascript:void(0);" 
                        style="opacity: 0.4; cursor: not-allowed !important; user-select: none; text-decoration: none !important;">
                            <i data-lucide="lock" style="color: #ef4444;"></i> 
                            <span>Manage Employee</span>
                        </a>
                    @else
                        <a href="{{ route('super_admin.manage-employee') }}" class="{{ Request::is('super_admin/manage-employee') ? 'active' : '' }}">
                            <i data-lucide="users"></i> <span>Manage Employee</span>
                        </a>
                    @endif
                </li>

                <li>
                    @if($isAccountLocked)
                        <a href="javascript:void(0);" 
                        style="opacity: 0.4; cursor: not-allowed !important; user-select: none; text-decoration: none !important;">
                            <i data-lucide="lock" style="color: #ef4444;"></i> 
                            <span>Manage Courses</span>
                        </a>
                    @else
                    <a href="{{ route('super_admin.manage-courses') }}" class="{{ Request::is('super_admin/manage-courses') ? 'active' : '' }}">
                        <i data-lucide="book-open"></i> <span>Manage Courses</span>
                    </a>
                    @endif
                </li>

                <li>
                    <a href="{{ route('super_admin.user_details') }}" class="{{ Request::is('super_admin/user_details') ? 'active' : '' }}">
                        <i data-lucide="user"></i> <span>Profile</span>
                    </a>
                </li>
            @endif
        
            @if(session('user_role') === 'system_admin')
                <li>
                    <a href="{{ route('system_admin.dashboard') }}" class="{{ Request::is('system_admin/dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('system_admin.manage-subjects') }}" class="{{ Request::is('system_admin/manage-subjects') ? 'active' : '' }}">
                        <i data-lucide="book-open"></i> <span>Manage Subjects</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('system_admin.manage-curriculum') }}" class="{{ Request::is('system_admin/manage-curriculum') ? 'active' : '' }}">
                        <i data-lucide="notebook-text"></i> <span>Manage Curriculum</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('system_admin.user_details') }}" class="{{ Request::is('system_admin/user_details') ? 'active' : '' }}">
                        <i data-lucide="user"></i> <span>Profile</span>
                    </a>
                </li>
            @endif

            @if(session('user_role') === 'registrar')
                <li>
                    <a href="{{ route('registrar.dashboard') }}" class="{{ Request::is('registrar/dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('registrar.masterlist') }}" class="{{ Request::is('registrar/masterlist') ? 'active' : '' }}">
                        <i data-lucide="users"></i> <span>Student Masterlist</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('registrar.manage-enrollment') }}" class="{{ Request::is('registrar/manage-enrollment') ? 'active' : '' }}">
                        <i data-lucide="user-check"></i> <span>Manage Enrollment</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('registrar.user_details') }}" class="{{ Request::is('registrar/user_details') ? 'active' : '' }}">
                        <i data-lucide="user"></i> <span>Profile</span>
                    </a>
                </li>
            @endif

            @if(session('user_role') === 'dean')
                <li>
                    <a href="{{ route('dean.dashboard') }}" class="{{ Request::is('dean/dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('dean.curriculum.index') }}" class="{{ Request::is('dean/encoding') ? 'active' : '' }}">
                        <i data-lucide="map"></i> <span>Curriculum Encoding</span>
                    </a>                    
                </li>
            @endif
        </ul>
    </div>

    <ul class="sidebar-menu logout-section">
        <li>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="logout-link">
                <i data-lucide="log-out"></i> <span>Logout Portal</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

<style>
    .sidebar {
        width: 280px;
        height: 100vh;
        background-color: #002D80; 
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        padding: 20px 15px; 
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    .sidebar-content {
        margin-top: 80px; 
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li { margin-bottom: 8px; }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar-menu a:hover {
        color: white;
        background: rgba(255, 255, 255, 0.08);
        padding-left: 28px;
    }

    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.15);
        color: #facc15;
        font-weight: 700;
        padding-left: 25px;
    }

    .logout-section {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logout-link:hover {
        color: #ff4d4d !important;
        background: rgba(255, 77, 77, 0.1) !important;
    }
</style>