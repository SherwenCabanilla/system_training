<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold text-teal-700">Welcome back</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Sign in to your portal</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Access your profile, appointments, and medical information in one secure place.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" class="mt-1 block w-full border-slate-300 focus:border-teal-600 focus:ring-teal-600" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full border-slate-300 focus:border-teal-600 focus:ring-teal-600" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="mt-4 inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-600" name="remember">
            <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
        </label>

        <div class="mt-6 flex items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
            <x-primary-button class="bg-teal-700 px-5 py-2.5 hover:bg-teal-800 focus:bg-teal-800 active:bg-teal-900">{{ __('Log in') }}</x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="mt-7 text-center text-sm text-slate-600">Need a portal account? <a class="font-semibold text-teal-700 hover:text-teal-800" href="{{ route('register') }}">Register here</a>.</p>
        @endif
    </form>
</x-guest-layout>
