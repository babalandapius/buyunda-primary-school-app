<?php
/**
 * School settings page for admin.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('ADMIN');

$schoolName = getSchoolName();
$alert = ['type' => '', 'message' => ''];
$emailTestResult = null;

try {
    $settings = db()->query('SELECT * FROM school_settings ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$settings) {
        $settings = [
            'school_name' => 'Buyunda Primary School',
            'school_email' => 'admin@buyundaprimaryschool.org',
            'contact_phone' => '',
            'address' => '',
        ];
    }
} catch (Throwable $e) {
    error_log('School settings load failed: ' . $e->getMessage());
    $settings = [
        'school_name' => 'Buyunda Primary School',
        'school_email' => 'admin@buyundaprimaryschool.org',
        'contact_phone' => '',
        'address' => '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $schoolNameInput = trim((string) ($_POST['school_name'] ?? ''));
    $schoolEmail = trim((string) ($_POST['school_email'] ?? ''));
    $contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));

    if ($schoolNameInput === '' || $schoolEmail === '') {
        $alert = ['type' => 'error', 'message' => 'School name and email are required.'];
    } else {
        try {
            $stmt = db()->prepare(
                'UPDATE school_settings SET school_name = :school_name, school_email = :school_email, contact_phone = :contact_phone, address = :address WHERE id = :id'
            );
            $stmt->execute([
                ':school_name' => $schoolNameInput,
                ':school_email' => $schoolEmail,
                ':contact_phone' => $contactPhone,
                ':address' => $address,
                ':id' => (int) ($settings['id'] ?? 0),
            ]);

            if ($stmt->rowCount() === 0) {
                $insert = db()->prepare(
                    'INSERT INTO school_settings (school_name, school_email, contact_phone, address, created_at) VALUES (:school_name, :school_email, :contact_phone, :address, NOW())'
                );
                $insert->execute([
                    ':school_name' => $schoolNameInput,
                    ':school_email' => $schoolEmail,
                    ':contact_phone' => $contactPhone,
                    ':address' => $address,
                ]);
            }

            $alert = ['type' => 'success', 'message' => 'School settings updated successfully.'];
        } catch (Throwable $e) {
            error_log('School settings update failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Unable to update school settings.'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $emailTestResult = sendTestEmailToAdmin();
    if ($emailTestResult['success']) {
        $alert = ['type' => 'success', 'message' => $emailTestResult['message']];
    } else {
        $alert = ['type' => 'info', 'message' => $emailTestResult['message'] . (isset($emailTestResult['error']) ? ' (' . $emailTestResult['error'] . ')' : '')];
    }
}

$adminNotificationEmail = getAdminEmail();
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = getenv('SMTP_PORT') ?: '587';
$smtpUser = getenv('SMTP_USER') ?: 'admin@buyundaprimaryschool.org';
$smtpPass = getenv('SMTP_PASS') ?: '';
$isConfigured = !empty($smtpPass) && $smtpPass !== 'your-16-digit-app-password' && $smtpUser !== 'your-actual-email@gmail.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Settings | <?php echo htmlspecialchars($schoolName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased flex flex-col">

    <!-- Admin Navigation Header -->
    <nav class="bg-slate-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-3 min-w-0">
                <a href="index.php" class="w-9 h-9 bg-emerald-600 text-white rounded-xl flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-sm">
                    B
                </a>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold truncate"><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p class="text-[11px] text-emerald-400 font-medium">School Configuration</p>
                </div>
            </div>

            <!-- Desktop Admin Navigation Links -->
            <div class="hidden lg:flex items-center gap-2">
                <a href="admin_dashboard.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Overview</a>
                <a href="pupils.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Pupils</a>
                <a href="report_cards.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Report Cards</a>
                <a href="manage_users.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Users</a>
                <a href="teacher_subjects.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Subjects</a>
                <a href="school_settings.php" class="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg text-sm font-semibold transition">Settings</a>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded-lg text-sm font-semibold transition shadow-sm ml-2">Logout</a>
            </div>

            <!-- Mobile Hamburger Toggle -->
            <div class="flex items-center lg:hidden">
                <button id="admin-menu-btn" type="button" class="p-2 rounded-lg text-slate-200 hover:text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-400 transition" aria-label="Toggle Admin Menu" aria-expanded="false">
                    <svg id="admin-icon-open" class="w-6 h-6 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg id="admin-icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Admin Menu Drawer -->
        <div id="admin-menu" class="hidden lg:hidden border-t border-slate-800 bg-slate-900/95 backdrop-blur px-4 pt-3 pb-5 space-y-1.5">
            <a href="admin_dashboard.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Overview Dashboard</a>
            <a href="pupils.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Pupil Records</a>
            <a href="report_cards.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Report Cards</a>
            <a href="manage_users.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">User Management</a>
            <a href="teacher_subjects.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Subject Scheduling</a>
            <a href="school_settings.php" class="block px-3 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-emerald-400">School Settings</a>
            <div class="pt-3 border-t border-slate-800">
                <a href="logout.php" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold shadow">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex-grow w-full space-y-6">
        <?php if ($alert['message']): ?>
            <div class="rounded-xl border px-4 py-3 text-sm flex items-center space-x-2 <?php
                if ($alert['type'] === 'success') {
                    echo 'bg-emerald-50 border-emerald-200 text-emerald-800';
                } elseif ($alert['type'] === 'info') {
                    echo 'bg-blue-50 border-blue-200 text-blue-800';
                } else {
                    echo 'bg-red-50 border-red-200 text-red-700';
                }
            ?>">
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- School Information Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-bold mb-1 text-slate-900">School Information</h2>
            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">Update basic school information displayed on public pages and student reports.</p>

            <form method="POST" class="space-y-4 sm:space-y-5">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">School Name</label>
                    <input type="text" name="school_name" value="<?php echo htmlspecialchars($settings['school_name'] ?? 'Buyunda Primary School'); ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">School Email</label>
                        <input type="email" name="school_email" value="<?php echo htmlspecialchars($settings['school_email'] ?? 'admin@buyundaprimaryschool.org'); ?>" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Contact Phone</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="+256 700 000 000">
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Physical Address</label>
                    <textarea name="address" rows="3" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. Buyunda, Uganda"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="save_settings" class="w-full sm:w-auto bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Email & Notification Configuration Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Email &amp; Notification System</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1">Status of automated email alerts for teacher sign-ins, pupil enrollments, and website inquiries.</p>
                </div>
                <div>
                    <?php if ($isConfigured): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live SMTP Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Demo / Log Mode (.env needs credentials)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuration Summary Table -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 text-xs sm:text-sm space-y-2">
                <div class="flex justify-between items-center py-1 border-b border-slate-200">
                    <span class="text-slate-500 font-medium">Recipient Admin Email:</span>
                    <span class="text-slate-900 font-bold"><?php echo htmlspecialchars($adminNotificationEmail); ?></span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200">
                    <span class="text-slate-500 font-medium">SMTP Server Host:</span>
                    <span class="text-slate-800 font-mono text-xs"><?php echo htmlspecialchars($smtpHost . ':' . $smtpPort); ?></span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500 font-medium">SMTP Authenticated User:</span>
                    <span class="text-slate-800 font-mono text-xs"><?php echo htmlspecialchars($smtpUser); ?></span>
                </div>
            </div>

            <!-- Active Notification Triggers List -->
            <div class="mb-6">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5">Automated School Triggers:</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">👩‍🏫 Teacher Sign-In</div>
                        <p class="text-[11px] text-slate-600">Dispatches instant alert to admin with educator name and clock-in time.</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">🎓 Pupil Enrollment</div>
                        <p class="text-[11px] text-slate-600">Sends confirmation to parent and registration alert to admin.</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">📊 Report Cards</div>
                        <p class="text-[11px] text-slate-600">Delivers pupil grades, evaluation table, and remarks to parent email.</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">🔑 User Provisioning</div>
                        <p class="text-[11px] text-slate-600">Emails login credentials and portal access link to new staff members.</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">📚 Subject Assignment</div>
                        <p class="text-[11px] text-slate-600">Alerts teachers immediately when assigned a new curriculum subject.</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <div class="font-bold text-slate-800 text-xs sm:text-sm mb-1">✉️ Contact Inquiries</div>
                        <p class="text-[11px] text-slate-600">Forwards visitor and parent messages with direct one-click reply-to.</p>
                    </div>
                </div>
            </div>

            <!-- Diagnostic Test Action Form -->
            <form method="POST" class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Send a test diagnostic message to <strong><?php echo htmlspecialchars($adminNotificationEmail); ?></strong> to verify SMTP connectivity.
                </p>
                <button type="submit" name="test_email" class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-semibold transition text-xs sm:text-sm shadow-sm flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Send Test Email to Admin
                </button>
            </form>
        </div>

    <!-- Mobile Admin Navigation Toggle Script -->
    <script>
        (function() {
            const menuBtn = document.getElementById('admin-menu-btn');
            const adminMenu = document.getElementById('admin-menu');
            const iconOpen = document.getElementById('admin-icon-open');
            const iconClose = document.getElementById('admin-icon-close');

            if (menuBtn && adminMenu) {
                menuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isExpanded = menuBtn.getAttribute('aria-expanded') === 'true';
                    menuBtn.setAttribute('aria-expanded', !isExpanded);
                    adminMenu.classList.toggle('hidden');
                    iconOpen.classList.toggle('hidden');
                    iconClose.classList.toggle('hidden');
                });

                document.addEventListener('click', function(e) {
                    if (!adminMenu.contains(e.target) && !menuBtn.contains(e.target) && !adminMenu.classList.contains('hidden')) {
                        adminMenu.classList.add('hidden');
                        menuBtn.setAttribute('aria-expanded', 'false');
                        iconOpen.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    }
                });

                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 1024 && !adminMenu.classList.contains('hidden')) {
                        adminMenu.classList.add('hidden');
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
