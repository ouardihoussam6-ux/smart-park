<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class Place
{
    public static function all(): array
    {
        return Database::get()
            ->query('SELECT * FROM places ORDER BY id_place')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM places WHERE id_place = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function update(int $id, string $etat, ?string $uid = null): void
    {
        Database::get()->prepare(
            'UPDATE places SET etat = ?, uid_actuel = ? WHERE id_place = ?'
        )->execute([$etat, $uid, $id]);
    }

    public static function resetAll(): void
    {
        Database::get()->exec('UPDATE places SET etat = \'libre\', uid_actuel = NULL');
    }

    public static function stats(): array
    {
        $rows = Database::get()
            ->query('SELECT etat, COUNT(*) as n FROM places GROUP BY etat')
            ->fetchAll();

        $stats = ['libre' => 0, 'occupee' => 0, 'panne' => 0];
        foreach ($rows as $r) {
            if (isset($stats[$r['etat']])) {
                $stats[$r['etat']] = (int) $r['n'];
            }
        }
        return $stats;
    }
}
