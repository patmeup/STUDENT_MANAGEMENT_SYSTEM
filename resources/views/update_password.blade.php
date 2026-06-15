@extends('layouts.app')

@section('content')
<main class="p-6 max-w-4xl w-full mx-auto space-y-6">

    {{-- ALERT COMPONENT FEEDBACK SYSTEM / TOPBAR NOTIFICATIONS --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 border-l-4 border-green-500 text-green-800 rounded-r-lg shadow-sm text-sm font-semibold">
            🎉 {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-r-lg shadow-sm text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>❌ {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- UNIVERSAL COMPACT FORM DESIGN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-700">🔒 Update Account Security Password</h3>
            <p class="text-xs text-gray-500">
                Logged in as: <span class="font-bold text-blue-600 font-mono">{{ $user->employee_id }}</span> 
                (<span class="capitalize text-gray-600">{{ str_replace('_', ' ', $user->role) }}</span>)
            </p>
        </div>

        <form action="{{ route('account.update_password') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            {{-- Current Password Input --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700">Current Security Password</label>
                <input type="password" name="current_password" required placeholder="I-enter ang kasalukuyang password"
                    class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white placeholder-gray-400 text-sm">
            </div>

            {{-- New Password Form Fields Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">New Secure Password</label>
                    <input type="password" name="new_password" required placeholder="Gumawa ng bagong password"
                        class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white placeholder-gray-400 text-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Dapat may minimum na 8 alphanumeric characters.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required placeholder="I-type muli ang bagong password"
                        class="w-full mt-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white placeholder-gray-400 text-sm">
                </div>
            </div>

            {{-- Action Submit Trigger Button --}}
            <div class="flex justify-end pt-2">
                <button type="submit" 
                    class="w-full md:w-auto px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg shadow-sm transition-all text-sm tracking-wide">
                    Lock & Secure Account
                </button>
            </div>
        </form>
    </div>

</main>
@endsection