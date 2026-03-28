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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex flex-col gap-3 md:flex-row md:justify-between md:items-center text-white">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Internship Tracking System</h1>
                <p class="text-[11px] sm:text-xs font-medium opacity-90">
                    Sangamner College Nagarpalika Arts, D. J. Malpani Commerce & B. N. Sarda Science College
                </p>
            </div>
            
            <div class="flex w-full flex-wrap items-center gap-2 sm:gap-3 md:w-auto md:justify-end">
                <a href="{{ route('student.auth') }}" class="inline-flex items-center rounded-full border border-white/30 px-3 py-1.5 text-xs sm:text-sm font-semibold text-white hover:text-slate-100 hover:bg-white/10 transition">
                    Student Login
                </a>
                <a href="{{ route('register') }}" class="px-4 sm:px-5 py-2 text-xs sm:text-sm font-semibold text-white bg-indigo-600 rounded-full hover:bg-indigo-700 shadow-md transition">
                    Register Now
                </a>
                <a href="{{ route('teacher.login') }}" class="inline-flex items-center rounded-full border border-white/30 px-3 py-1.5 text-xs sm:text-sm font-semibold text-white hover:text-slate-100 hover:bg-white/10 transition">
                    Teacher Login
                </a>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
        <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-2 gap-7 sm:gap-10 md:gap-12 items-center">
            
            {{-- LEFT SIDE: IMAGE SLIDER --}}
            @php
                $slides = [
                    asset('images/clg_1.jpg'),
                    asset('images/clg2.jpg'),
                    asset('images/clg3.jpg'),
                ];
            @endphp

            <div class="flex justify-center md:justify-start">
                <div id="slider" class="w-full max-w-[480px] h-[220px] sm:h-[280px] md:h-[320px] relative overflow-hidden rounded-2xl">

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
            <div class="space-y-4 sm:space-y-5 text-center md:text-left">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
                    Welcome to <br>
                    <span class="text-indigo-600">Digital Internship Portal</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-700 max-w-xl mx-auto md:mx-0 leading-relaxed">
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

    <div id="studentHelpBot" class="fixed bottom-4 right-4 sm:bottom-[6%] sm:right-5 z-50">
        <div id="chatbotPanel" class="hidden w-[320px] max-w-[calc(100vw-1.5rem)] rounded-[1.4rem] border border-blue-100 bg-white shadow-2xl overflow-hidden chatbot-panel-enter">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3.5 text-white">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 shadow-inner">
                            <span class="text-lg">🤖</span>
                            <span class="absolute bottom-0.5 right-0.5 h-2.5 w-2.5 rounded-full border-2 border-blue-600 bg-emerald-400"></span>
                        </div>
                        <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Student Help Bot</p>
                        <h3 class="text-base font-bold mt-1">Internship Portal Assistant</h3>
                        <p class="text-[13px] text-blue-100 mt-1">Help with login, registration, approval, and website details.</p>
                        <p class="text-[11px] text-blue-100/90 mt-1">Online now</p>
                        </div>
                    </div>
                    <button id="closeChatbot" type="button" class="h-8 w-8 rounded-full bg-white/15 hover:bg-white/25 text-white text-lg leading-none">×</button>
                </div>
            </div>

            <div id="chatbotMessages" class="h-[300px] overflow-y-auto bg-slate-50 px-3.5 py-3.5 space-y-3">
                <div class="flex items-end gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm">🤖</div>
                    <div>
                        <div class="max-w-[85%] rounded-2xl rounded-bl-md bg-blue-600 px-3.5 py-2.5 text-[13px] text-white shadow-sm">
                            Hello. I am the student help bot. I can explain how to register, how to log in, how approval works, and what this website is used for.
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">Assistant • just now</p>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm">🤖</div>
                    <div>
                        <div class="max-w-[90%] rounded-2xl rounded-tl-md bg-white px-3.5 py-2.5 text-[13px] text-slate-700 shadow-sm border border-slate-200">
                            Suggested topics: How to register, How to login, Approval process, Website info.
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">Assistant • just now</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-white px-3.5 py-3.5">
                <div class="flex flex-wrap gap-2 mb-3">
                    <button type="button" class="chatbot-chip">How to register?</button>
                    <button type="button" class="chatbot-chip">How to login?</button>
                    <button type="button" class="chatbot-chip">Approval process</button>
                    <button type="button" class="chatbot-chip">Website info</button>
                </div>

                <div class="flex items-center gap-2">
                    <input id="chatbotInput" type="text" class="flex-1 rounded-full border border-slate-300 px-4 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Type your question in English">
                    <button id="sendChatbotMessage" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-9.193-5.11A1 1 0 004 6.941v10.118a1 1 0 001.559.832l9.193-5.11a1 1 0 000-1.664z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <button id="openChatbot" type="button" class="chatbot-launcher inline-flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-2xl hover:bg-blue-700" aria-label="Open student help chatbot">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 7l2.5-2.5A2 2 0 003 17.086V6a2 2 0 012-2h14a2 2 0 012 2v11a2 2 0 01-2 2H8.914A2 2 0 007.5 19.5L5 22z" />
            </svg>
            <span class="chatbot-launcher-badge">1</span>
        </button>
    </div>

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

    const chatbotPanel = document.getElementById('chatbotPanel');
    const openChatbot = document.getElementById('openChatbot');
    const closeChatbot = document.getElementById('closeChatbot');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotInput = document.getElementById('chatbotInput');
    const sendChatbotMessage = document.getElementById('sendChatbotMessage');
    const chatbotChips = document.querySelectorAll('.chatbot-chip');
    let isBotTyping = false;

    const setChatbotBusyState = (busy) => {
        isBotTyping = busy;
        chatbotInput.disabled = busy;
        sendChatbotMessage.disabled = busy;
        chatbotInput.placeholder = busy ? 'Assistant is typing...' : 'Type your question in English';
        sendChatbotMessage.classList.toggle('opacity-60', busy);
        sendChatbotMessage.classList.toggle('cursor-not-allowed', busy);
    };

    const getTimeLabel = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const appendMessage = (content, sender = 'bot') => {
        const row = document.createElement('div');
        row.className = sender === 'user' ? 'flex justify-end' : 'flex items-end gap-2';

        if (sender === 'bot') {
            const avatar = document.createElement('div');
            avatar.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm';
            avatar.textContent = '🤖';
            row.appendChild(avatar);
        }

        const stack = document.createElement('div');
        const bubble = document.createElement('div');
        bubble.className = sender === 'user'
            ? 'ml-auto max-w-[85%] rounded-2xl rounded-br-md bg-slate-900 px-3.5 py-2.5 text-[13px] text-white shadow-sm'
            : 'max-w-[90%] rounded-2xl rounded-tl-md bg-white px-3.5 py-2.5 text-[13px] text-slate-700 shadow-sm border border-slate-200';
        bubble.textContent = content;

        const meta = document.createElement('p');
        meta.className = sender === 'user' ? 'mt-1 text-right text-[10px] text-slate-400' : 'mt-1 text-[10px] text-slate-400';
        meta.textContent = `${sender === 'user' ? 'You' : 'Assistant'} • ${getTimeLabel()}`;

        stack.appendChild(bubble);
        stack.appendChild(meta);
        row.appendChild(stack);
        chatbotMessages.appendChild(row);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    };

    const appendTypingIndicator = () => {
        const wrapper = document.createElement('div');
        wrapper.id = 'chatbotTypingIndicator';
        wrapper.className = 'flex items-end gap-2';
        wrapper.innerHTML = '<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm">🤖</div><div><div class="max-w-[72px] rounded-2xl rounded-tl-md bg-white px-3.5 py-3 shadow-sm border border-slate-200"><div class="chatbot-typing-dots"><span></span><span></span><span></span></div></div><p class="mt-1 text-[10px] text-slate-400">Assistant is typing...</p></div>';
        chatbotMessages.appendChild(wrapper);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    };

    const removeTypingIndicator = () => {
        document.getElementById('chatbotTypingIndicator')?.remove();
    };

    const getBotReply = (message) => {
        const text = message.toLowerCase();

        if (text.includes('register')) {
            return 'To register, click Register Now on the home page. Fill in your full name, username, email, class, department, password, and confirm password. Submit the form to send your registration request.';
        }

        if (text.includes('login') || text.includes('sign in')) {
            return 'To log in as a student, click Student Login on the home page. Enter your username or email, type your password, and click Login.';
        }

        if (text.includes('approval') || text.includes('accept') || text.includes('teacher request') || text.includes('request')) {
            return 'After your registration is submitted, your department teacher will review your request. Your student account will be approved only after the teacher accepts your registration request.';
        }

        if (text.includes('website') || text.includes('portal') || text.includes('info') || text.includes('about')) {
            return 'This website is the Internship Tracking System for students and teachers. Students can register, log in, apply for internships, view announcements, and maintain diary records. Teachers can review requests, manage batches, internships, and announcements.';
        }

        if (text.includes('password')) {
            return 'Use the password you created during student registration. If you enter the wrong password, you will not be able to log in until the correct password is used.';
        }

        return 'I can help with student registration, student login, approval process, and website information. Try asking: How to register? How to login? Approval process. Website info.';
    };

    const sendMessage = (message) => {
        const cleanMessage = message.trim();
        if (!cleanMessage || isBotTyping) {
            return;
        }

        appendMessage(cleanMessage, 'user');
        setChatbotBusyState(true);
        appendTypingIndicator();
        window.setTimeout(() => {
            removeTypingIndicator();
            appendMessage(getBotReply(cleanMessage), 'bot');
            setChatbotBusyState(false);
            chatbotInput.focus();
        }, 2000);
    };

    openChatbot?.addEventListener('click', () => {
        chatbotPanel.classList.remove('hidden');
        openChatbot.classList.add('hidden');
        chatbotInput?.focus();
    });

    closeChatbot?.addEventListener('click', () => {
        chatbotPanel.classList.add('hidden');
        openChatbot.classList.remove('hidden');
    });

    sendChatbotMessage?.addEventListener('click', () => {
        sendMessage(chatbotInput.value);
        chatbotInput.value = '';
    });

    chatbotInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage(chatbotInput.value);
            chatbotInput.value = '';
        }
    });

    chatbotChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            sendMessage(chip.textContent || '');
        });
    });
});
</script>

<style>
    img {
        image-rendering: auto;
        backface-visibility: hidden;
    }

    .chatbot-chip {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 9999px;
        padding: 0.42rem 0.78rem;
        font-size: 0.74rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .chatbot-chip:hover {
        background: #dbeafe;
        border-color: #93c5fd;
    }

    .chatbot-panel-enter {
        animation: chatbotPanelEnter 0.24s ease;
        transform-origin: bottom right;
    }

    .chatbot-launcher {
        position: relative;
        animation: chatbotFloat 3s ease-in-out infinite;
    }

    .chatbot-launcher::before {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 9999px;
        border: 1px solid rgba(59, 130, 246, 0.28);
        animation: chatbotPulse 2.2s infinite;
    }

    .chatbot-launcher-badge {
        position: absolute;
        top: -0.2rem;
        right: -0.15rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.15rem;
        height: 1.15rem;
        padding: 0 0.2rem;
        border-radius: 9999px;
        background: #ffffff;
        color: #2563eb;
        font-size: 0.68rem;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
    }

    .chatbot-typing-dots {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
    }

    .chatbot-typing-dots span {
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 9999px;
        background: #60a5fa;
        animation: chatbotTyping 1s infinite ease-in-out;
    }

    .chatbot-typing-dots span:nth-child(2) {
        animation-delay: 0.16s;
    }

    .chatbot-typing-dots span:nth-child(3) {
        animation-delay: 0.32s;
    }

    @keyframes chatbotTyping {
        0%, 80%, 100% {
            transform: scale(0.75);
            opacity: 0.45;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes chatbotPulse {
        0% {
            transform: scale(0.95);
            opacity: 0.65;
        }
        70% {
            transform: scale(1.08);
            opacity: 0;
        }
        100% {
            transform: scale(1.08);
            opacity: 0;
        }
    }

    @keyframes chatbotFloat {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    @keyframes chatbotPanelEnter {
        from {
            opacity: 0;
            transform: translateY(10px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
</style>

</body>
</html>
