<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Maestro Technologies</title>
    @vite(['resources/css/app.css'])
</head>
<body class="m-0 min-h-screen bg-[#0c1016] antialiased text-slate-900">
    <div
        class="relative isolate overflow-hidden px-4 sm:px-6"
        style="height:100vh;width:100vw;display:flex;align-items:center;justify-content:center;"
    >
        <div class="pointer-events-none absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-900/30 via-transparent to-transparent"></div>
        <div class="relative z-10 mx-auto w-full max-w-md pointer-events-auto">
            <div class="mb-6 text-center text-white">
                <img
                    src="/images/logo-maestrotech.svg"
                    alt="Maestro Technologies"
                    class="mx-auto h-12 w-auto"
                    width="160"
                    height="56"
                >
                <h1 class="mt-6 text-2xl font-semibold tracking-tight">Maestro Technologies</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-400">
                    HR Portal — manage employees, attendance, leave, and payroll from one secure dashboard.
                </p>
            </div>

            <form
                method="post"
                action="{{ route('login') }}"
                class="w-full max-w-md space-y-6 rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-900/5"
            >
                @csrf
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Sign in</h2>
                    <p class="mt-1 text-sm text-slate-600">Enter your work email and password.</p>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm transition focus:border-emerald-500/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm transition focus:border-emerald-500/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/30">
                        Remember me
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-emerald-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-500"
                >
                    Log in
                </button>
            </form>
        </div>
    </div>
</body>
</html>
