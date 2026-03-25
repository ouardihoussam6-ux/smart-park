<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class User
{
    public static function create(string $nom, string $prenom, string $email, string $password, string $role = 'user'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st = Database::get()->prepare(
            'INSERT INTO users (nom, prenom, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([trim($nom), trim($prenom), trim($email), $hash, $role]);
        return (int) Database::get()->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([trim($email)]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }
}
