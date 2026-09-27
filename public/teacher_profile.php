<?php
/**
 * Teacher profile page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('TEACHER');

$user = currentUser();
$schoolName = getSchoolName();

$teacherInfo = [
    'id' => (int) ($user['id'] ?? 0),
    'full_name' => $user['full_name'] ?? '',
    'email' => $user['email'] ?? '',
    'role' => $user['role'] ?? 'TEACHER',
];

try {
    $subjects = db()->prepare('SELECT subject_name FROM teacher_subjects WHERE teacher_id = :teacher_id ORDER BY subject_name ASC');
    $subjects->execute([':teacher_id' => $teacherInfo['id']]);
    $teacherSubjects = $subjects->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('Teacher subject query failed: ' . $e->getMessage());
    $teacherSubjects = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile | <?php echo htmlspecialchars($schoolName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased flex flex-col">

    <!-- Navigation Header -->
    <nav class="bg-emerald-800 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3.5 flex justify-between items-center flex-wrap gap-3">
            <div class="flex items-center space-x-3 min-w-0">
                <a href="index.php" class="w-9 h-9 bg-white text-emerald-800 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0 shadow">B</a>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold truncate"><?php echo htmlspecialchars($schoolName); ?></h1>
                    <p class="text-[11px] text-emerald-200">Teacher Profile</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-3">
                <a href="teacher_dashboard.php" class="bg-white text-emerald-800 hover:bg-emerald-50 px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition shadow-sm">Dashboard</a>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition shadow-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex-grow w-full">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-8">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-8 text-center sm:text-left">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl sm:text-3xl font-extrabold shadow-sm flex-shrink-0">
                    <?php echo strtoupper(substr($teacherInfo['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($teacherInfo['full_name']); ?></h2>
                    <span class="inline-block mt-1 bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs px-2.5 py-0.5 rounded-full font-semibold">
                        <?php echo htmlspecialchars($teacherInfo['role']); ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div class="bg-slate-50 p-4 sm:p-5 rounded-xl border border-slate-200">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Email Address</p>
                    <p class="text-sm sm:text-base font-medium text-slate-900 mt-1.5 break-all"><?php echo htmlspecialchars($teacherInfo['email']); ?></p>
                </div>
                <div class="bg-slate-50 p-4 sm:p-5 rounded-xl border border-slate-200">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">Assigned Subjects</p>
                    <p class="text-sm sm:text-base font-medium text-emerald-800 mt-1.5"><?php echo empty($teacherSubjects) ? '<span class="text-slate-400 italic font-normal">No subjects assigned yet</span>' : htmlspecialchars(implode(', ', $teacherSubjects)); ?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
