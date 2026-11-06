<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Aset IT services waiting for spare parts ("Menunggu Suku Cadang") for the "Perlu perhatian" bell and page,
 * as long as they wait, newest first, with whether the given account has seen them (asset_attention_reads,
 * per service and the moment it started waiting, so waiting again later counts as new).
 */
final class AssetAttention
{
    public const STATUS = 'parts';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id: string, deviceId: string, deviceName: string, type: string, problem: string, technician: string, note: string, since: string, read: bool}> */
    public function waiting(int $userId): array
    {
        $items = [];
        foreach ($this->pdo->query("SELECT data_json FROM asset_services WHERE status = '" . self::STATUS . "'")->fetchAll() as $row) {
            $t = json_decode((string) $row['data_json'], true);
            if (!is_array($t) || ($t['status'] ?? '') !== self::STATUS) {
                continue;
            }
            $log = is_array($t['log'] ?? null) ? $t['log'] : [];
            $last = $log === [] ? [] : end($log);
            $items[] = [
                'id' => (string) $t['id'], 'deviceId' => (string) ($t['deviceId'] ?? ''), 'deviceName' => (string) ($t['deviceName'] ?? ''),
                'type' => (string) ($t['type'] ?? ''), 'problem' => (string) ($t['problem'] ?? ''), 'technician' => (string) ($t['technician'] ?? ''),
                'note' => is_array($last) ? (string) ($last['note'] ?? '') : '', 'since' => self::since($t), 'read' => false,
            ];
        }
        if ($items === []) {
            return [];
        }
        $seen = $this->pdo->prepare('SELECT service_id, since FROM asset_attention_reads WHERE user_id = ?');
        $seen->execute([$userId]);
        $read = [];
        foreach ($seen->fetchAll() as $r) {
            $read[$r['service_id'] . '|' . $r['since']] = true;
        }
        foreach ($items as &$item) {
            $item['read'] = isset($read[$item['id'] . '|' . $item['since']]);
        }
        unset($item);
        usort($items, static fn (array $a, array $b): int => strcmp($b['since'], $a['since']));
        return $items;
    }

    /** $userId has seen these waiting services (ids of others are ignored). @param list<string> $serviceIds */
    public function markRead(int $userId, array $serviceIds): void
    {
        $wanted = array_flip(array_filter($serviceIds, 'is_string'));
        $insert = $this->pdo->prepare('INSERT IGNORE INTO asset_attention_reads (user_id, service_id, since, read_at) VALUES (?, ?, ?, UTC_TIMESTAMP())');
        foreach ($this->waiting($userId) as $item) {
            if (isset($wanted[$item['id']]) && !$item['read']) {
                $insert->execute([$userId, $item['id'], $item['since']]);
            }
        }
    }

    public function markAllRead(int $userId): void
    {
        $this->markRead($userId, array_column($this->waiting($userId), 'id'));
    }

    /**
     * When a service started its current wait: the first entry of the last run of "parts" in its log (an update
     * that only adds a note keeps the wait), or its creation time.
     */
    public static function since(array $service): string
    {
        $since = (string) ($service['createdAt'] ?? '');
        foreach (array_reverse(is_array($service['log'] ?? null) ? $service['log'] : []) as $entry) {
            if (!is_array($entry) || ($entry['status'] ?? '') !== self::STATUS) {
                break;
            }
            $since = (string) ($entry['at'] ?? $since);
        }
        return $since;
    }

    /** The UTC "Y-m-d H:i:s" of an ISO time of the asset records (for App\Helpers\Format). */
    public static function utc(string $iso): string
    {
        return preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/', $iso, $m) === 1 ? $m[1] . ' ' . $m[2] : '';
    }
}
