<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

    <div class="min-h-screen bg-gradient-to-b from-emerald-50 via-white to-slate-100 flex items-center justify-center px-4 py-10">
        <div class="relative max-w-xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-500 via-green-500 to-emerald-600 text-white px-6 py-4 flex items-center gap-3 shadow-lg">
                <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4m-7 6a9 9 0 1 1 18 0a9 9 0 0 1-18 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold opacity-90">Registration Successful</p>
                    <p class="text-base font-bold">Awaiting Department Teacher Approval</p>
                </div>
            </div>

            <div class="p-8 space-y-5 text-center">
                <h1 class="text-3xl font-extrabold text-emerald-700">Thank you for registering!</h1>
                <p class="text-lg text-slate-700 leading-relaxed">
                    Your approval request has been sent to your department teacher.<br>
                    You’ll be notified as soon as they approve it.
                </p>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold border border-emerald-200">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    Approval in progress
                </div>
            </div>

            <div class="bg-slate-50 px-8 py-6 flex justify-center">
                <a href="{{ route('login') }}" class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold shadow-lg hover:bg-emerald-700 transition">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
