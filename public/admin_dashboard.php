<?php
/**
 * Admin dashboard for attendance review, overview, and pupil management.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('ADMIN');

$schoolName = getSchoolName();
$alert = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pupil'])) {
    $regNumber = trim((string) ($_POST['reg_number'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $className = trim((string) ($_POST['class_name'] ?? ''));
    $parentEmail = trim((string) ($_POST['parent_email'] ?? ''));

    if ($regNumber === '' || $fullName === '' || $className === '' || $parentEmail === '') {
        $alert = ['type' => 'error', 'message' => 'Please fill in all pupil fields.'];
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO pupils (reg_number, full_name, class_name, parent_email, created_at) VALUES (:reg_number, :full_name, :class_name, :parent_email, NOW())'
            );
            $stmt->execute([
                ':reg_number' => $regNumber,
                ':full_name' => $fullName,
                ':class_name' => $className,
                ':parent_email' => $parentEmail,
            ]);

            // Dispatch notification to administrator and confirmation to parent
            sendNewPupilEnrollmentNotification($fullName, $regNumber, $className, $parentEmail);
            sendPupilEnrollmentConfirmation($fullName, $regNumber, $className, $parentEmail);

            $alert = ['type' => 'success', 'message' => 'Pupil record added and enrollment confirmation email dispatched.'];
        } catch (Throwable $e) {
            error_log('Add pupil failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Could not add the pupil. Check if the registration number already exists.'];
        }
    }
}

$overviewStats = [
    'totalPupils' => totalPupils(),
    'totalTeachers' => totalTeachers(),
    'totalNotifications' => totalNotificationsSent(),
    'recentAttendance' => getRecentAttendance(6),
    'recentNotifications' => getRecentNotifications(6),
    'pupils' => getPupils(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo htmlspecialchars($schoolName); ?></title>
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
                    <p class="text-[11px] text-emerald-400 font-medium">Admin Control Panel</p>
                </div>
            </div>

            <!-- Desktop Admin Navigation Links -->
            <div class="hidden lg:flex items-center gap-2">
                <a href="admin_dashboard.php" class="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg text-sm font-semibold transition">Overview</a>
                <a href="pupils.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Pupils</a>
                <a href="report_cards.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Report Cards</a>
                <a href="manage_users.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Users</a>
                <a href="teacher_subjects.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Subjects</a>
                <a href="school_settings.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Settings</a>
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
            <a href="admin_dashboard.php" class="block px-3 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-emerald-400">Overview Dashboard</a>
            <a href="pupils.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Pupil Records</a>
            <a href="report_cards.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Report Cards</a>
            <a href="manage_users.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">User Management</a>
            <a href="teacher_subjects.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Subject Scheduling</a>
            <a href="school_settings.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">School Settings</a>
            <div class="pt-3 border-t border-slate-800">
                <a href="logout.php" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold shadow">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex-grow w-full">
        <?php if ($alert['message']): ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm flex items-center space-x-2 <?php
                echo $alert['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
                    ($alert['type'] === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-red-50 border-red-200 text-red-800');
            ?>">
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Total Pupils</p>
                    <div class="w-10 h-10 bg-emerald-50 text-emerald-700 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-3"><?php echo (int) $overviewStats['totalPupils']; ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Teachers</p>
                    <div class="w-10 h-10 bg-teal-50 text-teal-700 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-3"><?php echo (int) $overviewStats['totalTeachers']; ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Dispatched Alerts</p>
                    <div class="w-10 h-10 bg-blue-50 text-blue-700 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-3"><?php echo (int) $overviewStats['totalNotifications']; ?></h2>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col justify-between">
                <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Quick Actions</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="pupils.php" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold px-3 py-1.5 rounded-lg text-xs transition">Pupils</a>
                    <a href="report_cards.php" class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-3 py-1.5 rounded-lg text-xs transition shadow-sm">Reports</a>
                    <a href="school_settings.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-lg text-xs transition shadow-sm">Email Setup</a>
                </div>
            </div>
        </section>

        <!-- Forms and Sign-ins Grid -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8 sm:mt-10">
            <!-- Add Pupil Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Add New Pupil</h3>
                    <span class="text-xs text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full font-medium">Auto Email Alert</span>
                </div>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Registration Number</label>
                            <input type="text" name="reg_number" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. BPS/2026/001">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                            <input type="text" name="full_name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. John Doe">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Class Name</label>
                            <input type="text" name="class_name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. Primary 5">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Parent Email</label>
                            <input type="email" name="parent_email" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="parent@example.com">
                        </div>
                    </div>

                    <button type="submit" name="add_pupil" class="w-full sm:w-auto bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                        Save Pupil & Send Confirmation
                    </button>
                </form>
            </div>

            <!-- Recent Teacher Sign-ins -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Recent Teacher Sign-ins</h3>
                    <span class="text-xs text-slate-500">Live Attendance</span>
                </div>
                <div class="overflow-x-auto flex-grow">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Teacher</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Date</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (empty($overviewStats['recentAttendance'])): ?>
                                <tr>
                                    <td colspan="3" class="px-3.5 py-4 text-xs sm:text-sm text-slate-500 text-center">No sign-ins recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($overviewStats['recentAttendance'] as $entry): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-3.5 py-3 text-xs sm:text-sm font-medium text-slate-800"><?php echo htmlspecialchars($entry['teacher_name']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm text-slate-600"><?php echo htmlspecialchars($entry['date']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm text-emerald-700 font-medium"><?php echo htmlspecialchars($entry['sign_in_time']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Notification Activity & Audit Log Section -->
        <section class="mt-8 sm:mt-10 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Notification & Email Activity Log</h3>
                </div>
                <a href="school_settings.php" class="text-xs sm:text-sm text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1">
                    <span>Email & SMTP Diagnostics &rarr;</span>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Event / Type</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Subject</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Recipient</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($overviewStats['recentNotifications'])): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-xs sm:text-sm text-slate-500 text-center">No email notifications recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($overviewStats['recentNotifications'] as $log): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 text-xs sm:text-sm font-semibold text-slate-800">
                                        <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-mono">
                                            <?php echo htmlspecialchars($log['notification_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs sm:text-sm font-medium text-slate-800 max-w-xs truncate">
                                        <?php echo htmlspecialchars($log['subject']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs sm:text-sm text-slate-600">
                                        <?php echo htmlspecialchars($log['recipient_email']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs sm:text-sm">
                                        <?php if ($log['status'] === 'SENT'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Sent</span>
                                        <?php elseif ($log['status'] === 'LOGGED'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800" title="Recorded in sandbox / logging mode">Sandbox</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800" title="<?php echo htmlspecialchars($log['error_details'] ?? ''); ?>">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                        <?php echo htmlspecialchars(date('M j, g:i a', strtotime($log['created_at']))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Pupil Records List Section -->
        <section class="mt-8 sm:mt-10 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <h3 class="text-lg sm:text-xl font-bold text-slate-900">Pupil Records</h3>
                <span class="text-xs text-slate-500"><?php echo count($overviewStats['pupils']); ?> pupils registered</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Reg No.</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Full Name</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Class</th>
                            <th class="px-4 py-3 text-xs uppercase font-semibold text-slate-600">Parent Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($overviewStats['pupils'])): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-xs sm:text-sm text-slate-500 text-center">No pupils added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($overviewStats['pupils'] as $pupil): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 text-xs sm:text-sm font-semibold text-emerald-800"><?php echo htmlspecialchars($pupil['reg_number']); ?></td>
                                    <td class="px-4 py-3 text-xs sm:text-sm font-medium text-slate-800"><?php echo htmlspecialchars($pupil['full_name']); ?></td>
                                    <td class="px-4 py-3 text-xs sm:text-sm text-slate-600"><?php echo htmlspecialchars($pupil['class_name']); ?></td>
                                    <td class="px-4 py-3 text-xs sm:text-sm text-slate-600 break-all"><?php echo htmlspecialchars($pupil['parent_email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

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