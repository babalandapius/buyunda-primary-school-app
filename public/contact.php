<?php
/**
 * Contact Us Page - Buyunda Primary School
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

$schoolName = getSchoolName();
$isLoggedIn = isLoggedIn();
$userRole = $_SESSION['user']['role'] ?? '';
$sentAlert = false;
$emailFeedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $senderName = trim((string) ($_POST['name'] ?? ''));
    $senderEmail = trim((string) ($_POST['email'] ?? ''));
    $messageContent = trim((string) ($_POST['message'] ?? ''));

    if ($senderName !== '' && $senderEmail !== '' && $messageContent !== '') {
        $result = sendContactInquiryNotification($senderName, $senderEmail, $messageContent);
        $sentAlert = true;
        if ($result['success']) {
            $emailFeedback = 'Thank you for reaching out! Your message has been sent directly to the school administration.';
        } else {
            $emailFeedback = 'Thank you for reaching out! Your message has been recorded and our administration has been alerted.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | <?php echo htmlspecialchars($schoolName); ?></title>
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
                <a href="about.php" class="hover:text-emerald-200 transition">About Us</a>
                <a href="academics.php" class="hover:text-emerald-200 transition">Academics</a>
                <a href="gallery.php" class="hover:text-emerald-200 transition">Gallery</a>
                <a href="contact.php" class="text-emerald-200 font-semibold">Contact Us</a>
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
            <a href="about.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">About Us</a>
            <a href="academics.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Academics</a>
            <a href="gallery.php" class="block px-3 py-2 rounded-lg text-base font-medium text-emerald-100 hover:bg-emerald-800 hover:text-white transition">Gallery</a>
            <a href="contact.php" class="block px-3 py-2 rounded-lg text-base font-semibold bg-emerald-800 text-white">Contact Us</a>

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

    <!-- Page Banner -->
    <section class="bg-gradient-to-r from-emerald-800 to-teal-800 text-white py-10 sm:py-14 px-4 sm:px-6">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight">Contact Us</h1>
            <p class="text-emerald-100 mt-2 text-sm sm:text-base">Get in touch with our administration for inquiries and admissions.</p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14 flex-grow w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 sm:gap-10">
            <!-- Contact Info -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Reach Out To Us</h2>
                    <p class="text-slate-600 text-sm sm:text-base mt-2 leading-relaxed">
                        We welcome inquiries regarding admissions, school operations, and academic performance.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start space-x-3.5 p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
                        <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-xl flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-slate-800">Physical Location</p>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Buyunda, Uganda</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3.5 p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
                        <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-xl flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-slate-800">Email Address</p>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 break-all">admin@buyundaprimaryschool.org</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3.5 p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
                        <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-xl flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-slate-800">Phone Contact</p>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">+256 765 990 686</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mb-4">Send Us a Message</h3>

                <?php if ($sentAlert): ?>
                    <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-xs sm:text-sm flex items-start space-x-2.5">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div>
                            <p class="font-semibold text-emerald-900"><?php echo htmlspecialchars($emailFeedback); ?></p>
                            <p class="text-emerald-700 text-xs mt-0.5">Our administrative team will review your inquiry and follow up promptly.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Your Name</label>
                        <input type="text" name="name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="John Doe">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="you@example.com">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Message</label>
                        <textarea name="message" rows="4" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="How can we assist you?"></textarea>
                    </div>
                    <button type="submit" name="send_message" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 rounded-xl text-sm sm:text-base transition shadow-sm">
                        Submit Inquiry
                    </button>
                </form>
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