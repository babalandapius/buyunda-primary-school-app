<?php
/**
 * Seed default users for the Buyunda Primary School system.
 * Run this script after creating the database schema.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$users = [
    [
        'full_name' => 'System Administrator',
        'email' => 'admin@buyundaprimaryschool.org',
        'password' => 'admin123',
        'role' => 'ADMIN',
    ],
    [
        'full_name' => 'Teacher One',
        'email' => 'teacher@buyundaprimaryschool.org',
        'password' => 'teacher123',
        'role' => 'TEACHER',
    ],
];

foreach ($users as $user) {
    $hash = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, created_at)
         VALUES (:full_name, :email, :password_hash, :role, NOW())
         ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password_hash = VALUES(password_hash), role = VALUES(role)'
    );
    $stmt->execute([
        ':full_name' => $user['full_name'],
        ':email' => $user['email'],
        ':password_hash' => $hash,
        ':role' => $user['role'],
    ]);

    echo "Created/updated user: {$user['email']} ({$user['role']})\n";
}

echo "Default school users configured successfully.\n";
