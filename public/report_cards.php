<?php
/**
 * Report card management page.
 * Allows admins to add marks for pupils and view a print-ready summary.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

requireRole('ADMIN');

$schoolName = getSchoolName();
$alert = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_report'])) {
    $pupilId = (int) ($_POST['pupil_id'] ?? 0);
    $term = trim((string) ($_POST['term'] ?? ''));
    $academicYear = trim((string) ($_POST['academic_year'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $marks = (float) ($_POST['marks'] ?? 0);
    $comments = trim((string) ($_POST['comments'] ?? ''));
    $notifyParent = isset($_POST['notify_parent']);

    if ($pupilId <= 0 || $term === '' || $academicYear === '' || $subject === '') {
        $alert = ['type' => 'error', 'message' => 'Please complete all required fields.'];
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO report_cards (pupil_id, term, academic_year, subject, marks, comments, created_at)
                 VALUES (:pupil_id, :term, :academic_year, :subject, :marks, :comments, NOW())'
            );
            $stmt->execute([
                ':pupil_id' => $pupilId,
                ':term' => $term,
                ':academic_year' => $academicYear,
                ':subject' => $subject,
                ':marks' => $marks,
                ':comments' => $comments,
            ]);

            $successMsg = 'Report card entry saved successfully.';

            // If notify parent checkbox was checked, dispatch email with updated grade summary
            if ($notifyParent) {
                $pupilStmt = db()->prepare('SELECT * FROM pupils WHERE id = :id LIMIT 1');
                $pupilStmt->execute([':id' => $pupilId]);
                $pupilData = $pupilStmt->fetch(PDO::FETCH_ASSOC);

                if ($pupilData && !empty($pupilData['parent_email'])) {
                    $rowsStmt = db()->prepare('SELECT * FROM report_cards WHERE pupil_id = :pupil_id AND term = :term AND academic_year = :year ORDER BY subject ASC');
                    $rowsStmt->execute([':pupil_id' => $pupilId, ':term' => $term, ':year' => $academicYear]);
                    $pupilReportRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                    sendParentReportCardNotification($pupilData, $term, $academicYear, $pupilReportRows, $comments);
                    $successMsg .= ' Parent has been notified via email (' . htmlspecialchars($pupilData['parent_email']) . ').';
                }
            }

            $alert = ['type' => 'success', 'message' => $successMsg];
        } catch (Throwable $e) {
            error_log('Report card insert failed: ' . $e->getMessage());
            $alert = ['type' => 'error', 'message' => 'Could not save the report card entry.'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_report_card'])) {
    $pupilId = (int) ($_POST['pupil_id'] ?? 0);
    $term = trim((string) ($_POST['term'] ?? 'TERM_1'));
    $academicYear = trim((string) ($_POST['academic_year'] ?? date('Y')));
    $remarks = trim((string) ($_POST['headteacher_remarks'] ?? ''));

    if ($pupilId > 0) {
        $pupilStmt = db()->prepare('SELECT * FROM pupils WHERE id = :id LIMIT 1');
        $pupilStmt->execute([':id' => $pupilId]);
        $targetPupil = $pupilStmt->fetch(PDO::FETCH_ASSOC);

        if ($targetPupil && !empty($targetPupil['parent_email'])) {
            $rowsStmt = db()->prepare('SELECT * FROM report_cards WHERE pupil_id = :pupil_id ORDER BY academic_year DESC, FIELD(term, "TERM_1", "TERM_2", "TERM_3"), subject ASC');
            $rowsStmt->execute([':pupil_id' => $pupilId]);
            $targetReportRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($targetReportRows)) {
                $emailResult = sendParentReportCardNotification($targetPupil, $term, $academicYear, $targetReportRows, $remarks);
                if ($emailResult['success']) {
                    $alert = ['type' => 'success', 'message' => 'Official report card summary emailed successfully to ' . htmlspecialchars($targetPupil['parent_email']) . '.'];
                } else {
                    $alert = ['type' => 'error', 'message' => 'Failed to dispatch email: ' . htmlspecialchars($emailResult['message'])];
                }
            } else {
                $alert = ['type' => 'error', 'message' => 'No recorded subject marks to send for this pupil yet.'];
            }
        } else {
            $alert = ['type' => 'error', 'message' => 'Selected pupil does not have a valid parent email address.'];
        }
    }
}

$pupils = getPupils();
$selectedPupilId = isset($_GET['pupil_id']) ? (int) $_GET['pupil_id'] : (int) ($pupils[0]['id'] ?? 0);

$reportRows = [];
if ($selectedPupilId > 0) {
    try {
        $stmt = db()->prepare(
            'SELECT * FROM report_cards WHERE pupil_id = :pupil_id ORDER BY academic_year DESC, FIELD(term, "TERM_1", "TERM_2", "TERM_3"), subject ASC'
        );
        $stmt->execute([':pupil_id' => $selectedPupilId]);
        $reportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Report card fetch failed: ' . $e->getMessage());
    }
}

$selectedPupil = null;
foreach ($pupils as $pupil) {
    if ((int) $pupil['id'] === $selectedPupilId) {
        $selectedPupil = $pupil;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards | <?php echo htmlspecialchars($schoolName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            nav, button, form, .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            main {
                padding: 0 !important;
                max-width: 100% !important;
            }
            .print-full-width {
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
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
                    <p class="text-[11px] text-emerald-400 font-medium">Academic Report Cards</p>
                </div>
            </div>

            <!-- Desktop Admin Navigation Links -->
            <div class="hidden lg:flex items-center gap-2">
                <a href="admin_dashboard.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Overview</a>
                <a href="pupils.php" class="hover:bg-slate-800 text-slate-200 px-3 py-2 rounded-lg text-sm font-semibold transition">Pupils</a>
                <a href="report_cards.php" class="bg-slate-800 text-emerald-400 px-3 py-2 rounded-lg text-sm font-semibold transition">Report Cards</a>
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
            <a href="pupils.php" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">Pupil Records</a>
            <a href="report_cards.php" class="block px-3 py-2.5 rounded-lg text-sm font-semibold bg-slate-800 text-emerald-400">Report Cards</a>
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
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm flex items-center space-x-2 no-print <?php
                echo $alert['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-700';
            ?>">
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            <!-- Add Performance Record Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 no-print">
                <h2 class="text-xl sm:text-2xl font-bold mb-4 text-slate-900">Add Performance Record</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Select Pupil</label>
                        <select name="pupil_id" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition bg-white">
                            <option value="">Choose a pupil</option>
                            <?php foreach ($pupils as $pupil): ?>
                                <option value="<?php echo (int) $pupil['id']; ?>" <?php echo ((int) $pupil['id'] === $selectedPupilId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pupil['full_name']); ?> (<?php echo htmlspecialchars($pupil['reg_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Term</label>
                            <select name="term" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition bg-white">
                                <option value="TERM_1">Term 1</option>
                                <option value="TERM_2">Term 2</option>
                                <option value="TERM_3">Term 3</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Academic Year</label>
                            <input type="text" name="academic_year" value="2026" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Subject</label>
                        <input type="text" name="subject" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="e.g. Mathematics">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Marks (0 - 100)</label>
                            <input type="number" step="0.01" min="0" max="100" name="marks" required class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="85">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Comments</label>
                            <input type="text" name="comments" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition" placeholder="Excellent performance">
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-1">
                        <input type="checkbox" id="notify_parent" name="notify_parent" value="1" checked class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <label for="notify_parent" class="text-xs sm:text-sm text-slate-700 font-medium">Send automatic grade update email to parent</label>
                    </div>

                    <button type="submit" name="save_report" class="w-full sm:w-auto bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                        Save Report Entry
                    </button>
                </form>
            </div>

            <!-- Print-ready Report View Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 print-full-width">
                <h2 class="text-xl sm:text-2xl font-bold mb-4 text-slate-900 no-print">Report Card View</h2>

                <form method="GET" class="mb-5 no-print">
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Filter by Pupil</label>
                    <select name="pupil_id" onchange="this.form.submit()" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition bg-white">
                        <option value="">Choose a pupil</option>
                        <?php foreach ($pupils as $pupil): ?>
                            <option value="<?php echo (int) $pupil['id']; ?>" <?php echo ((int) $pupil['id'] === $selectedPupilId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pupil['full_name']); ?> (<?php echo htmlspecialchars($pupil['reg_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if ($selectedPupil): ?>
                    <div class="border border-slate-200 rounded-xl p-4 sm:p-5 bg-slate-50 print-full-width">
                        <div class="flex flex-col sm:flex-row justify-between items-start mb-4 gap-2 border-b border-slate-200 pb-3">
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-slate-900"><?php echo htmlspecialchars($selectedPupil['full_name']); ?></h3>
                                <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5">Reg No: <?php echo htmlspecialchars($selectedPupil['reg_number']); ?></p>
                            </div>
                            <div class="text-left sm:text-right text-xs sm:text-sm text-slate-600">
                                <div><span class="font-semibold text-slate-700">Class:</span> <?php echo htmlspecialchars($selectedPupil['class_name']); ?></div>
                                <div class="break-all"><span class="font-semibold text-slate-700">Parent:</span> <?php echo htmlspecialchars($selectedPupil['parent_email']); ?></div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-left">
                                <thead class="bg-slate-200">
                                    <tr>
                                        <th class="px-3 py-2 text-xs uppercase font-semibold text-slate-700">Term</th>
                                        <th class="px-3 py-2 text-xs uppercase font-semibold text-slate-700">Year</th>
                                        <th class="px-3 py-2 text-xs uppercase font-semibold text-slate-700">Subject</th>
                                        <th class="px-3 py-2 text-xs uppercase font-semibold text-slate-700">Marks</th>
                                        <th class="px-3 py-2 text-xs uppercase font-semibold text-slate-700">Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <?php if (empty($reportRows)): ?>
                                        <tr>
                                            <td colspan="5" class="px-3 py-4 text-xs sm:text-sm text-slate-500 text-center">No report card entries for this pupil yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reportRows as $row): ?>
                                            <tr class="hover:bg-slate-50 transition">
                                                <td class="px-3 py-2.5 text-xs sm:text-sm text-slate-700 whitespace-nowrap"><?php echo htmlspecialchars(str_replace('_', ' ', $row['term'])); ?></td>
                                                <td class="px-3 py-2.5 text-xs sm:text-sm text-slate-700 whitespace-nowrap"><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                <td class="px-3 py-2.5 text-xs sm:text-sm font-medium text-slate-800"><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td class="px-3 py-2.5 text-xs sm:text-sm font-bold text-emerald-700"><?php echo htmlspecialchars((string) $row['marks']); ?></td>
                                                <td class="px-3 py-2.5 text-xs sm:text-sm text-slate-600"><?php echo htmlspecialchars($row['comments'] ?: '—'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Email & Print Action Bar -->
                        <div class="mt-5 pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 no-print">
                            <form method="POST" class="flex-grow flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <input type="hidden" name="pupil_id" value="<?php echo (int) $selectedPupil['id']; ?>">
                                <input type="hidden" name="term" value="<?php echo htmlspecialchars($reportRows[0]['term'] ?? 'TERM_1'); ?>">
                                <input type="hidden" name="academic_year" value="<?php echo htmlspecialchars($reportRows[0]['academic_year'] ?? '2026'); ?>">
                                <input type="text" name="headteacher_remarks" placeholder="Optional Headteacher remark..." class="border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none flex-grow">
                                <button type="submit" name="email_report_card" class="bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2.5 rounded-xl font-semibold transition text-xs sm:text-sm shadow-sm inline-flex items-center justify-center space-x-1.5 whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span>Email to Parent</span>
                                </button>
                            </form>

                            <button onclick="window.print();" class="bg-slate-900 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-slate-700 transition text-xs sm:text-sm shadow-sm inline-flex items-center justify-center space-x-1.5 whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                <span>Print / PDF</span>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500 italic">Please choose a pupil above to preview their report card.</p>
                <?php endif; ?>
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
