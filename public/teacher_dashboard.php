<?php
/**
 * Teacher Dashboard & Attendance Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('TEACHER');

$user = currentUser();
$schoolName = getSchoolName();

$message = '';
$error = '';
$todayDate = date('Y-m-d');

// Check if teacher has already signed attendance today
$stmt = db()->prepare('SELECT * FROM attendance WHERE teacher_id = :teacher_id AND date = :date LIMIT 1');
$stmt->execute([':teacher_id' => $user['id'], ':date' => $todayDate]);
$todayRecord = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_attendance'])) {
    if ($todayRecord) {
        $error = 'You have already signed attendance for today (' . $todayDate . ').';
    } else {
        $signInTime = date('H:i:s');
        try {
            $insert = db()->prepare('INSERT INTO attendance (teacher_id, date, sign_in_time, status) VALUES (:teacher_id, :date, :time, "PRESENT")');
            $insert->execute([
                ':teacher_id' => $user['id'],
                ':date'       => $todayDate,
                ':time'       => $signInTime
            ]);

            // Dispatch instant email notification to school admin
            sendTeacherAttendanceNotification($user['full_name'], $todayDate, $signInTime);

            $message = 'Attendance signed successfully for today!';
            
            // Refresh record state
            $todayRecord = ['date' => $todayDate, 'sign_in_time' => $signInTime, 'status' => 'PRESENT'];
        } catch (Throwable $e) {
            error_log('Attendance signing failed: ' . $e->getMessage());
            $error = 'Failed to record attendance. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?= htmlspecialchars($schoolName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col antialiased">

    <!-- Teacher Navigation Header -->
    <nav class="bg-emerald-800 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3.5 flex justify-between items-center flex-wrap gap-3">
            <div class="flex items-center space-x-3 min-w-0">
                <a href="index.php" class="w-9 h-9 bg-white text-emerald-800 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0 shadow">B</a>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg font-bold truncate"><?= htmlspecialchars($schoolName) ?></h1>
                    <p class="text-[11px] text-emerald-200">Teacher Portal</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                <a href="teacher_profile.php" class="bg-emerald-700 hover:bg-emerald-600 text-emerald-100 hover:text-white px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition inline-flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="truncate max-w-[120px] sm:max-w-[180px]"><?= htmlspecialchars($user['full_name']) ?></span>
                </a>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm px-3 py-1.5 rounded-lg font-medium transition shadow-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex-grow w-full">

        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-xl text-xs sm:text-sm flex items-center space-x-2">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-xl text-xs sm:text-sm flex items-center space-x-2">
                <svg class="w-5 h-5 flex-shrink-0 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Daily Attendance Sign-In Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-8 mb-6 sm:mb-8">
            <h2 class="text-lg sm:text-2xl font-bold text-slate-900 mb-2">Daily Attendance Sign-In</h2>
            <p class="text-slate-600 text-xs sm:text-sm mb-6 leading-relaxed">
                Record your daily presence for today (<strong><?= date('F j, Y') ?></strong>). An instant notification will be dispatched to school administration upon submission.
            </p>

            <?php if ($todayRecord): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <p class="text-emerald-900 font-bold text-sm sm:text-base">Attendance Recorded for Today</p>
                        <p class="text-emerald-700 text-xs sm:text-sm mt-0.5">Signed in at <?= htmlspecialchars($todayRecord['sign_in_time']) ?></p>
                    </div>
                    <span class="bg-emerald-600 text-white text-xs px-3.5 py-1.5 rounded-full font-bold uppercase tracking-wider">PRESENT</span>
                </div>
            <?php else: ?>
                <form action="teacher_dashboard.php" method="POST">
                    <button type="submit" name="sign_attendance" value="1"
                            class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-semibold transition text-sm sm:text-base shadow-sm">
                        Sign Attendance for Today
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Quick Links Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-8">
            <h2 class="text-base sm:text-xl font-bold text-slate-900 mb-4">Quick Links</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <a href="pupils.php" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 p-4 rounded-xl text-sm font-medium transition flex items-center space-x-3">
                    <div class="w-9 h-9 bg-emerald-100 text-emerald-700 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">Pupils Roster</p>
                        <p class="text-xs text-slate-500">View enrolled student records</p>
                    </div>
                </a>
                <a href="report_cards.php" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 p-4 rounded-xl text-sm font-medium transition flex items-center space-x-3">
                    <div class="w-9 h-9 bg-emerald-100 text-emerald-700 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900">Academic Reports</p>
                        <p class="text-xs text-slate-500">Enter marks and view reports</p>
                    </div>
                </a>
            </div>
        </div>

    </main>

</body>
</html>