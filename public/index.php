<?php
/**
 * Main landing page for Buyunda Primary School.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$schoolName = getSchoolName();
$isLoggedIn = isLoggedIn();
$userRole = $_SESSION['user']['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($schoolName); ?> | Nurturing Future Leaders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen antialiased">

    <!-- Header Navigation -->
    <header class="bg-emerald-800 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 sm:py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white text-emerald-800 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-lg sm:text-xl shadow">
                    B
                </div>
                <div class="min-w-0">
                    <a href="index.php" class="text-base sm:text-xl font-bold tracking-tight block truncate"><?php echo htmlspecialchars($schoolName); ?></a>
                    <p class="text-[10px] sm:text-xs text-emerald-200 truncate">Excellence, Discipline & Growth</p>
                </div>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                <a href="index.php" class="text-emerald-200 font-semibold">Home</a>
                <a href="about.php" class="hover:text-emerald-200 transition">About Us</a>
                <a href="academics.php" class="hover:text-emerald-200 transition">Academics</a>
                <a href="gallery.php" class="hover:text-emerald-200 transition">Gallery</a>
                <a href="contact.php" class="hover:text-emerald-200 transition">Contact Us</a>
            </nav>

            <!-- Desktop Actions -->
            <div class="hidden md:flex items-center space-x-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $userRole === 'ADMIN' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" class="bg-white text-emerald-800 px-4 py-2 rounded-lg font-semibold hover:bg-emerald-50 transition text-sm shadow-sm">
                        Dashboard
                    </a>
                    <a href="logout.php" class="bg-emerald-900 text-white px-3.5 py-2 rounded-lg font-semibold hover:bg-emerald-950 transition text-sm">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-lg font-semibold shadow transition text-sm">
                        Staff Login
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center md:hidden">
                <button id="mobile-menu-btn" type="button" class="p-2 rounded-lg text-emerald-100 hover:text-white hover:bg-emerald-700/70 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" aria-label="Toggle Menu" aria-expanded="false">
                    <svg id="menu-icon-open" class="w-6 h-6 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg id="menu-icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-emerald-700/60 bg-emerald-900 px-4 pt-3 pb-5 space-y-2">
            <a href="index.php" class="block px-3 py-2 rounded-lg text-base font-semibold bg-emerald-800 text-white">Home</a>
            <a href="about.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">About Us</a>
            <a href="academics.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Academics</a>
            <a href="gallery.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Gallery</a>
            <a href="contact.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Contact Us</a>

            <div class="pt-3 border-t border-emerald-700/60 flex flex-col gap-2">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $userRole === 'ADMIN' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" class="w-full text-center bg-white text-emerald-900 py-2.5 rounded-lg font-semibold text-sm shadow">
                        Go to Dashboard
                    </a>
                    <a href="logout.php" class="w-full text-center bg-emerald-950 text-white py-2.5 rounded-lg font-semibold text-sm">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="w-full text-center bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 rounded-lg font-semibold text-sm shadow">
                        Staff Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-teal-800 text-white py-12 sm:py-16 md:py-24 px-4 sm:px-6">
        <div class="max-w-5xl mx-auto text-center">
            <span class="inline-block bg-emerald-900/70 text-emerald-200 text-xs sm:text-sm font-semibold uppercase px-3.5 py-1.5 rounded-full border border-emerald-500/30">
                Welcome to Our Official School Portal
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold mt-4 mb-6 leading-tight tracking-tight">
                Empowering Minds, Building Character & Inspiring Success
            </h1>
            <p class="text-base sm:text-lg md:text-xl text-emerald-100 max-w-3xl mx-auto mb-8 font-light leading-relaxed">
                At <?php echo htmlspecialchars($schoolName); ?>, we deliver holistic primary education designed to foster academic brilliance, practical skills, and strong community values.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-3.5 sm:gap-4 max-w-md sm:max-w-none mx-auto">
                <a href="about.php" class="w-full sm:w-auto text-center bg-white text-emerald-800 font-semibold px-6 py-3 rounded-xl shadow-md hover:bg-emerald-50 transition">
                    Learn More About Us
                </a>
                <a href="login.php" class="w-full sm:w-auto text-center bg-emerald-900 border border-emerald-500/60 text-white font-semibold px-6 py-3 rounded-xl hover:bg-emerald-950 transition">
                    Access Portal
                </a>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="bg-white py-8 sm:py-10 border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">
            <div class="p-3 sm:p-4 rounded-xl bg-slate-50/60 md:bg-transparent">
                <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-700">500+</p>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">Active Pupils</p>
            </div>
            <div class="p-3 sm:p-4 rounded-xl bg-slate-50/60 md:bg-transparent">
                <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-700">25+</p>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">Qualified Educators</p>
            </div>
            <div class="p-3 sm:p-4 rounded-xl bg-slate-50/60 md:bg-transparent">
                <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-700">100%</p>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">P.L.E Pass Rate</p>
            </div>
            <div class="p-3 sm:p-4 rounded-xl bg-slate-50/60 md:bg-transparent">
                <p class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-700">10+</p>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">Co-Curricular Clubs</p>
            </div>
        </div>
    </section>

    <!-- About Overview Section -->
    <section class="py-12 sm:py-16 px-4 sm:px-6 max-w-6xl mx-auto w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">About Buyunda Primary School</h2>
                <p class="text-slate-600 mb-4 leading-relaxed text-sm sm:text-base">
                    Founded with a dedication to educational standards, Buyunda Primary School offers a safe, vibrant, and supportive learning environment for young learners.
                </p>
                <p class="text-slate-600 mb-6 leading-relaxed text-sm sm:text-base">
                    Our curriculum focuses on foundational literacy, numeracy, science, digital skills, and social development to prepare students for secondary education and lifelong learning.
                </p>

                <div class="space-y-3 mb-6">
                    <div class="flex items-start sm:items-center space-x-3">
                        <div class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5 sm:mt-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-slate-700 font-medium text-sm sm:text-base">Qualified and dedicated teaching staff</span>
                    </div>
                    <div class="flex items-start sm:items-center space-x-3">
                        <div class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5 sm:mt-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-slate-700 font-medium text-sm sm:text-base">Modern classrooms and learning facilities</span>
                    </div>
                    <div class="flex items-start sm:items-center space-x-3">
                        <div class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5 sm:mt-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-slate-700 font-medium text-sm sm:text-base">Active parent-teacher collaboration</span>
                    </div>
                </div>

                <a href="about.php" class="inline-flex items-center text-emerald-700 font-semibold hover:text-emerald-800 transition text-sm sm:text-base">
                    Read full school profile
                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <div class="bg-emerald-50 rounded-2xl p-6 sm:p-8 border border-emerald-100 space-y-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-800 mb-2">Our Vision</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        To be a leading center of primary educational excellence, nurturing well-rounded, disciplined, and innovative future leaders.
                    </p>
                </div>
                <hr class="border-emerald-200">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-800 mb-2">Our Mission</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        To provide quality primary education through innovative teaching, holistic character building, and an inclusive learning environment.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Academic Programs Preview -->
    <section class="bg-slate-100 py-12 sm:py-16 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Academic Programs</h2>
                <p class="text-slate-600 mt-2 text-sm sm:text-base">Comprehensive primary education tailored for every stage of development</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 md:gap-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                            P1
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold mb-2">Lower Primary (P.1 – P.3)</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Focusing on foundational reading, writing, basic mathematics, social interactions, and creative arts in a supportive environment.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                            P4
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold mb-2">Transition Primary (P.4 – P.5)</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Building core comprehension skills across English, Mathematics, Integrated Science, and Social Studies.
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 sm:col-span-2 md:col-span-1 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center font-bold text-xl mb-4">
                            P7
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold mb-2">Upper Primary (P.6 – P.7)</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Intensive preparation for Primary Leaving Examinations (PLE) alongside leadership development and practical skills.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-8 sm:mt-10">
                <a href="academics.php" class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-6 py-3 rounded-xl transition inline-block text-sm sm:text-base shadow-sm">
                    View Full Curriculum Details
                </a>
            </div>
        </div>
    </section>

    <!-- Digital Portal Features -->
    <section class="py-12 sm:py-16 px-4 sm:px-6 max-w-6xl mx-auto w-full">
        <div class="text-center mb-8 sm:mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Digital School Portal</h2>
            <p class="text-slate-600 mt-2 text-sm sm:text-base">Streamlined administration and academic record management</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 md:gap-8">
            <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200 shadow-sm text-center">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg mb-2">Pupil Record Management</h3>
                <p class="text-slate-600 text-sm">Secure records of student enrollment, class allocations, and parent contacts.</p>
            </div>

            <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200 shadow-sm text-center">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg mb-2">Academic Report Cards</h3>
                <p class="text-slate-600 text-sm">Termly performance tracking, automated subject grading, and printable report cards.</p>
            </div>

            <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200 shadow-sm text-center sm:col-span-2 md:col-span-1">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-lg mb-2">Teacher Attendance</h3>
                <p class="text-slate-600 text-sm">Daily digital sign-in tracking for teachers and instant administration overview.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 mt-auto py-10 sm:py-12 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 mb-8">
            <div>
                <h3 class="text-white font-bold text-lg mb-3"><?php echo htmlspecialchars($schoolName); ?></h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Providing quality primary education and inspiring future leaders through discipline, character, and academic dedication.
                </p>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm uppercase tracking-wider mb-3">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="about.php" class="hover:text-emerald-300 transition inline-block py-1">About Us</a></li>
                    <li><a href="academics.php" class="hover:text-emerald-300 transition inline-block py-1">Academics</a></li>
                    <li><a href="gallery.php" class="hover:text-emerald-300 transition inline-block py-1">School Gallery</a></li>
                    <li><a href="contact.php" class="hover:text-emerald-300 transition inline-block py-1">Contact Us</a></li>
                    <li><a href="login.php" class="hover:text-emerald-300 transition inline-block py-1">Staff Portal</a></li>
                </ul>
            </div>

            <div class="sm:col-span-2 md:col-span-1">
                <h4 class="text-white font-semibold text-sm uppercase tracking-wider mb-3">School Contact</h4>
                <ul class="space-y-3 text-sm text-slate-400">
                    <li class="flex items-center space-x-2.5">
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Buyunda, Uganda</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="break-all">admin@buyundaprimaryschool.org</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span>+256 765 990 686</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($schoolName); ?>. All rights reserved.
        </div>
    </footer>

    <!-- Mobile Navigation Toggle Script -->
    <script>
        (function() {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('menu-icon-open');
            const iconClose = document.getElementById('menu-icon-close');

            if (menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isExpanded = menuBtn.getAttribute('aria-expanded') === 'true';
                    menuBtn.setAttribute('aria-expanded', !isExpanded);
                    mobileMenu.classList.toggle('hidden');
                    iconOpen.classList.toggle('hidden');
                    iconClose.classList.toggle('hidden');
                });

                document.addEventListener('click', function(e) {
                    if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target) && !mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        menuBtn.setAttribute('aria-expanded', 'false');
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    }
                });

                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768 && !mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        menuBtn.setAttribute('aria-expanded', 'false');
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    }
                });
            }
        })();
    </script>
</body>
</html>