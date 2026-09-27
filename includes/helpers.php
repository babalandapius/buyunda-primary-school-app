<?php
/**
 * Shared helper functions for the application.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function getSchoolName(): string
{
    try {
        $stmt = db()->query("SELECT school_name FROM school_settings ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['school_name'] ?? 'Buyunda Primary School';
    } catch (Throwable $e) {
        error_log('School name lookup failed: ' . $e->getMessage());
        return 'Buyunda Primary School';
    }
}

function totalPupils(): int
{
    try {
        $stmt = db()->query('SELECT COUNT(*) AS total FROM pupils');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('Total pupils query failed: ' . $e->getMessage());
        return 0;
    }
}

function totalTeachers(): int
{
    try {
        $stmt = db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'TEACHER'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('Total teachers query failed: ' . $e->getMessage());
        return 0;
    }
}

function getRecentAttendance(int $limit = 10): array
{
    try {
        $stmt = db()->prepare(
            'SELECT a.id, a.date, a.sign_in_time, a.status, u.full_name AS teacher_name
             FROM attendance a
             INNER JOIN users u ON u.id = a.teacher_id
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Recent attendance query failed: ' . $e->getMessage());
        return [];
    }
}

function getPupils(): array
{
    try {
        $stmt = db()->query('SELECT * FROM pupils ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Pupil query failed: ' . $e->getMessage());
        return [];
    }
}

function getRecentNotifications(int $limit = 10): array
{
    try {
        $stmt = db()->prepare('SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Recent notifications query failed: ' . $e->getMessage());
        return [];
    }
}

function totalNotificationsSent(): int
{
    try {
        $stmt = db()->query("SELECT COUNT(*) AS total FROM notification_logs WHERE status IN ('SENT', 'LOGGED')");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        error_log('Total notifications query failed: ' . $e->getMessage());
        return 0;
    }
}