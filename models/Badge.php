<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class Badge
{
    public static function isAuthorized(string $uid): bool
    {
        $st = Database::get()->prepare(
            'SELECT id FROM badges WHERE tag_uid = ? AND autorise = 1 LIMIT 1'
        );
        $st->execute([$uid]);
        return $st->fetch() !== false;
    }

    public static function findByUid(string $uid): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM badges WHERE tag_uid = ? LIMIT 1');
        $st->execute([$uid]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        return Database::get()
            ->query('SELECT * FROM badges ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function create(string $uid, string $nom, ?int $userId = null): int
    {
        $st = Database::get()->prepare(
            'INSERT INTO badges (tag_uid, nom, autorise, user_id) VALUES (?, ?, 1, ?)'
        );
        $st->execute([strtoupper(trim($uid)), trim($nom), $userId]);
        return (int) Database::get()->lastInsertId();
    }

    public static function toggleAuth(int $id): void
    {
        Database::get()->prepare(
            'UPDATE badges SET autorise = 1 - autorise WHERE id = ?'
        )->execute([$id]);
    }

    public static function delete(int $id): void
    {
        Database::get()->prepare('DELETE FROM badges WHERE id = ?')->execute([$id]);
    }
}
