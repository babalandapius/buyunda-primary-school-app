<?php
/**
 * Login page for Admins and Teachers.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isLoggedIn()) {
    $role = $_SESSION['user']['role'];
    redirect($role === 'ADMIN' ? 'admin_dashboard.php' : 'teacher_dashboard.php');
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = loginUser($email, $password);

    if ($user) {
        $redirect = $user['role'] === 'ADMIN' ? 'admin_dashboard.php' : 'teacher_dashboard.php';
        redirect($redirect);
    }

    $loginError = 'Invalid email or password. Please try again.';
}

$schoolName = getSchoolName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | <?php echo htmlspecialchars($schoolName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased">
    <div class="w-full max-w-md my-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-800 to-teal-800 px-5 sm:px-8 py-5 sm:py-6 text-white text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start space-x-3 mb-2">
                    <div class="w-8 h-8 bg-white text-emerald-800 rounded-full flex items-center justify-center font-bold text-base shadow">B</div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight"><?php echo htmlspecialchars($schoolName); ?></h1>
                </div>
                <p class="text-xs sm:text-sm text-emerald-100">Staff & Management Access Portal</p>
            </div>

            <div class="p-5 sm:p-8">
                <?php if ($loginError): ?>
                    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span><?php echo htmlspecialchars($loginError); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition"
                            placeholder="admin@buyundaprimaryschool.org"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition"
                            placeholder="••••••••"
                        >
                    </div>

                    <button type="submit" name="login" class="w-full bg-emerald-700 text-white py-3 rounded-xl font-semibold hover:bg-emerald-800 transition text-sm sm:text-base shadow-sm">
                        Sign In to Portal
                    </button>
                </form>

                <div class="mt-6 text-center text-xs sm:text-sm text-slate-500 border-t border-slate-100 pt-5">
                    <a href="index.php" class="text-emerald-700 hover:text-emerald-800 font-medium inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>