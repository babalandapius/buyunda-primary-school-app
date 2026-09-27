<?php
/**
 * Teacher subject assignment page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('ADMIN');

$schoolName = getSchoolName();
$alert = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subject'])) {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $subjectName = trim((string) ($_POST['subject_name'] ?? ''));

    if ($teacherId <= 0 || $subjectName === '') {
        $alert = ['type' => 'error', 'message' => 'Teacher and subject are required.'];
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO teacher_subjects (teacher_id, subject_name, created_at) VALUES (:teacher_id, :subject_name, NOW())'
            );
            $stmt->execute([
                ':teacher_id' => $teacherId,
                ':subject_name' => $subjectName,
            ]);

            // Fetch teacher info to send notification
            $teacherStmt = db()->prepare('SELECT full_name, email FROM users WHERE id = :id LIMIT 1');
            $teacherStmt->execute([':id' => $teacherId]);
            $teacherData = $teacherStmt->fetch(PDO::FETCH_ASSOC);

            if ($teacherData && !empty($teacherData['email'])) {
                sendTeacherSubjectAssignmentEmail($teacherData['full_name'], $teacherData['email'], $subjectName);
            }

            $alert = ['type' => 'success', 'message' => "Subject \"{$subjectName}\" assigned successfully and notification sent to teacher."];
        } catch (Throwable $e) {
            error_log('Teacher subject assignment failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Unable to assign the selected subject.'];
        }
    }
}

try {
    $teachers = db()->query("SELECT * FROM users WHERE role = 'TEACHER' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $assignments = db()->query(
        'SELECT ts.id, u.full_name AS teacher_name, ts.subject_name FROM teacher_subjects ts INNER JOIN users u ON u.id = ts.teacher_id ORDER BY u.full_name ASC, ts.subject_name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Teacher subject query failed: ' . $e->getMessage());
    $teachers = [];
    $assignments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Subjects | <?php echo htmlspecialchars($schoolName); ?></title>
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
                    <p class="text-[11px] text-emerald-400 font-medium">Subject Scheduling</p>
                </div>
            </div>

            <!-- Desktop Admin Navigation Links -->
            <div class="hidden lg:flex items-center gap-2">
                <a href="admin_dashboard.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Overview</a>
                <a href="pupils.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Pupils</a>
                <a href="report_cards.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Report Cards</a>
                <a href="manage_users.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Users</a>
                <a href="teacher_subjects.php" class="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg text-sm font-semibold transition">Subjects</a>
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
            <a href="pupils.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Pupil Records</a>
            <a href="report_cards.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Report Cards</a>
            <a href="manage_users.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">User Management</a>
            <a href="teacher_subjects.php" class="block px-3 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-emerald-400">Subject Scheduling</a>
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
            <!-- Assign Subject Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
                <h2 class="text-xl sm:text-2xl font-bold mb-4 text-slate-900">Assign Subject</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Teacher</label>
                        <select name="teacher_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition bg-white">
                            <option value="">Select a teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo (int) $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Subject Name</label>
                        <input type="text" name="subject_name" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. Mathematics, English, Science">
                    </div>
                    <button type="submit" name="assign_subject" class="w-full sm:w-auto bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                        Assign Subject
                    </button>
                </form>
            </div>

            <!-- Assigned Subjects List Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Assigned Subjects</h2>
                    <span class="text-xs text-slate-500"><?php echo count($assignments); ?> assignments</span>
                </div>
                <div class="overflow-x-auto flex-grow">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Teacher</th>
                                <th class="px-3.5 py-2.5 text-xs font-semibold uppercase text-slate-600">Subject</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="2" class="px-3.5 py-6 text-xs sm:text-sm text-slate-500 text-center">No assignments recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-3.5 py-3 text-xs sm:text-sm font-medium text-slate-800"><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td class="px-3.5 py-3 text-xs sm:text-sm text-emerald-800 font-semibold"><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
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
