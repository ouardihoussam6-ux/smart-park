<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

final class Setting
{
    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $st = Database::get()->prepare('SELECT val FROM settings WHERE cle = ? LIMIT 1');
        $st->execute([$key]);
        $row = $st->fetch();
        return $row ? $row['val'] : $default;
    }

    /**
     * Set a setting value (insert or update).
     */
    public static function set(string $key, string $value): void
    {
        $st = Database::get()->prepare(
            'INSERT INTO settings (cle, val) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE val = VALUES(val)'
        );
        $st->execute([$key, $value]);
    }

    /**
     * Get opening hour (default 07:00).
     */
    public static function openingHour(): string
    {
        return self::get('parking_open', '07:00') ?? '07:00';
    }

    /**
     * Get closing hour (default 19:00).
     */
    public static function closingHour(): string
    {
        return self::get('parking_close', '19:00') ?? '19:00';
    }

    /**
     * Check if parking access is enabled (time-based control can be toggled).
     */
    public static function isScheduleEnabled(): bool
    {
        return self::get('schedule_enabled', '1') === '1';
    }

    /**
     * Check if parking is currently open based on schedule.
     * Returns true if schedule is disabled OR current time is within the range.
     */
    public static function isParkingOpen(): bool
    {
        if (!self::isScheduleEnabled()) {
            return true; // no schedule restriction
        }

        $now   = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $open  = DateTimeImmutable::createFromFormat('H:i', self::openingHour(), new DateTimeZone('Europe/Paris'));
        $close = DateTimeImmutable::createFromFormat('H:i', self::closingHour(), new DateTimeZone('Europe/Paris'));

        if (!$open || !$close) {
            return true; // fallback: allow access on parse error
        }

        // Set all dates to the same day for comparison
        $nowTime   = (int) $now->format('Hi');
        $openTime  = (int) $open->format('Hi');
        $closeTime = (int) $close->format('Hi');

        if ($openTime <= $closeTime) {
            // Normal range: e.g. 07:00 – 19:00
            return $nowTime >= $openTime && $nowTime < $closeTime;
        } else {
            // Overnight range: e.g. 22:00 – 06:00
            return $nowTime >= $openTime || $nowTime < $closeTime;
        }
    }
}
