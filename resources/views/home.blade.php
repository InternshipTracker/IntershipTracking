<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Internship Tracking System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-slate-800 font-sans" style="background-color:#f4d9c3;">
<div class="min-h-screen flex flex-col">

    {{-- HEADER --}}
    <header class="border-b border-slate-200 shadow-sm sticky top-0 z-50" style="background-color:#8c2230;">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center text-white">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Internship Tracking System</h1>
                <p class="text-xs font-medium opacity-90">
                    Sangamner College Nagarpalika Arts, D. J. Malpani Commerce & B. N. Sarda Science College
                </p>
            </div>
            
            <div class="flex items-center gap-5">
                <a href="{{ route('student.auth') }}" class="text-sm font-semibold text-white hover:text-slate-100 transition">
                    Student Login
                </a>
                <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-full hover:bg-indigo-700 shadow-md transition">
                    Register Now
                </a>
                <a href="{{ route('teacher.login') }}" class="font-semibold text-white hover:text-slate-100">
                    Teacher Login
                </a>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex items-center justify-center p-8">
        <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            
            {{-- LEFT SIDE: IMAGE SLIDER --}}
            @php
                $slides = [
                    asset('images/clg_1.jpg'),
                    asset('images/clg2.jpg'),
                    asset('images/clg3.jpg'),
                ];
            @endphp

            <div class="flex justify-center md:justify-start">
                <div id="slider" class="w-full max-w-[480px] h-[320px] relative overflow-hidden rounded-2xl">

                    @foreach($slides as $index => $slide)
                        <img 
                            src="{{ $slide }}" 
                            class="absolute inset-0 w-full h-full object-cover rounded-2xl transition-opacity duration-1000 {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}"
                            data-slide
                            loading="eager"
                            alt="College Image"
                        >
                    @endforeach

                </div>
            </div>

            {{-- RIGHT SIDE --}}
            <div class="space-y-5 text-left">
                <h2 class="text-5xl font-extrabold text-slate-900 leading-tight">
                    Welcome to <br>
                    <span class="text-indigo-600">Digital Internship Portal</span>
                </h2>
                <p class="text-lg text-slate-700 max-w-xl leading-relaxed">
                    Official college portal to track and manage students' internships seamlessly.
                </p>
            </div>

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-slate-200 py-4" style="background-color:#8c2230;">
        <div class="max-w-7xl mx-auto px-4 text-center text-white">
            <p class="text-xs">
                © {{ now()->year }} Internship Tracking System · Sangamner College Nagarpalika Arts,
                D. J. Malpani Commerce & B. N. Sarda Science College
            </p>
        </div>
    </footer>

</div>

{{-- IMPROVED SLIDER SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('[data-slide]');
    let current = 0;

    setInterval(() => {
        slides[current].classList.remove('opacity-100');
        slides[current].classList.add('opacity-0');

        current = (current + 1) % slides.length;

        slides[current].classList.remove('opacity-0');
        slides[current].classList.add('opacity-100');
    }, 4500);
});
</script>

<style>
    img {
        image-rendering: auto;
        backface-visibility: hidden;
    }
</style>

</body>
</html>
