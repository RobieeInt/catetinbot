<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robby Hernowo — Full Stack Developer</title>
    <meta name="description" content="Full Stack Developer · Laravel & API Specialist · Mobile App Developer · AI & Automation Enthusiast">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --glow-blue: rgba(96,165,250,0.15);
            --glow-violet: rgba(167,139,250,0.15);
            --glow-amber: rgba(245,158,11,0.15);
        }
        * { box-sizing: border-box; }
        body { background-color: #080808; color: #d1d5db; overflow-x: hidden; }

        /* ---- NOISE TEXTURE ---- */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        /* ---- FLOATING ORBS ---- */
        .orb {
            position: fixed; border-radius: 50%; filter: blur(80px);
            pointer-events: none; z-index: 0; opacity: 0.35;
            animation: float 12s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #1d4ed8 0%, transparent 70%); top: -150px; right: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #6d28d9 0%, transparent 70%); bottom: 20%; left: -100px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #0f766e 0%, transparent 70%); top: 40%; right: 10%; animation-delay: -8s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            33% { transform: translateY(-30px) scale(1.05); }
            66% { transform: translateY(15px) scale(0.97); }
        }

        /* ---- STATUS DOTS ---- */
        .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .dot-active   { background: #22c55e; box-shadow: 0 0 8px #22c55e99; }
        .dot-building { background: #f59e0b; box-shadow: 0 0 8px #f59e0b99; animation: pulse-dot 2s ease-in-out infinite; }
        .dot-dev      { background: #818cf8; box-shadow: 0 0 8px #818cf899; animation: pulse-dot 2s ease-in-out infinite; }
        .dot-ongoing  { background: #38bdf8; box-shadow: 0 0 8px #38bdf899; }
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.4} }

        /* ---- CARDS ---- */
        .project-card {
            background: rgba(15,15,18,0.8);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            display: block;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(.22,.68,0,1.2), border-color 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .project-card:focus-visible {
            outline: 2px solid rgba(96,165,250,0.7);
            outline-offset: 4px;
        }
        .project-card::before {
            content: '';
            position: absolute; inset: 0; border-radius: 20px;
            background: radial-gradient(circle at var(--mx,50%) var(--my,50%), rgba(255,255,255,0.04) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .project-card:hover { transform: translateY(-6px); box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .project-card:hover::before { opacity: 1; }
        .project-card:hover { border-color: rgba(255,255,255,0.12); }

        /* Glow border per card type */
        .card-glow-blue:hover   { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(59,130,246,0.3), 0 0 30px rgba(59,130,246,0.08); }
        .card-glow-amber:hover  { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,158,11,0.3), 0 0 30px rgba(245,158,11,0.08); }
        .card-glow-green:hover  { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(34,197,94,0.3),  0 0 30px rgba(34,197,94,0.08); }
        .card-glow-violet:hover { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(139,92,246,0.3), 0 0 30px rgba(139,92,246,0.08); }
        .card-glow-cyan:hover   { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(6,182,212,0.3),  0 0 30px rgba(6,182,212,0.08); }
        .card-glow-rose:hover   { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(244,63,94,0.3),  0 0 30px rgba(244,63,94,0.08); }
        .card-glow-sky:hover    { box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(56,189,248,0.3), 0 0 30px rgba(56,189,248,0.08); }

        .project-shot {
            aspect-ratio: 16 / 9;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            margin: -8px -8px 20px;
            position: relative;
        }
        .project-shot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.45s ease, filter 0.45s ease;
        }
        .project-card:hover .project-shot img { transform: scale(1.04); filter: saturate(1.08); }
        .project-shot::after {
            content: 'Preview gallery';
            position: absolute;
            right: 10px;
            bottom: 10px;
            color: #e5e7eb;
            background: rgba(0,0,0,0.55);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            backdrop-filter: blur(8px);
        }
        .project-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #d1d5db;
            border: 1px solid rgba(255,255,255,0.09);
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            padding: 7px 10px;
            font-size: 12px;
            transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
        }
        .project-link:hover {
            color: white;
            border-color: rgba(96,165,250,0.45);
            background: rgba(96,165,250,0.1);
        }

        /* ---- PROJECT MODAL ---- */
        .project-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .project-modal.open { display: flex; }
        .project-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.78);
            backdrop-filter: blur(14px);
        }
        .project-modal__panel {
            position: relative;
            z-index: 1;
            width: min(1120px, 100%);
            max-height: min(88vh, 860px);
            background: rgba(10,10,12,0.96);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            box-shadow: 0 30px 90px rgba(0,0,0,0.65);
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
        }
        .project-modal__media {
            min-height: 0;
            background: #050506;
            display: flex;
            flex-direction: column;
        }
        .project-modal__stage {
            position: relative;
            min-height: 360px;
            height: 62vh;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .project-modal__stage img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .project-modal__nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: rgba(0,0,0,0.48);
            border: 1px solid rgba(255,255,255,0.16);
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .project-modal__nav:hover { background: rgba(0,0,0,0.7); border-color: rgba(255,255,255,0.3); }
        .project-modal__nav.prev { left: 16px; }
        .project-modal__nav.next { right: 16px; }
        .project-modal__thumbs {
            display: flex;
            gap: 10px;
            padding: 14px;
            overflow-x: auto;
        }
        .project-modal__thumb {
            width: 96px;
            aspect-ratio: 16 / 9;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.12);
            opacity: 0.55;
            flex: 0 0 auto;
            background: rgba(255,255,255,0.05);
        }
        .project-modal__thumb.active {
            opacity: 1;
            border-color: rgba(96,165,250,0.85);
            box-shadow: 0 0 0 2px rgba(96,165,250,0.18);
        }
        .project-modal__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .project-modal__side {
            padding: 24px;
            overflow-y: auto;
            border-left: 1px solid rgba(255,255,255,0.08);
        }
        .project-modal__close {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 2;
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        body.modal-open { overflow: hidden; }

        /* ---- SCROLL REVEAL ---- */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* ---- TECH PILL ---- */
        .tech-pill {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border-radius: 6px;
            font-size: 11px; font-family: monospace;
            border: 1px solid; font-weight: 500;
        }

        /* ---- TERMINAL ---- */
        .terminal-wrap {
            background: #0d0d0f;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 40px 80px rgba(0,0,0,0.6);
        }
        .terminal-body {
            padding: 28px 32px;
            font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', ui-monospace, monospace;
            font-size: 13px;
            line-height: 1.8;
            min-height: 420px;
        }
        .t-line { opacity: 0; transform: translateY(3px); transition: opacity 0.25s ease, transform 0.25s ease; }
        .t-line.show { opacity: 1; transform: translateY(0); }
        .t-cursor::after { content: '▋'; color: #22c55e; animation: blink 1s step-end infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
        .scanline {
            position: absolute; inset: 0; pointer-events: none;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.025) 2px, rgba(0,0,0,0.025) 4px);
        }

        /* ---- HERO BADGE ---- */
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 100px; padding: 6px 16px;
            font-size: 12px; color: #9ca3af;
            backdrop-filter: blur(8px);
        }

        /* ---- SECTION LABEL ---- */
        .section-label {
            font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.15em; color: #6b7280;
            font-weight: 600;
        }

        /* ---- GRID MASONRY-LIKE ---- */
        @media (min-width: 768px) {
            .projects-grid { columns: 2; column-gap: 20px; }
            .projects-grid .project-card { break-inside: avoid; margin-bottom: 20px; }
        }
        @media (min-width: 1024px) {
            .projects-grid { columns: 3; }
        }
        @media (max-width: 767px) {
            .projects-grid .project-card { margin-bottom: 16px; }
            .project-modal { padding: 10px; }
            .project-modal__panel {
                grid-template-columns: 1fr;
                max-height: 92vh;
            }
            .project-modal__stage {
                min-height: 240px;
                height: 42vh;
            }
            .project-modal__side {
                border-left: 0;
                border-top: 1px solid rgba(255,255,255,0.08);
                padding: 18px;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    {{-- NAV --}}
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-white/5 backdrop-blur-xl bg-black/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <span class="font-bold text-white tracking-tight text-sm">RH<span class="text-blue-400">.</span></span>
            <div class="hidden sm:flex items-center gap-6">
                <a href="#about" class="text-xs text-gray-500 hover:text-white transition-colors">About</a>
                <a href="#projects" class="text-xs text-gray-500 hover:text-white transition-colors">Projects</a>
                <a href="#terminal" class="text-xs text-gray-500 hover:text-white transition-colors">Terminal</a>
                <a href="#contact" class="text-xs text-gray-500 hover:text-white transition-colors">Contact</a>
            </div>
            <a href="{{ route('dashboard.login') }}"
               class="text-xs text-gray-400 hover:text-white border border-white/10 hover:border-white/25 px-4 py-1.5 rounded-lg transition-all">
                Dashboard →
            </a>
        </div>
    </nav>

    <main class="relative z-10 pt-14">

        {{-- ================================================================ --}}
        {{-- HERO --}}
        {{-- ================================================================ --}}
        <section class="min-h-[92vh] flex items-center justify-center px-4 sm:px-6 py-24">
            <div class="max-w-3xl mx-auto text-center">
                <div class="hero-badge mb-8">
                    <span class="dot dot-building"></span>
                    Currently building · Open for projects
                </div>
                <h1 class="text-5xl sm:text-7xl font-bold tracking-tight text-white mb-6 leading-[1.1]">
                    Robby<br>
                    <span style="background:linear-gradient(135deg,#60a5fa,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Hernowo</span>
                </h1>
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    <span class="tech-pill text-blue-400 bg-blue-500/10 border-blue-500/20">Full Stack Developer</span>
                    <span class="tech-pill text-violet-400 bg-violet-500/10 border-violet-500/20">Laravel Specialist</span>
                    <span class="tech-pill text-cyan-400 bg-cyan-500/10 border-cyan-500/20">RESTful API Integration</span>
                    <span class="tech-pill text-green-400 bg-green-500/10 border-green-500/20">Flutter & React Native</span>
                    <span class="tech-pill text-pink-400 bg-pink-500/10 border-pink-500/20">AI & Automation Enthusiast</span>
                </div>
                <p class="text-gray-400 text-base sm:text-lg leading-relaxed max-w-xl mx-auto mb-12">
                    Membangun solusi digital yang
                    <span class="text-white font-medium">sederhana, cepat, dan benar-benar digunakan.</span>
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="#projects"
                       class="bg-white text-black font-semibold px-7 py-3 rounded-xl hover:bg-gray-100 transition-colors text-sm">
                        Lihat Projects
                    </a>
                    <a href="#about"
                       class="border border-white/10 text-gray-300 font-medium px-7 py-3 rounded-xl hover:border-white/25 hover:text-white transition-all text-sm">
                        Tentang Saya
                    </a>
                </div>

                <div class="mt-20 flex justify-center">
                    <div class="flex flex-col items-center gap-2 text-gray-600 text-xs animate-bounce">
                        <span>scroll</span>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12l7 7 7-7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- ABOUT --}}
        {{-- ================================================================ --}}
        <section id="about" class="py-24 px-4 sm:px-6 border-t border-white/5">
            <div class="max-w-6xl mx-auto">
                <div class="mb-14 reveal">
                    <p class="section-label mb-3">About the Builder</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white">Hi! 👋</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-start">
                    <div class="space-y-5 text-gray-400 leading-relaxed reveal">
                        <p>
                            Saya adalah <span class="text-white font-semibold">Full Stack Developer</span> yang fokus membangun solusi digital yang sederhana, cepat, dan benar-benar digunakan.
                        </p>
                        <p>
                            Mulai dari website company profile, dashboard administrasi, sistem bisnis custom, RESTful API untuk aplikasi mobile, hingga integrasi AI dan automasi workflow menggunakan <span class="text-white">Laravel, Livewire, Flutter, React Native, Telegram Bot,</span> dan berbagai teknologi modern lainnya.
                        </p>
                        <p>
                            Selain membangun produk untuk klien melalui <span class="text-white font-medium">Reconext</span>, saya juga aktif mengembangkan berbagai project pribadi untuk mengeksplorasi teknologi terbaru dan meningkatkan efisiensi proses bisnis.
                        </p>
                        <blockquote class="text-white italic border-l-2 border-blue-500 pl-5 py-1 text-sm leading-relaxed">
                            "Software yang baik bukan yang paling kompleks, melainkan yang paling membantu menyelesaikan masalah nyata."
                        </blockquote>
                    </div>

                    <div class="space-y-2.5 reveal" style="transition-delay:0.1s">
                        @php
                        $stack = [
                            ['Laravel',          'Backend & Full-stack Framework', 'text-red-400',    'bg-red-500/10',    'border-red-500/20'],
                            ['Livewire',         'Reactive UI without SPA',        'text-pink-400',   'bg-pink-500/10',   'border-pink-500/20'],
                            ['MySQL',            'Relational Database',            'text-blue-400',   'bg-blue-500/10',   'border-blue-500/20'],
                            ['RESTful API',      'Backend API & Third-party Integration', 'text-cyan-400', 'bg-cyan-500/10', 'border-cyan-500/20'],
                            ['Flutter',          'Cross-platform Mobile Apps',     'text-sky-400',    'bg-sky-500/10',    'border-sky-500/20'],
                            ['React Native',     'Mobile App Development',         'text-indigo-400', 'bg-indigo-500/10', 'border-indigo-500/20'],
                            ['Gemini AI',        'AI Integration & OCR',           'text-violet-400', 'bg-violet-500/10', 'border-violet-500/20'],
                            ['Telegram Bot API', 'Automation & Messaging',         'text-cyan-400',   'bg-cyan-500/10',   'border-cyan-500/20'],
                            ['Tailwind CSS',     'Utility-first Styling',          'text-teal-400',   'bg-teal-500/10',   'border-teal-500/20'],
                        ];
                        @endphp
                        @foreach($stack as $s)
                        <div class="flex items-center gap-3 p-3.5 rounded-xl"
                             style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06)">
                            <span class="tech-pill {{ $s[2] }} {{ $s[3] }} {{ $s[4] }}">{{ $s[0] }}</span>
                            <span class="text-sm text-gray-500">{{ $s[1] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- PROJECTS --}}
        {{-- ================================================================ --}}
        @php
            $projectImages = function (string $slug) {
                $files = glob(public_path("project/{$slug}/*.{png,jpg,jpeg,webp}"), GLOB_BRACE) ?: [];
                natsort($files);

                return array_map(
                    fn ($path) => asset('project/'.$slug.'/'.basename($path)),
                    array_values($files)
                );
            };

            $projects = [
                [
                    'slug' => 'reconext',
                    'title' => 'Reconext',
                    'category' => 'Digital Agency',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-blue',
                    'accent' => 'text-blue-500',
                    'pill' => 'text-blue-400 bg-blue-500/10 border-blue-500/20',
                    'link' => 'https://reconext.id/',
                    'description' => 'Digital agency untuk website, landing page, sistem bisnis custom, automasi workflow, dan integrasi AI untuk UMKM maupun perusahaan.',
                    'features' => ['Company Profile', 'Service Landing Page', 'Lead Capture', 'Automation Offering', 'AI Integration'],
                    'stack' => ['Laravel', 'Blade', 'RESTful API', 'Tailwind CSS', 'JavaScript', 'WhatsApp CTA'],
                ],
                [
                    'slug' => 'jenzaudio',
                    'title' => 'Jenz Audio',
                    'category' => 'Business Website',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-green',
                    'accent' => 'text-green-500',
                    'pill' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'link' => 'https://jenzaudiogs.com/',
                    'description' => 'Website bisnis untuk branding, product showcase, dan katalog digital Jenz Audio dengan tampilan visual yang fokus ke konversi.',
                    'features' => ['Product Showcase', 'Digital Catalog', 'Landing Page', 'Responsive UI', 'Marketing Optimization'],
                    'stack' => ['Laravel', 'Blade', 'RESTful API', 'Tailwind CSS', 'JavaScript', 'SEO Basics'],
                ],
                [
                    'slug' => 'morizono',
                    'title' => 'Morizono',
                    'category' => 'Property Website',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-cyan',
                    'accent' => 'text-cyan-500',
                    'pill' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20',
                    'link' => 'https://morizono.co.id/',
                    'description' => 'Website properti premium untuk Morizono Japanese Living, lengkap dengan unit types, facilities, development progress, updates, dan lead form.',
                    'features' => ['Unit Types', 'Facilities Showcase', 'News & Updates', 'Contact Form', 'WhatsApp Lead'],
                    'stack' => ['Laravel', 'Blade', 'RESTful API', 'Tailwind CSS', 'JavaScript', 'Content Pages'],
                ],
                [
                    'slug' => 'eventicketing',
                    'title' => 'Event Ticketing System',
                    'category' => 'Event Management',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-violet',
                    'accent' => 'text-violet-500',
                    'pill' => 'text-violet-400 bg-violet-500/10 border-violet-500/20',
                    'link' => null,
                    'description' => 'Platform manajemen event dan ticketing untuk registrasi, monitoring peserta, pengelolaan tiket, serta reporting operasional.',
                    'features' => ['Event Management', 'Ticket Registration', 'Participant Dashboard', 'Check-in Flow', 'Reporting'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'QR Workflow', 'Admin Dashboard'],
                ],
                [
                    'slug' => 'restaurantpos',
                    'title' => 'Restaurant POS System',
                    'category' => 'Restaurant POS',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-rose',
                    'accent' => 'text-rose-500',
                    'pill' => 'text-rose-400 bg-rose-500/10 border-rose-500/20',
                    'link' => null,
                    'description' => 'Sistem POS restoran untuk operasional kasir, pemesanan, menu, meja, pembayaran, hingga laporan penjualan.',
                    'features' => ['Order Management', 'Cashier System', 'Menu Management', 'Sales Report', 'Operational Dashboard'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'Tailwind CSS', 'Role Access'],
                ],
                [
                    'slug' => 'rsms',
                    'title' => 'RSMS',
                    'category' => 'Business System',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-sky',
                    'accent' => 'text-sky-500',
                    'pill' => 'text-sky-400 bg-sky-500/10 border-sky-500/20',
                    'link' => null,
                    'description' => 'Sistem dashboard operasional untuk pengelolaan data bisnis, monitoring workflow, dan reporting internal.',
                    'features' => ['Dashboard', 'Data Management', 'Reporting', 'Workflow Monitoring', 'Admin Tools'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'Tailwind CSS', 'Charts'],
                ],
                [
                    'slug' => 'hris',
                    'title' => 'HRIS',
                    'category' => 'HR Management',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-amber',
                    'accent' => 'text-amber-500',
                    'pill' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                    'link' => null,
                    'description' => 'Human resource information system untuk membantu pengelolaan data karyawan, attendance, request, dan dashboard HR.',
                    'features' => ['Employee Data', 'Attendance', 'Leave Request', 'HR Dashboard', 'Role Access'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'Tailwind CSS', 'Admin Panel'],
                ],
                [
                    'slug' => 'garudaclub',
                    'title' => 'Garuda Club',
                    'category' => 'Membership Platform',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-violet',
                    'accent' => 'text-violet-500',
                    'pill' => 'text-violet-400 bg-violet-500/10 border-violet-500/20',
                    'link' => null,
                    'description' => 'Platform membership dan dashboard komunitas dengan modul data member, benefit, dan pengelolaan aktivitas.',
                    'features' => ['Member Management', 'Dashboard', 'Activity Data', 'Benefit Tracking', 'Admin Tools'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'Tailwind CSS', 'Dashboard UI'],
                ],
                [
                    'slug' => null,
                    'title' => 'SuperSave',
                    'category' => 'Energy Product Website',
                    'status' => 'Active',
                    'statusClass' => 'text-green-400 bg-green-500/10 border-green-500/20',
                    'dot' => 'dot-active',
                    'glow' => 'card-glow-sky',
                    'accent' => 'text-sky-500',
                    'pill' => 'text-sky-400 bg-sky-500/10 border-sky-500/20',
                    'link' => 'https://supersave.id/',
                    'description' => 'Website company/product untuk solusi energi, katalog produk, insight artikel, testimoni, dan konsultasi via WhatsApp.',
                    'features' => ['Product Catalog', 'Blog & News', 'Testimonials', 'WhatsApp Consultation', 'Responsive Landing'],
                    'stack' => ['Laravel', 'Blade', 'RESTful API', 'Tailwind CSS', 'JavaScript', 'Content Management'],
                ],
                [
                    'slug' => null,
                    'title' => 'Catetin',
                    'category' => 'AI Personal Finance',
                    'status' => 'Building',
                    'statusClass' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                    'dot' => 'dot-building',
                    'glow' => 'card-glow-amber',
                    'accent' => 'text-amber-500',
                    'pill' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                    'link' => null,
                    'description' => 'Aplikasi pencatat keuangan berbasis Telegram Bot dan Dashboard Web yang memahami teks, voice note, dan foto struk menggunakan AI.',
                    'features' => ['OCR Receipt', 'Voice Note Processing', 'Budget Tracking', 'Savings Goal', 'Auto Recap'],
                    'stack' => ['Laravel', 'Livewire', 'RESTful API', 'MySQL', 'Telegram Bot', 'Gemini AI'],
                ],
            ];

            $projects = array_map(function ($project) use ($projectImages) {
                $project['images'] = $project['slug'] ? $projectImages($project['slug']) : [];
                return $project;
            }, $projects);
        @endphp
        <section id="projects" class="py-24 px-4 sm:px-6 border-t border-white/5">
            <div class="max-w-6xl mx-auto">
                <div class="mb-5 reveal">
                    <p class="section-label mb-3">What I'm Building</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Current Projects</h2>
                    <p class="text-gray-500 text-base max-w-xl">
                        Beberapa produk, sistem, dan platform yang sedang saya bangun dan kembangkan.
                    </p>
                </div>

                <div class="projects-grid mt-12">
                    @foreach($projects as $index => $project)
                    <article
                        class="project-card {{ $project['glow'] }} reveal"
                        style="transition-delay:{{ ($index % 9) * 0.04 }}s"
                        role="{{ count($project['images']) ? 'button' : 'article' }}"
                        tabindex="{{ count($project['images']) ? '0' : '-1' }}"
                        data-project-index="{{ $index }}"
                    >
                        @if(count($project['images']))
                        <div class="project-shot">
                            <img src="{{ $project['images'][0] }}" alt="Screenshot {{ $project['title'] }}" loading="lazy">
                        </div>
                        @else
                        <div class="absolute top-0 right-0 w-32 h-32 rounded-full opacity-10"
                             style="background:radial-gradient(circle, currentColor, transparent);transform:translate(30%,-30%)"></div>
                        @endif

                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-xs text-gray-600 uppercase tracking-wider mb-1">{{ $project['category'] }}</p>
                                <h3 class="text-xl font-bold text-white">{{ $project['title'] }}</h3>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs {{ $project['statusClass'] }} border px-2.5 py-1 rounded-full shrink-0 ml-2">
                                <span class="dot {{ $project['dot'] }}"></span> {{ $project['status'] }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-400 leading-relaxed mb-4">{{ $project['description'] }}</p>

                        <ul class="space-y-1 mb-5">
                            @foreach($project['features'] as $feature)
                            <li class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="{{ $project['accent'] }}">▸</span> {{ $feature }}
                            </li>
                            @endforeach
                        </ul>

                        <div class="flex flex-wrap gap-1.5 pt-4 border-t border-white/5">
                            @foreach($project['stack'] as $tech)
                            <span class="tech-pill {{ $project['pill'] }}">{{ $tech }}</span>
                            @endforeach
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3">
                            @if(count($project['images']))
                            <span class="text-xs text-gray-600">{{ count($project['images']) }} screenshots</span>
                            @else
                            <span class="text-xs text-gray-600">Project detail</span>
                            @endif

                            @if($project['link'])
                            <a href="{{ $project['link'] }}" target="_blank" rel="noopener noreferrer" class="project-link" aria-label="Open {{ $project['title'] }} live site">
                                Live site
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8"/>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- TERMINAL --}}
        {{-- ================================================================ --}}
        <section id="terminal" class="py-24 px-4 sm:px-6 border-t border-white/5">
            <div class="max-w-6xl mx-auto">
                <div class="mb-12 reveal">
                    <p class="section-label mb-3">Interactive</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white">Developer Terminal</h2>
                </div>

                <div class="terminal-wrap reveal" id="terminal-box">
                    {{-- Header bar --}}
                    <div class="flex items-center gap-2 px-5 py-3.5 border-b border-white/5"
                         style="background:rgba(255,255,255,0.02)">
                        <span class="w-3 h-3 rounded-full" style="background:#ff5f57"></span>
                        <span class="w-3 h-3 rounded-full" style="background:#febc2e"></span>
                        <span class="w-3 h-3 rounded-full" style="background:#28c840"></span>
                        <span class="ml-4 text-xs text-gray-600 font-mono">robby@dev:~</span>
                        <span class="ml-auto text-xs text-gray-700 font-mono">zsh</span>
                    </div>
                    {{-- Terminal content --}}
                    <div class="terminal-body relative" id="terminal-output-wrap">
                        <div class="scanline"></div>
                        <div id="terminal-output" class="relative z-10 space-y-0.5"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- CONTACT --}}
        {{-- ================================================================ --}}
        <section id="contact" class="py-24 px-4 sm:px-6 border-t border-white/5">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12 reveal">
                    <p class="section-label mb-3">Get in Touch</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Let's Connect</h2>
                    <p class="text-gray-400 text-sm max-w-md mx-auto">Got a project in mind, a collaboration idea, or just want to say hi? I'm always open.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal">
                    {{-- Email --}}
                    <a href="mailto:robbyhernowo@gmail.com"
                       class="group flex items-center gap-3 bg-white/4 hover:bg-white/8 border border-white/8 hover:border-blue-500/40 rounded-2xl px-6 py-4 transition-all duration-300 w-full sm:w-auto">
                        <div class="w-9 h-9 rounded-xl bg-blue-500/15 flex items-center justify-center shrink-0 group-hover:bg-blue-500/25 transition-colors">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-gray-500 mb-0.5">Email</p>
                            <p class="text-sm font-medium text-white group-hover:text-blue-300 transition-colors">robbyhernowo@gmail.com</p>
                        </div>
                    </a>

                    {{-- WhatsApp --}}
                    <a href="https://wa.me/6208568780192" target="_blank" rel="noopener noreferrer"
                       class="group flex items-center gap-3 bg-white/4 hover:bg-white/8 border border-white/8 hover:border-green-500/40 rounded-2xl px-6 py-4 transition-all duration-300 w-full sm:w-auto">
                        <div class="w-9 h-9 rounded-xl bg-green-500/15 flex items-center justify-center shrink-0 group-hover:bg-green-500/25 transition-colors">
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-gray-500 mb-0.5">WhatsApp</p>
                            <p class="text-sm font-medium text-white group-hover:text-green-300 transition-colors">0856 8780 192</p>
                        </div>
                    </a>

                    {{-- LinkedIn --}}
                    <a href="https://www.linkedin.com/in/robby-hernowo-5b003b1b3/" target="_blank" rel="noopener noreferrer"
                       class="group flex items-center gap-3 bg-white/4 hover:bg-white/8 border border-white/8 hover:border-sky-500/40 rounded-2xl px-6 py-4 transition-all duration-300 w-full sm:w-auto">
                        <div class="w-9 h-9 rounded-xl bg-sky-500/15 flex items-center justify-center shrink-0 group-hover:bg-sky-500/25 transition-colors">
                            <svg class="w-4 h-4 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-gray-500 mb-0.5">LinkedIn</p>
                            <p class="text-sm font-medium text-white group-hover:text-sky-300 transition-colors">Robby Hernowo</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- FOOTER --}}
        {{-- ================================================================ --}}
        <footer class="py-12 px-4 sm:px-6 border-t border-white/5">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-white text-sm">RH<span class="text-blue-400">.</span></span>
                    <span class="text-gray-700">·</span>
                    <span class="text-xs text-gray-600">Robby Hernowo</span>
                </div>
                <p class="text-xs text-gray-700">Built with Laravel & shipped with ☕</p>
                <a href="{{ route('dashboard.login') }}"
                   class="text-xs text-gray-500 hover:text-white border border-white/8 hover:border-white/20 px-4 py-1.5 rounded-lg transition-all">
                    Dashboard →
                </a>
            </div>
        </footer>

    </main>

    <div class="project-modal" id="project-modal" aria-hidden="true">
        <div class="project-modal__backdrop" data-modal-close></div>
        <div class="project-modal__panel" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
            <button type="button" class="project-modal__close" data-modal-close aria-label="Close preview">
                <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>

            <div class="project-modal__media">
                <div class="project-modal__stage">
                    <button type="button" class="project-modal__nav prev" data-gallery-prev aria-label="Previous screenshot">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    <img id="project-modal-image" src="" alt="">
                    <button type="button" class="project-modal__nav next" data-gallery-next aria-label="Next screenshot">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="project-modal__thumbs" id="project-modal-thumbs"></div>
            </div>

            <aside class="project-modal__side">
                <p class="section-label mb-3" id="project-modal-category"></p>
                <h3 class="text-2xl font-bold text-white mb-3" id="project-modal-title"></h3>
                <p class="text-sm text-gray-400 leading-relaxed mb-5" id="project-modal-description"></p>

                <div class="mb-6">
                    <p class="text-xs uppercase tracking-wider text-gray-600 mb-3">Highlights</p>
                    <ul class="space-y-2" id="project-modal-features"></ul>
                </div>

                <div class="mb-6">
                    <p class="text-xs uppercase tracking-wider text-gray-600 mb-3">Stack</p>
                    <div class="flex flex-wrap gap-1.5" id="project-modal-stack"></div>
                </div>

                <a id="project-modal-link" href="#" target="_blank" rel="noopener noreferrer" class="project-link hidden">
                    Visit live site
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8"/>
                    </svg>
                </a>
            </aside>
        </div>
    </div>

<script>
const projectData = @json($projects);

// ---- TERMINAL DATA ----
const lines = [
    { d: 200,  type: 'prompt', text: 'whoami' },
    { d: 700,  type: 'out',    text: '<span style="color:#4ade80;font-weight:600">Robby Hernowo</span>' },
    { d: 1100, type: 'gap' },
    { d: 1300, type: 'prompt', text: 'role' },
    { d: 1800, type: 'out',    text: '<span style="color:#60a5fa">Full Stack Developer</span>' },
    { d: 1970, type: 'out',    text: '<span style="color:#a78bfa">Laravel Specialist</span>' },
    { d: 2140, type: 'out',    text: '<span style="color:#22d3ee">RESTful API & Integration Developer</span>' },
    { d: 2310, type: 'out',    text: '<span style="color:#4ade80">Flutter / React Native Mobile Developer</span>' },
    { d: 2480, type: 'out',    text: '<span style="color:#f472b6">AI & Automation Enthusiast</span>' },
    { d: 2500, type: 'gap' },
    { d: 2700, type: 'prompt', text: 'current_projects' },
    { d: 3200, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#4ade80">Reconext</span>                 <span style="color:#374151">[active]</span>' },
    { d: 3350, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#4ade80">Jenz Audio</span>               <span style="color:#374151">[active]</span>' },
    { d: 3500, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#22d3ee">Morizono</span>                 <span style="color:#374151">[active]</span>' },
    { d: 3650, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#818cf8">Event Ticketing System</span>   <span style="color:#374151">[active]</span>' },
    { d: 3800, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#f87171">Restaurant POS</span>           <span style="color:#374151">[active]</span>' },
    { d: 3950, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#38bdf8">RSMS / HRIS / Garuda Club</span> <span style="color:#374151">[active]</span>' },
    { d: 4100, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#38bdf8">SuperSave</span>                <span style="color:#374151">[active]</span>' },
    { d: 4250, type: 'out',    text: '<span style="color:#6b7280">→</span> <span style="color:#fbbf24">Catetin</span>                  <span style="color:#374151">[building]</span>' },
    { d: 4500, type: 'gap' },
    { d: 4700, type: 'prompt', text: 'stack' },
    { d: 5200, type: 'out',    text: '<span style="color:#f87171">Laravel</span>  <span style="color:#f472b6">Livewire</span>  <span style="color:#60a5fa">MySQL</span> <span style="color:#34d399">PHP</span> <span style="color:#fbbf24">JavaScript</span>' },
    { d: 5380, type: 'out',    text: '<span style="color:#22d3ee">RESTful API</span>  <span style="color:#38bdf8">API Integration</span>  <span style="color:#4ade80">Flutter</span>  <span style="color:#818cf8">React Native</span>' },
    { d: 5550, type: 'out',    text: '<span style="color:#34d399">Telegram Bot</span>  <span style="color:#a78bfa">Gemini AI</span>  <span style="color:#2dd4bf">Tailwind CSS</span>' },
    { d: 5720, type: 'out',    text: '<span style="color:#fbbf24">Other Tools:</span> Redis, Docker, GitHub Actions, Vercel, Railway' },
    { d: 5900, type: 'gap' },
    { d: 6100, type: 'prompt', text: 'currently_building' },
    { d: 6400, type: 'out',    text: '<span style="color:#94a3b8">Business Systems</span>' },
    { d: 6550, type: 'out',    text: '<span style="color:#94a3b8">AI Products</span>' },
    { d: 6700, type: 'out',    text: '<span style="color:#94a3b8">Mobile Apps</span>' },
    { d: 6850, type: 'out',    text: '<span style="color:#94a3b8">Automation Tools</span>' },
    { d: 7000, type: 'out',    text: '<span style="color:#94a3b8">Digital Experiences</span>' },
    { d: 7200, type: 'gap' },
    { d: 7400, type: 'prompt', text: 'mission' },
    { d: 7900, type: 'out',    text: '<span style="color:#f1f5f9;font-style:italic">Build products people actually use.</span>' },
    { d: 8300, type: 'gap' },
    { d: 8500, type: 'cursor' },
];

const output = document.getElementById('terminal-output');

function addLine(line) {
    const el = document.createElement('div');
    el.className = 't-line';
    if (line.type === 'prompt') {
        el.innerHTML = `<span style="color:#4ade80">❯</span> <span style="color:#f1f5f9">${line.text}</span>`;
    } else if (line.type === 'out') {
        el.innerHTML = `<span style="padding-left:16px;display:block">${line.text}</span>`;
    } else if (line.type === 'gap') {
        el.innerHTML = '&nbsp;';
        el.style.height = '6px';
    } else if (line.type === 'cursor') {
        el.innerHTML = `<span style="color:#4ade80">❯</span> <span class="t-cursor"></span>`;
    }
    output.appendChild(el);
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
}

let terminalStarted = false;
function startTerminal() {
    if (terminalStarted) return;
    terminalStarted = true;
    lines.forEach(l => setTimeout(() => addLine(l), l.d));
}

// ---- SCROLL REVEAL ----
const revealEls = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); } });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObs.observe(el));

// ---- TERMINAL TRIGGER ----
const termObs = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) { startTerminal(); termObs.disconnect(); }
}, { threshold: 0.2 });
termObs.observe(document.getElementById('terminal-box'));

// ---- CARD MOUSE GLOW ----
document.querySelectorAll('.project-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
        card.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
    });
});

// ---- PROJECT GALLERY MODAL ----
const modal = document.getElementById('project-modal');
const modalImage = document.getElementById('project-modal-image');
const modalThumbs = document.getElementById('project-modal-thumbs');
const modalTitle = document.getElementById('project-modal-title');
const modalCategory = document.getElementById('project-modal-category');
const modalDescription = document.getElementById('project-modal-description');
const modalFeatures = document.getElementById('project-modal-features');
const modalStack = document.getElementById('project-modal-stack');
const modalLink = document.getElementById('project-modal-link');
const modalPrev = document.querySelector('[data-gallery-prev]');
const modalNext = document.querySelector('[data-gallery-next]');
let activeProject = null;
let activeImageIndex = 0;

function setActiveImage(index) {
    if (!activeProject || !activeProject.images.length) return;

    activeImageIndex = (index + activeProject.images.length) % activeProject.images.length;
    modalImage.src = activeProject.images[activeImageIndex];
    modalImage.alt = `${activeProject.title} screenshot ${activeImageIndex + 1}`;

    modalThumbs.querySelectorAll('.project-modal__thumb').forEach((thumb, thumbIndex) => {
        thumb.classList.toggle('active', thumbIndex === activeImageIndex);
        if (thumbIndex === activeImageIndex) thumb.scrollIntoView({ inline: 'center', block: 'nearest' });
    });
}

function openProjectModal(project) {
    if (!project.images.length) return;

    activeProject = project;
    activeImageIndex = 0;
    modalTitle.textContent = project.title;
    modalCategory.textContent = project.category;
    modalDescription.textContent = project.description;

    modalFeatures.innerHTML = project.features.map(feature => (
        `<li class="flex items-center gap-2 text-sm text-gray-400"><span class="${project.accent}">▸</span>${feature}</li>`
    )).join('');

    modalStack.innerHTML = project.stack.map(tech => (
        `<span class="tech-pill ${project.pill}">${tech}</span>`
    )).join('');

    if (project.link) {
        modalLink.href = project.link;
        modalLink.classList.remove('hidden');
    } else {
        modalLink.classList.add('hidden');
    }

    modalThumbs.innerHTML = project.images.map((src, index) => (
        `<button type="button" class="project-modal__thumb" data-thumb-index="${index}" aria-label="Show screenshot ${index + 1}">
            <img src="${src}" alt="">
        </button>`
    )).join('');

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    setActiveImage(0);
    modal.querySelector('.project-modal__close').focus();
}

function closeProjectModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    modalImage.src = '';
    activeProject = null;
}

document.querySelectorAll('.project-card[data-project-index]').forEach(card => {
    const project = projectData[Number(card.dataset.projectIndex)];
    if (!project || !project.images.length) return;

    card.addEventListener('click', e => {
        if (e.target.closest('a')) return;
        openProjectModal(project);
    });

    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openProjectModal(project);
        }
    });
});

modalThumbs.addEventListener('click', e => {
    const thumb = e.target.closest('[data-thumb-index]');
    if (thumb) setActiveImage(Number(thumb.dataset.thumbIndex));
});

modalPrev.addEventListener('click', () => setActiveImage(activeImageIndex - 1));
modalNext.addEventListener('click', () => setActiveImage(activeImageIndex + 1));
document.querySelectorAll('[data-modal-close]').forEach(el => el.addEventListener('click', closeProjectModal));

document.addEventListener('keydown', e => {
    if (!modal.classList.contains('open')) return;
    if (e.key === 'Escape') closeProjectModal();
    if (e.key === 'ArrowLeft') setActiveImage(activeImageIndex - 1);
    if (e.key === 'ArrowRight') setActiveImage(activeImageIndex + 1);
});

// ---- SMOOTH SCROLL ----
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const t = document.querySelector(a.getAttribute('href'));
        if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// ---- STAGGER PROJECTS ON LOAD ----
const projectCards = document.querySelectorAll('.project-card');
const projObs = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); projObs.unobserve(e.target); } });
}, { threshold: 0.08, rootMargin: '0px 0px -20px 0px' });
projectCards.forEach(c => projObs.observe(c));
</script>

</body>
</html>
