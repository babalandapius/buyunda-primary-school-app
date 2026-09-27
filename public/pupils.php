<?php
/**
 * Pupil record management page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('ADMIN');

$schoolName = getSchoolName();
$alert = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pupil'])) {
    $regNumber = trim((string) ($_POST['reg_number'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $className = trim((string) ($_POST['class_name'] ?? ''));
    $parentEmail = strtolower(trim((string) ($_POST['parent_email'] ?? '')));

    if ($regNumber === '' || $fullName === '' || $className === '' || $parentEmail === '') {
        $alert = ['type' => 'error', 'message' => 'All pupil fields are required.'];
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO pupils (reg_number, full_name, class_name, parent_email, created_at)
                 VALUES (:reg_number, :full_name, :class_name, :parent_email, NOW())
                 ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), class_name = VALUES(class_name), parent_email = VALUES(parent_email)'
            );
            $stmt->execute([
                ':reg_number' => $regNumber,
                ':full_name' => $fullName,
                ':class_name' => $className,
                ':parent_email' => $parentEmail,
            ]);

            // Send notification to school administration & parent
            sendNewPupilEnrollmentNotification($fullName, $regNumber, $className, $parentEmail);
            sendPupilEnrollmentConfirmation($fullName, $regNumber, $className, $parentEmail);

            $alert = ['type' => 'success', 'message' => 'Pupil record saved and enrollment confirmation email sent to parent (' . htmlspecialchars($parentEmail) . ').'];
        } catch (Throwable $e) {
            error_log('Pupil save failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Unable to save pupil record.'];
        }
    }
}

if (isset($_GET['delete'])) {
    $pupilId = (int) $_GET['delete'];
    if ($pupilId > 0) {
        try {
            $stmt = db()->prepare('DELETE FROM pupils WHERE id = :id');
            $stmt->execute([':id' => $pupilId]);
            $alert = ['type' => 'success', 'message' => 'Pupil record removed successfully.'];
        } catch (Throwable $e) {
            error_log('Pupil delete failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Unable to remove pupil.'];
        }
    }
}

$pupils = getPupils();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pupil Records | <?php echo htmlspecialchars($schoolName); ?></title>
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
                    <p class="text-[11px] text-emerald-400 font-medium">Pupil Management</p>
                </div>
            </div>

            <!-- Desktop Admin Navigation Links -->
            <div class="hidden lg:flex items-center gap-2">
                <a href="admin_dashboard.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Overview</a>
                <a href="pupils.php" class="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg text-sm font-semibold transition">Pupils</a>
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
            <a href="admin_dashboard.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Overview Dashboard</a>
            <a href="pupils.php" class="block px-3 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-emerald-400">Pupil Records</a>
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
                echo $alert['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-700';
            ?>">
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            <!-- Add / Update Pupil Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold mb-4 text-slate-900">Add / Update Pupil</h2>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Registration Number</label>
                            <input type="text" name="reg_number" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. BPS/2026/012">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                            <input type="text" name="full_name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. David Mukasa">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Class</label>
                            <input type="text" name="class_name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. Primary 6">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Parent Email</label>
                            <input type="email" name="parent_email" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="parent@example.com">
                        </div>
                    </div>

                    <button type="submit" name="save_pupil" class="w-full sm:w-auto bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                        Save Pupil Record
                    </button>
                </form>
            </div>

            <!-- Current Pupil List Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Current Pupil List</h2>
                    <span class="text-xs text-slate-500"><?php echo count($pupils); ?> registered</span>
                </div>
                <div class="overflow-x-auto flex-grow">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Reg No</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Name</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Class</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (empty($pupils)): ?>
                                <tr>
                                    <td colspan="4" class="px-3.5 py-6 text-xs sm:text-sm text-slate-500 text-center">No pupils available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pupils as $pupil): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-3.5 py-3 text-xs sm:text-sm font-semibold text-emerald-800"><?php echo htmlspecialchars($pupil['reg_number']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm font-medium text-slate-800"><?php echo htmlspecialchars($pupil['full_name']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm text-slate-600"><?php echo htmlspecialchars($pupil['class_name']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm text-right">
                                            <a href="pupils.php?delete=<?php echo (int) $pupil['id']; ?>" onclick="return confirm('Delete this pupil?')" class="inline-block bg-red-50 hover:bg-red-100 text-red-700 px-2.5 py-1 rounded-lg font-semibold transition">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
