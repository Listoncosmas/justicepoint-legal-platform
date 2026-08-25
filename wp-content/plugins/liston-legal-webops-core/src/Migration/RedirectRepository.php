<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Migration;

final class RedirectRepository
{
    public function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'jp_redirects';
    }

    public function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table   = $this->table();
        dbDelta(
            "CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                source_path varchar(512) NOT NULL,
                destination_url varchar(1024) NOT NULL,
                status_code smallint(3) unsigned NOT NULL DEFAULT 301,
                hits bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY source_path (source_path(191))
            ) {$charset};"
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $source_path): ?array
    {
        global $wpdb;
        $path = self::normalize_path($source_path);
        if ($path === '') {
            return null;
        }
        $sql = $wpdb->prepare('SELECT * FROM %i WHERE source_path = %s LIMIT 1', $this->table(), $path);
        $row = $wpdb->get_row($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately above with identifier and value placeholders.
        return is_array($row) ? $row : null;
    }

    public function upsert(string $source, string $destination, int $status = 301): bool
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $result = $wpdb->replace(
            $this->table(),
            [
                'source_path'     => self::normalize_path($source),
                'destination_url' => self::normalize_destination($destination),
                'status_code'     => in_array($status, [301, 302, 307, 308], true) ? $status : 301,
                'hits'            => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s']
        );
        return $result !== false;
    }

    public function increment_hits(int $id): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('UPDATE %i SET hits = hits + 1, updated_at = %s WHERE id = %d', $this->table(), current_time('mysql', true), $id));
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM %i ORDER BY source_path ASC', $this->table()), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function normalize_path(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }
        $path = str_starts_with($source, 'http://') || str_starts_with($source, 'https://')
            ? (string) wp_parse_url($source, PHP_URL_PATH)
            : (string) strtok($source, '?#');
        $path = '/' . ltrim(rawurldecode($path), '/');
        return preg_replace('#/+#', '/', $path) ?: '';
    }

    public static function normalize_destination(string $destination): string
    {
        $destination = trim($destination);
        if (str_starts_with($destination, '/')) {
            return home_url(self::normalize_path($destination));
        }
        return esc_url_raw($destination, ['http', 'https']);
    }
}
