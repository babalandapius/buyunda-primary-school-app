<?php
/**
 * About Us Page - Buyunda Primary School
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
    <title>About Us | <?php echo htmlspecialchars($schoolName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen antialiased">

    <!-- Header Navigation -->
    <header class="bg-emerald-800 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 sm:py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3 min-w-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-white text-emerald-800 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-lg sm:text-xl shadow">B</div>
                <div class="min-w-0">
                    <a href="index.php" class="text-base sm:text-xl font-bold tracking-tight block truncate"><?php echo htmlspecialchars($schoolName); ?></a>
                    <p class="text-[10px] sm:text-xs text-emerald-200 truncate">Excellence, Discipline & Growth</p>
                </div>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                <a href="index.php" class="hover:text-emerald-200 transition">Home</a>
                <a href="about.php" class="text-emerald-200 font-semibold">About Us</a>
                <a href="academics.php" class="hover:text-emerald-200 transition">Academics</a>
                <a href="gallery.php" class="hover:text-emerald-200 transition">Gallery</a>
                <a href="contact.php" class="hover:text-emerald-200 transition">Contact Us</a>
            </nav>

            <!-- Desktop Actions -->
            <div class="hidden md:flex items-center space-x-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $userRole === 'ADMIN' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" class="bg-white text-emerald-800 px-4 py-2 rounded-lg font-semibold hover:bg-emerald-50 transition text-sm shadow-sm">Dashboard</a>
                    <a href="logout.php" class="bg-emerald-900 text-white px-3.5 py-2 rounded-lg font-semibold hover:bg-emerald-950 transition text-sm">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-lg font-semibold shadow transition text-sm">Staff Login</a>
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
            <a href="index.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Home</a>
            <a href="about.php" class="block px-3 py-2 rounded-lg text-base font-semibold bg-emerald-800 text-white">About Us</a>
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

    <!-- Page Header Banner -->
    <section class="bg-gradient-to-r from-emerald-800 to-teal-800 text-white py-10 sm:py-14 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight">About Our School</h1>
            <p class="text-emerald-100 mt-2 text-sm sm:text-base">Discover our history, values, and educational philosophy.</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14 flex-grow space-y-12 w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-center">
            <div class="space-y-4">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Welcome to <?php echo htmlspecialchars($schoolName); ?></h2>
                <p class="text-slate-600 leading-relaxed text-sm sm:text-base">
                    Founded to nurture young minds, Buyunda Primary School provides high-quality primary education focused on holistic development, sound moral values, and academic excellence.
                </p>
                <p class="text-slate-600 leading-relaxed text-sm sm:text-base">
                    We maintain an environment where every child feels valued, encouraged, and challenged to achieve their highest personal potential both inside and outside the classroom.
                </p>
            </div>
            <div class="bg-emerald-50 rounded-2xl p-6 sm:p-8 border border-emerald-100 space-y-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-800 mb-2">Our Vision</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">To be a leading educational institution producing well-rounded, innovative, and morally grounded future leaders.</p>
                </div>
                <hr class="border-emerald-200">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-emerald-800 mb-2">Our Mission</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">To deliver quality primary education through child-centered teaching practices, dedicated mentorship, and strong community partnership.</p>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 text-center mb-8">Our Core Values</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center font-bold text-lg mx-auto mb-3">01</div>
                    <h3 class="font-bold text-slate-800 mb-1">Excellence</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Striving for high academic and moral standards in everything we do.</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center font-bold text-lg mx-auto mb-3">02</div>
                    <h3 class="font-bold text-slate-800 mb-1">Discipline</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Cultivating self-control, respect, and responsibility across our community.</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center font-bold text-lg mx-auto mb-3">03</div>
                    <h3 class="font-bold text-slate-800 mb-1">Integrity</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Fostering honesty, accountability, and strong ethical principles.</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center font-bold text-lg mx-auto mb-3">04</div>
                    <h3 class="font-bold text-slate-800 mb-1">Inclusivity</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Welcoming learners from diverse backgrounds into a supportive environment.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-300 py-6 px-4 border-t border-slate-800 text-center text-xs">
        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($schoolName); ?>. All rights reserved.
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