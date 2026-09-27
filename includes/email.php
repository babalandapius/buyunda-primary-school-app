<?php
/**
 * Automated Email & Notification Engine for Buyunda Primary School.
 * Powered by PHPMailer with SMTP configuration support, fallback logger, and audit trail.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

// Check for Composer autoloader or manual PHPMailer installation
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
}

/**
 * Robust environment variable loader.
 */
function loadEnvironmentVariables(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        $loaded = true;
        return;
    }

    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->safeLoad();
            $loaded = true;
            return;
        } catch (Throwable $e) {
            error_log('Dotenv loader error: ' . $e->getMessage());
        }
    }

    // Fallback native .env parser
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $key = trim($k);
                $value = trim($v, " \t\n\r\0\x0B\"'");
                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    $loaded = true;
}

// Immediately load environment
loadEnvironmentVariables();

/**
 * Ensures the notification_logs table exists in the database.
 */
function ensureNotificationTableExists(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    try {
        db()->exec("
            CREATE TABLE IF NOT EXISTS notification_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                recipient_email VARCHAR(150) NOT NULL,
                recipient_name VARCHAR(150) NOT NULL DEFAULT '',
                notification_type VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                status ENUM('SENT','FAILED','LOGGED') NOT NULL DEFAULT 'LOGGED',
                error_details TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_notifications_type (notification_type),
                KEY idx_notifications_status (status),
                KEY idx_notifications_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $checked = true;
    } catch (Throwable $e) {
        error_log('Unable to auto-create notification_logs table: ' . $e->getMessage());
    }
}

/**
 * Records an entry into the notification audit log table.
 */
function logNotification(
    string $recipientEmail,
    string $recipientName,
    string $notificationType,
    string $subject,
    string $status,
    ?string $errorDetails = null
): void {
    ensureNotificationTableExists();

    try {
        $stmt = db()->prepare("
            INSERT INTO notification_logs (recipient_email, recipient_name, notification_type, subject, status, error_details, created_at)
            VALUES (:email, :name, :type, :subject, :status, :error, NOW())
        ");
        $stmt->execute([
            ':email'   => $recipientEmail,
            ':name'    => $recipientName,
            ':type'    => $notificationType,
            ':subject' => $subject,
            ':status'  => in_array($status, ['SENT', 'FAILED', 'LOGGED'], true) ? $status : 'LOGGED',
            ':error'   => $errorDetails,
        ]);
    } catch (Throwable $e) {
        error_log('Failed to write to notification_logs: ' . $e->getMessage());
    }
}

/**
 * Checks whether live SMTP credentials are configured (or in sandbox/placeholder mode).
 */
function isSmtpConfigured(): bool
{
    loadEnvironmentVariables();

    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';

    if ($pass === '' || $pass === 'your-16-digit-app-password' || $user === 'your-actual-email@gmail.com' || str_contains($user, 'placeholder')) {
        return false;
    }

    return true;
}

/**
 * Returns diagnostic metadata about the SMTP configuration.
 *
 * @return array{configured: bool, host: string, port: int, user: string, secure: string, from_name: string}
 */
function getSmtpStatusDetails(): array
{
    loadEnvironmentVariables();

    $configured = isSmtpConfigured();
    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $user = getenv('SMTP_USER') ?: 'admin@buyundaprimaryschool.org';
    $secure = (string) (getenv('SMTP_SECURE') ?: 'tls');
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Buyunda Primary School';

    return [
        'configured' => $configured,
        'host'       => $host,
        'port'       => $port,
        'user'       => $user,
        'secure'     => $secure,
        'from_name'  => $fromName,
    ];
}

/**
 * Resolves the destination email address for administrator notifications.
 */
function getAdminEmail(): string
{
    loadEnvironmentVariables();

    // 1. Check override in environment
    $envAdmin = getenv('ADMIN_NOTIFICATION_EMAIL');
    if (!empty($envAdmin) && filter_var($envAdmin, FILTER_VALIDATE_EMAIL)) {
        return $envAdmin;
    }

    // 2. Query database for Admin user email
    try {
        $stmt = db()->query("SELECT email FROM users WHERE role = 'ADMIN' ORDER BY id ASC LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            return $user['email'];
        }

        // 3. Query school settings email
        $stmt = db()->query("SELECT school_email FROM school_settings ORDER BY id ASC LIMIT 1");
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($setting && !empty($setting['school_email']) && filter_var($setting['school_email'], FILTER_VALIDATE_EMAIL)) {
            return $setting['school_email'];
        }
    } catch (Throwable $e) {
        error_log('Unable to query admin email from database: ' . $e->getMessage());
    }

    // 4. Default fallback
    return 'admin@buyundaprimaryschool.org';
}

/**
 * Universal email dispatch helper via PHPMailer SMTP.
 * Automatically records all dispatches into the notification_logs table.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody,
    ?string $replyToEmail = null,
    ?string $replyToName = null,
    string $notificationType = 'GENERAL'
): array {
    loadEnvironmentVariables();

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $errorMsg = 'PHPMailer library is not available. Please ensure vendor dependencies are installed.';
        error_log($errorMsg);
        logNotification($toEmail, $toName, $notificationType, $subject, 'FAILED', $errorMsg);
        return ['success' => false, 'message' => $errorMsg, 'error' => 'PHPMailer not found', 'status' => 'FAILED'];
    }

    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $user = getenv('SMTP_USER') ?: 'admin@buyundaprimaryschool.org';
    $pass = getenv('SMTP_PASS') ?: '';
    $secure = strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'));
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Buyunda Primary School';
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: $user;

    // Check for sandbox / unconfigured placeholder credentials
    if (!isSmtpConfigured()) {
        $infoMsg = "Notification queued in Sandbox Mode for <{$toEmail}> [{$subject}]. (SMTP credentials in .env are using placeholders).";
        error_log($infoMsg);
        logNotification($toEmail, $toName, $notificationType, $subject, 'LOGGED', 'Placeholder SMTP credentials in .env. Notification logged safely.');

        return [
            'success' => true,
            'message' => 'Notification recorded successfully (Sandbox / Logging Mode).',
            'error'   => 'Placeholder SMTP credentials',
            'status'  => 'LOGGED',
        ];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;

        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        // Permissive SSL options for local dev/XAMPP environments
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        if (!empty($replyToEmail) && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        error_log("Email successfully sent to {$toEmail} [Subject: {$subject}]");

        logNotification($toEmail, $toName, $notificationType, $subject, 'SENT');

        return ['success' => true, 'message' => "Email transmitted successfully to {$toEmail}.", 'status' => 'SENT'];
    } catch (Throwable $e) {
        $errorMsg = 'PHPMailer transmission error: ' . $e->getMessage();
        error_log($errorMsg);
        logNotification($toEmail, $toName, $notificationType, $subject, 'FAILED', $e->getMessage());

        return ['success' => false, 'message' => 'Failed to transmit email.', 'error' => $e->getMessage(), 'status' => 'FAILED'];
    }
}

/**
 * Builds standard responsive HTML email layout with Buyunda Primary School branding.
 */
function buildHtmlEmailTemplate(string $title, string $headerSubtitle, string $contentHtml, string $footerText = ''): string
{
    $schoolName = 'Buyunda Primary School';
    $year = date('Y');
    $footerNotice = $footerText ?: "This is an automated notification sent from the {$schoolName} Management Portal.";

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);" cellspacing="0" cellpadding="0">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #065f46 0%, #0f766e 100%); padding: 28px 24px; text-align: center;">
                            <div style="display: inline-block; width: 44px; height: 44px; line-height: 44px; background-color: #ffffff; color: #065f46; font-weight: bold; font-size: 22px; border-radius: 50%; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">B</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">{$schoolName}</h1>
                            <p style="margin: 4px 0 0; color: #a7f3d0; font-size: 13px;">{$headerSubtitle}</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 28px 24px;">
                            <h2 style="margin-top: 0; margin-bottom: 16px; color: #0f172a; font-size: 18px; font-weight: 700;">{$title}</h2>
                            {$contentHtml}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 24px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #1e293b;">
                            <p style="margin: 0 0 6px 0;">{$footerNotice}</p>
                            <p style="margin: 0; color: #64748b;">&copy; {$year} {$schoolName}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * 1. Teacher Attendance Alert to Admin.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendTeacherAttendanceNotification(string $teacherName, string $attendanceDate, string $signInTime, ?string $adminEmail = null): array
{
    $adminEmail = $adminEmail ?: getAdminEmail();
    $subject = "[Attendance Alert] Teacher {$teacherName} Signed In ({$attendanceDate})";
    $formattedDate = date('F j, Y', strtotime($attendanceDate));

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Hello Administrator,<br>
            A staff attendance record has just been logged on the portal. Below are the submission details:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px;">
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600; width: 35%;">Educator Name:</td>
                <td style="padding: 10px 0; color: #0f172a; font-weight: 700;">{$teacherName}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Date:</td>
                <td style="padding: 10px 0; color: #0f172a;">{$formattedDate}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Sign-in Time:</td>
                <td style="padding: 10px 0; color: #047857; font-weight: 700;">{$signInTime}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Recorded Status:</td>
                <td style="padding: 10px 0;"><span style="background-color: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase;">PRESENT</span></td>
            </tr>
        </table>

        <div style="background-color: #f8fafc; border-left: 4px solid #047857; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">
            <p style="margin: 0; font-size: 13px; color: #475569;">
                You can review real-time sign-in summaries anytime directly from the <strong>Admin Overview Dashboard</strong>.
            </p>
        </div>
HTML;

    $textBody = "Attendance Alert: {$teacherName} signed in on {$attendanceDate} at {$signInTime}. Status: PRESENT.";
    $htmlBody = buildHtmlEmailTemplate("Teacher Attendance Recorded", "Staff Attendance Notification", $contentHtml);

    return sendEmail($adminEmail, 'School Administrator', $subject, $htmlBody, $textBody, null, null, 'ATTENDANCE_ALERT');
}

/**
 * 2. Visitor / Parent Contact Inquiry Notification to Admin.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendContactInquiryNotification(string $senderName, string $senderEmail, string $messageContent, ?string $adminEmail = null): array
{
    $adminEmail = $adminEmail ?: getAdminEmail();
    $subject = "[Website Inquiry] New Message from {$senderName}";
    $timestamp = date('F j, Y, g:i a');
    $sanitizedMessage = nl2br(htmlspecialchars($messageContent));
    $safeName = htmlspecialchars($senderName);
    $safeEmail = htmlspecialchars($senderEmail);

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Hello Administrator,<br>
            A new inquiry message has been submitted via the public school contact form:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600; width: 30%;">Sender Name:</td>
                <td style="padding: 10px 0; color: #0f172a; font-weight: 700;">{$safeName}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Email Address:</td>
                <td style="padding: 10px 0; color: #0284c7; font-weight: 600;"><a href="mailto:{$safeEmail}" style="color: #0284c7; text-decoration: none;">{$safeEmail}</a></td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Submitted On:</td>
                <td style="padding: 10px 0; color: #64748b;">{$timestamp}</td>
            </tr>
        </table>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Inquiry Message:</p>
            <div style="font-size: 14px; line-height: 1.6; color: #1e293b;">
                {$sanitizedMessage}
            </div>
        </div>

        <p style="margin: 0; font-size: 13px; color: #64748b;">
            Tip: You can reply directly to this email to respond to <strong>{$safeName}</strong>.
        </p>
HTML;

    $textBody = "New Inquiry from {$senderName} ({$senderEmail}) on {$timestamp}:\n\n{$messageContent}";
    $htmlBody = buildHtmlEmailTemplate("New Website Inquiry", "Public Portal Message", $contentHtml);

    return sendEmail($adminEmail, 'School Administrator', $subject, $htmlBody, $textBody, $senderEmail, $senderName, 'CONTACT_INQUIRY');
}

/**
 * 3. New Pupil Enrollment Alert to Admin.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendNewPupilEnrollmentNotification(string $pupilName, string $regNumber, string $className, string $parentEmail, ?string $adminEmail = null): array
{
    $adminEmail = $adminEmail ?: getAdminEmail();
    $subject = "[Pupil Enrollment] {$pupilName} Registered ({$className})";
    $timestamp = date('F j, Y, g:i a');

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Hello Administrator,<br>
            A new pupil has been successfully registered in the Buyunda Primary School management database:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600; width: 35%;">Pupil Name:</td>
                <td style="padding: 10px 0; color: #0f172a; font-weight: 700;">{$pupilName}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Registration No:</td>
                <td style="padding: 10px 0; color: #047857; font-weight: 700;">{$regNumber}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Assigned Class:</td>
                <td style="padding: 10px 0; color: #0f172a;">{$className}</td>
            </tr>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Parent Email:</td>
                <td style="padding: 10px 0; color: #0284c7;">{$parentEmail}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #64748b; font-weight: 600;">Enrollment Date:</td>
                <td style="padding: 10px 0; color: #64748b;">{$timestamp}</td>
            </tr>
        </table>
HTML;

    $textBody = "New Pupil Registered: {$pupilName} (Reg: {$regNumber}) in {$className}. Parent Email: {$parentEmail}.";
    $htmlBody = buildHtmlEmailTemplate("New Pupil Enrollment", "Academic Records Notification", $contentHtml);

    return sendEmail($adminEmail, 'School Administrator', $subject, $htmlBody, $textBody, null, null, 'PUPIL_ENROLLMENT_ADMIN');
}

/**
 * 4. Pupil Enrollment Welcome Email to Parent.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendPupilEnrollmentConfirmation(string $pupilName, string $regNumber, string $className, string $parentEmail): array
{
    $subject = "Welcome to Buyunda Primary School - Enrollment Confirmation for {$pupilName}";
    $timestamp = date('F j, Y');

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Dear Parent / Guardian,<br>
            We are pleased to confirm that your child, <strong>{$pupilName}</strong>, has been officially registered at <strong>Buyunda Primary School</strong>.
        </p>

        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px; margin-bottom: 22px;">
            <h3 style="margin: 0 0 12px 0; color: #065f46; font-size: 15px;">Pupil Enrollment Details</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 8px 0; color: #047857; font-weight: 600; width: 40%;">Pupil Name:</td>
                    <td style="padding: 8px 0; color: #065f46; font-weight: 700;">{$pupilName}</td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 8px 0; color: #047857; font-weight: 600;">Registration Number:</td>
                    <td style="padding: 8px 0; color: #065f46; font-weight: 700;">{$regNumber}</td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 8px 0; color: #047857; font-weight: 600;">Registered Class:</td>
                    <td style="padding: 8px 0; color: #065f46;">{$className}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #047857; font-weight: 600;">Confirmation Date:</td>
                    <td style="padding: 8px 0; color: #065f46;">{$timestamp}</td>
                </tr>
            </table>
        </div>

        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Our dedicated faculty and staff look forward to partnering with you in providing your child with an enriching, disciplined, and transformative education.
        </p>

        <p style="margin: 0; font-size: 13px; color: #64748b;">
            For inquiries, please contact our administrative desk at <a href="mailto:admin@buyundaprimaryschool.org" style="color: #047857; font-weight: 600;">admin@buyundaprimaryschool.org</a>.
        </p>
HTML;

    $textBody = "Welcome to Buyunda Primary School! {$pupilName} has been enrolled in {$className} with Registration Number: {$regNumber}.";
    $htmlBody = buildHtmlEmailTemplate("Enrollment Confirmation", "Welcome to Buyunda Primary School", $contentHtml);

    return sendEmail($parentEmail, "Parent of {$pupilName}", $subject, $htmlBody, $textBody, null, null, 'PUPIL_ENROLLMENT_PARENT');
}

/**
 * 5. Report Card Grade Breakdown & Performance Summary to Parent.
 *
 * @param array<string, mixed> $pupil
 * @param array<int, array<string, mixed>> $reportRows
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendParentReportCardNotification(
    array $pupil,
    string $term,
    string $academicYear,
    array $reportRows,
    ?string $adminComment = null
): array {
    $parentEmail = (string) ($pupil['parent_email'] ?? '');
    $pupilName = (string) ($pupil['full_name'] ?? 'Pupil');
    $className = (string) ($pupil['class_name'] ?? '');
    $regNumber = (string) ($pupil['reg_number'] ?? '');

    if (empty($parentEmail) || !filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Invalid or missing parent email address.',
            'error'   => 'Invalid parent email',
            'status'  => 'FAILED',
        ];
    }

    $formattedTerm = str_replace('_', ' ', $term);
    $subject = "[Report Card] Academic Performance for {$pupilName} ({$formattedTerm} {$academicYear})";

    $tableRowsHtml = '';
    $totalMarks = 0.0;
    $count = count($reportRows);

    foreach ($reportRows as $row) {
        $subjectName = htmlspecialchars((string) ($row['subject'] ?? ''));
        $marks = (float) ($row['marks'] ?? 0);
        $totalMarks += $marks;
        $comment = htmlspecialchars((string) ($row['comments'] ?? ''));

        $gradeColor = $marks >= 80 ? '#047857' : ($marks >= 60 ? '#0284c7' : ($marks >= 50 ? '#d97706' : '#dc2626'));

        $tableRowsHtml .= <<<HTML
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 12px; font-weight: 600; color: #1e293b;">{$subjectName}</td>
                <td style="padding: 10px 12px; text-align: center; font-weight: 700; color: {$gradeColor};">{$marks}%</td>
                <td style="padding: 10px 12px; color: #475569; font-size: 13px;">{$comment}</td>
            </tr>
HTML;
    }

    $averageMarks = $count > 0 ? round($totalMarks / $count, 1) : 0.0;

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Dear Parent / Guardian of <strong>{$pupilName}</strong>,<br>
            Please find below the official academic report summary for <strong>{$formattedTerm} ({$academicYear})</strong>.
        </p>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="color: #64748b;">Pupil Name: <strong>{$pupilName}</strong></span>
                <span style="color: #64748b;">Reg No: <strong>{$regNumber}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b;">Class: <strong>{$className}</strong></span>
                <span style="color: #64748b;">Term / Year: <strong>{$formattedTerm} - {$academicYear}</strong></span>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background-color: #065f46; color: #ffffff; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <th style="padding: 10px 12px;">Subject</th>
                    <th style="padding: 10px 12px; text-align: center;">Score</th>
                    <th style="padding: 10px 12px;">Teacher Remark</th>
                </tr>
            </thead>
            <tbody>
                {$tableRowsHtml}
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 700;">
                    <td style="padding: 10px 12px; color: #0f172a;">Overall Average:</td>
                    <td style="padding: 10px 12px; text-align: center; color: #047857; font-size: 15px;">{$averageMarks}%</td>
                    <td style="padding: 10px 12px; color: #64748b; font-size: 13px;">({$count} Subjects evaluated)</td>
                </tr>
            </tfoot>
        </table>
HTML;

    if (!empty($adminComment)) {
        $safeAdminComment = htmlspecialchars($adminComment);
        $contentHtml .= <<<HTML
            <div style="background-color: #ecfdf5; border-left: 4px solid #047857; padding: 14px 16px; border-radius: 6px; margin-bottom: 20px;">
                <p style="margin: 0 0 4px 0; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #065f46;">Headteacher Remarks:</p>
                <p style="margin: 0; font-size: 13px; color: #1e293b;">{$safeAdminComment}</p>
            </div>
HTML;
    }

    $contentHtml .= <<<HTML
        <p style="margin: 0; font-size: 13px; color: #64748b;">
            To view or print the full official report card certificate, please consult with the school administration.
        </p>
HTML;

    $textBody = "Report Card for {$pupilName} ({$formattedTerm} {$academicYear}): Average Score: {$averageMarks}%.";
    $htmlBody = buildHtmlEmailTemplate("Academic Report Card", "Student Evaluation Report", $contentHtml);

    return sendEmail($parentEmail, "Parent of {$pupilName}", $subject, $htmlBody, $textBody, null, null, 'REPORT_CARD_PARENT');
}

/**
 * 6. New User Account Provisioning & Credentials Email.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendNewUserAccountEmail(string $fullName, string $email, string $rawPassword, string $role): array
{
    $subject = "Your Buyunda Primary School Account Credentials";
    $portalUrl = 'http://localhost/buyunda-primary-school/public/login.php';
    $roleTitle = $role === 'ADMIN' ? 'Administrator' : 'Educator / Teacher';

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Hello <strong>{$fullName}</strong>,<br>
            An account has been created for you on the <strong>Buyunda Primary School</strong> Management System with the role of <strong>{$roleTitle}</strong>.
        </p>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 22px;">
            <h3 style="margin: 0 0 12px 0; color: #0f172a; font-size: 15px;">Your Login Credentials</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600; width: 35%;">Portal URL:</td>
                    <td style="padding: 8px 0; color: #0284c7;"><a href="{$portalUrl}" style="color: #0284c7; text-decoration: none; font-weight: 600;">Open Login Portal</a></td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Email Address:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{$email}</td>
                </tr>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Temporary Password:</td>
                    <td style="padding: 8px 0; font-family: monospace; color: #047857; font-weight: 700; font-size: 15px;">{$rawPassword}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Assigned Role:</td>
                    <td style="padding: 8px 0; color: #0f172a;">{$roleTitle}</td>
                </tr>
            </table>
        </div>

        <div style="background-color: #fffbeb; border: 1px solid #fef3c7; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0; font-size: 13px; color: #92400e;">
                <strong>Security Notice:</strong> Please sign in and protect your account credentials. Do not share your login information with unauthorized persons.
            </p>
        </div>
HTML;

    $textBody = "Your Buyunda Primary School account has been created.\nRole: {$roleTitle}\nEmail: {$email}\nPassword: {$rawPassword}\nLogin: {$portalUrl}";
    $htmlBody = buildHtmlEmailTemplate("Welcome to the Staff Portal", "Account Provisioning", $contentHtml);

    return sendEmail($email, $fullName, $subject, $htmlBody, $textBody, null, null, 'USER_PROVISION');
}

/**
 * 7. Teacher Subject Assignment Notification.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendTeacherSubjectAssignmentEmail(string $teacherName, string $teacherEmail, string $subjectName): array
{
    $subject = "New Subject Assignment: {$subjectName}";
    $timestamp = date('F j, Y');

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Dear <strong>{$teacherName}</strong>,<br>
            The school administration has assigned a new academic subject to your teaching profile.
        </p>

        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 8px 0; color: #047857; font-weight: 600; width: 35%;">Assigned Subject:</td>
                    <td style="padding: 8px 0; color: #065f46; font-weight: 700; font-size: 16px;">{$subjectName}</td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 8px 0; color: #047857; font-weight: 600;">Instructor:</td>
                    <td style="padding: 8px 0; color: #065f46;">{$teacherName}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #047857; font-weight: 600;">Assignment Date:</td>
                    <td style="padding: 8px 0; color: #065f46;">{$timestamp}</td>
                </tr>
            </table>
        </div>

        <p style="margin: 0; font-size: 13px; color: #64748b;">
            You can review your updated subject list anytime on your <a href="http://localhost/buyunda-primary-school/public/teacher_profile.php" style="color: #047857; font-weight: 600;">Teacher Profile</a>.
        </p>
HTML;

    $textBody = "New Subject Assignment: {$subjectName} has been assigned to you ({$teacherName}) on {$timestamp}.";
    $htmlBody = buildHtmlEmailTemplate("Subject Assignment Notification", "Faculty Curriculum Updates", $contentHtml);

    return sendEmail($teacherEmail, $teacherName, $subject, $htmlBody, $textBody, null, null, 'SUBJECT_ASSIGNMENT');
}

/**
 * 8. Diagnostics / SMTP Test Email to Admin.
 *
 * @return array{success: bool, message: string, error?: string, status: string}
 */
function sendTestEmailToAdmin(?string $adminEmail = null): array
{
    $adminEmail = $adminEmail ?: getAdminEmail();
    $subject = "[Test Diagnostic] Buyunda Primary School Email System Working";
    $timestamp = date('F j, Y, g:i:s a');
    $phpVersion = PHP_VERSION;
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = getenv('SMTP_PORT') ?: '587';
    $smtpUser = getenv('SMTP_USER') ?: 'admin@buyundaprimaryschool.org';

    $contentHtml = <<<HTML
        <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
            Congratulations! Your email notification engine for <strong>Buyunda Primary School</strong> is correctly configured and operational.
        </p>

        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 8px 0; color: #065f46; font-size: 14px;">Diagnostic Connection Details:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #047857; line-height: 1.6;">
                <li>Recipient Admin: <strong>{$adminEmail}</strong></li>
                <li>SMTP Host: <strong>{$smtpHost}:{$smtpPort}</strong></li>
                <li>SMTP Authenticated User: <strong>{$smtpUser}</strong></li>
                <li>Server Timestamp: <strong>{$timestamp}</strong></li>
                <li>PHP Version: <strong>{$phpVersion}</strong></li>
            </ul>
        </div>
HTML;

    $textBody = "Email System Test: Connection verified successfully to {$adminEmail} at {$timestamp}.";
    $htmlBody = buildHtmlEmailTemplate("Email System Test Successful", "SMTP Diagnostics", $contentHtml);

    return sendEmail($adminEmail, 'School Administrator', $subject, $htmlBody, $textBody, null, null, 'SMTP_TEST');
}