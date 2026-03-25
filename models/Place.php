<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class Place
{
    public static function freeExpiredReservations(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        Database::get()->prepare(
            "UPDATE places SET etat = 'libre', reserve_par = NULL, reserve_jusqu_a = NULL WHERE etat = 'reservee' AND reserve_jusqu_a <= ?"
        )->execute([$now]);
    }

    public static function all(): array
    {
        self::freeExpiredReservations();
        return Database::get()
            ->query('SELECT * FROM places ORDER BY id_place')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        self::freeExpiredReservations();
        $st = Database::get()->prepare('SELECT * FROM places WHERE id_place = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function reserve(int $id_place, int $user_id): bool
    {
        // Check if user already has a reservation
        $st = Database::get()->prepare('SELECT id_place FROM places WHERE reserve_par = ? AND etat = "reservee" LIMIT 1');
        $st->execute([$user_id]);
        if ($st->fetch()) return false;

        $timeout = (new DateTimeImmutable('+5 minutes'))->format('Y-m-d H:i:s');
        $st = Database::get()->prepare("UPDATE places SET etat = 'reservee', reserve_par = ?, reserve_jusqu_a = ? WHERE id_place = ? AND etat = 'libre'");
        $st->execute([$user_id, $timeout, $id_place]);
        return $st->rowCount() > 0;
    }

    public static function cancelReservation(int $id_place, int $user_id): void
    {
        Database::get()->prepare("UPDATE places SET etat = 'libre', reserve_par = NULL, reserve_jusqu_a = NULL WHERE id_place = ? AND reserve_par = ? AND etat = 'reservee'")->execute([$id_place, $user_id]);
    }

    public static function update(int $id, string $etat, ?string $uid = null): void
    {
        Database::get()->prepare(
            'UPDATE places SET etat = ?, uid_actuel = ?, reserve_par = NULL, reserve_jusqu_a = NULL WHERE id_place = ?'
        )->execute([$etat, $uid, $id]);
    }

    public static function resetAll(): void
    {
        Database::get()->exec('UPDATE places SET etat = \'libre\', uid_actuel = NULL, reserve_par = NULL, reserve_jusqu_a = NULL');
    }

    public static function stats(): array
    {
        self::freeExpiredReservations();
        $rows = Database::get()
            ->query('SELECT etat, COUNT(*) as n FROM places GROUP BY etat')
            ->fetchAll();

        $stats = ['libre' => 0, 'occupee' => 0, 'panne' => 0, 'reservee' => 0];
        foreach ($rows as $r) {
            if (isset($stats[$r['etat']])) {
                $stats[$r['etat']] = (int) $r['n'];
            }
        }
        return $stats;
    }
}
