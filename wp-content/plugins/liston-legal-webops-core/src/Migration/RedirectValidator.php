<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Migration;

final class RedirectValidator
{
    /**
     * @return array{rows:array<int,array{source:string,destination:string,status:int}>,issues:array<int,array{level:string,code:string,row:int,message:string}>,valid:bool}
     */
    public function validate_file(string $file, bool $check_destinations = true): array
    {
        $issues = [];
        $rows   = [];
        if (! is_readable($file)) {
            return ['rows' => [], 'issues' => [['level' => 'error', 'code' => 'file_unreadable', 'row' => 0, 'message' => 'CSV file is not readable.']], 'valid' => false];
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return ['rows' => [], 'issues' => [['level' => 'error', 'code' => 'file_unreadable', 'row' => 0, 'message' => 'CSV file could not be opened.']], 'valid' => false];
        }

        $headers = fgetcsv($handle, null, ',', '"', '');
        $headers = is_array($headers) ? array_map(static fn ($item): string => strtolower(trim((string) $item)), $headers) : [];
        if (! in_array('source', $headers, true) || ! in_array('destination', $headers, true)) {
            fclose($handle);
            return ['rows' => [], 'issues' => [['level' => 'error', 'code' => 'headers', 'row' => 1, 'message' => 'CSV requires source and destination headers.']], 'valid' => false];
        }

        $line = 1;
        while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
            ++$line;
            if ($values === [null] || $values === []) {
                continue;
            }
            $row = array_combine($headers, array_pad($values, count($headers), ''));
            if (! is_array($row)) {
                $issues[] = $this->issue('error', 'malformed_row', $line, 'Column count does not match the header.');
                continue;
            }
            $source = trim((string) ($row['source'] ?? ''));
            $destination = trim((string) ($row['destination'] ?? ''));
            $status = (int) ($row['status'] ?? 301);

            if ($source === '' || ! $this->valid_source($source)) {
                $issues[] = $this->issue('error', 'malformed_source', $line, 'Source must be a valid site-relative path or HTTP(S) URL.');
            }
            if ($destination === '') {
                $issues[] = $this->issue('error', 'missing_destination', $line, 'Destination is missing.');
            } elseif (! $this->valid_destination($destination)) {
                $issues[] = $this->issue('error', 'malformed_destination', $line, 'Destination must be a valid site-relative path or HTTP(S) URL.');
            }
            if (! in_array($status, [301, 302, 307, 308], true)) {
                $issues[] = $this->issue('warning', 'status_normalized', $line, 'Unsupported status will be normalized to 301.');
                $status = 301;
            }
            if (preg_match('#^http://#i', $source) && preg_match('#^https://#i', $destination)) {
                $issues[] = $this->issue('warning', 'scheme_inconsistency', $line, 'HTTP source maps to HTTPS; confirm the migration protocol policy.');
            }
            if ($source !== '' && $destination !== '') {
                $source_path = RedirectRepository::normalize_path($source);
                $destination_path = RedirectRepository::normalize_path($destination);
                if ($source_path === $destination_path) {
                    $issues[] = $this->issue('error', 'redirect_loop', $line, 'Source and destination resolve to the same path.');
                }
            }

            $rows[] = ['source' => $source, 'destination' => $destination, 'status' => $status, '_line' => $line];
        }
        fclose($handle);

        $issues = array_merge($issues, $this->cross_row_issues($rows));
        if ($check_destinations) {
            $issues = array_merge($issues, $this->destination_issues($rows));
        }
        $has_errors = (bool) array_filter($issues, static fn (array $issue): bool => $issue['level'] === 'error');

        return ['rows' => array_map(static fn (array $row): array => array_intersect_key($row, array_flip(['source', 'destination', 'status'])), $rows), 'issues' => $issues, 'valid' => ! $has_errors];
    }

    /** @param array<int,array<string,mixed>> $rows @return array<int,array{level:string,code:string,row:int,message:string}> */
    private function cross_row_issues(array $rows): array
    {
        $issues = [];
        $sources = [];
        $destinations = [];
        $semantic_sources = [];
        $home_rows = [];

        foreach ($rows as $row) {
            $line = (int) $row['_line'];
            $source = RedirectRepository::normalize_path((string) $row['source']);
            $destination = RedirectRepository::normalize_destination((string) $row['destination']);
            $destination_path = RedirectRepository::normalize_path($destination);
            if (isset($sources[$source])) {
                $issues[] = $this->issue('error', 'duplicate_source', $line, sprintf('Duplicate source; first defined on row %d.', $sources[$source]));
            } else {
                $sources[$source] = $line;
            }
            $destinations[$destination][] = $line;
            $semantic = preg_replace('#\.html$#', '', rtrim($source, '/')) ?: '/';
            if (isset($semantic_sources[$semantic]) && $semantic_sources[$semantic]['path'] !== $source) {
                $issues[] = $this->issue('warning', 'html_trailing_conflict', $line, sprintf('.html/trailing-slash conflict with row %d.', $semantic_sources[$semantic]['line']));
            } else {
                $semantic_sources[$semantic] = ['line' => $line, 'path' => $source];
            }
            if ($destination_path === RedirectRepository::normalize_path(home_url('/'))) {
                $home_rows[] = $line;
            }
        }

        foreach ($destinations as $destination => $lines) {
            if (count($lines) > 1) {
                foreach (array_slice($lines, 1) as $line) {
                    $issues[] = $this->issue('warning', 'duplicate_destination', $line, sprintf('Destination is shared with row %d and requires relevance review.', $lines[0]));
                }
            }
        }

        foreach ($rows as $row) {
            $line = (int) $row['_line'];
            $source = RedirectRepository::normalize_path((string) $row['source']);
            $destination = RedirectRepository::normalize_path((string) $row['destination']);
            if (isset($sources[$destination]) && $destination !== $source) {
                $next_row = $sources[$destination];
                $next = $rows[array_search($next_row, array_column($rows, '_line'), true)] ?? null;
                $is_loop = is_array($next) && RedirectRepository::normalize_path((string) $next['destination']) === $source;
                $issues[] = $this->issue('error', $is_loop ? 'redirect_loop' : 'redirect_chain', $line, $is_loop ? sprintf('Two-way loop with row %d.', $next_row) : sprintf('Destination is itself a source on row %d; collapse to the final URL.', $next_row));
            }
        }

        $graph = [];
        foreach ($rows as $row) {
            $graph[RedirectRepository::normalize_path((string) $row['source'])] = RedirectRepository::normalize_path((string) $row['destination']);
        }
        foreach ($rows as $row) {
            $origin = RedirectRepository::normalize_path((string) $row['source']);
            $cursor = $origin;
            $visited = [];
            while (isset($graph[$cursor])) {
                if (isset($visited[$cursor])) {
                    $issues[] = $this->issue('error', 'redirect_loop', (int) $row['_line'], 'Redirect graph contains a cycle; every legacy URL must resolve in one hop.');
                    break;
                }
                $visited[$cursor] = true;
                $cursor = $graph[$cursor];
            }
        }

        if (count($home_rows) >= 3 && count($home_rows) / max(1, count($rows)) >= 0.2) {
            foreach ($home_rows as $line) {
                $issues[] = $this->issue('error', 'unsafe_homepage_redirect', $line, 'Mass redirect to the homepage is unsafe and loses topical relevance.');
            }
        }
        return $issues;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<int,array{level:string,code:string,row:int,message:string}> */
    private function destination_issues(array $rows): array
    {
        $issues = [];
        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        foreach ($rows as $row) {
            $destination = RedirectRepository::normalize_destination((string) $row['destination']);
            $host = strtolower((string) wp_parse_url($destination, PHP_URL_HOST));
            if ($host !== $home_host) {
                continue;
            }
            $response = wp_safe_remote_head($destination, ['timeout' => 5, 'redirection' => 0, 'reject_unsafe_urls' => false]);
            if (is_wp_error($response)) {
                $issues[] = $this->issue('warning', 'destination_unreachable', (int) $row['_line'], 'Destination could not be checked: ' . $response->get_error_message());
                continue;
            }
            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code >= 400 || $code === 0) {
                $issues[] = $this->issue('error', 'destination_http_error', (int) $row['_line'], sprintf('Existing destination returned HTTP %d.', $code));
            } elseif ($code >= 300) {
                $issues[] = $this->issue('error', 'destination_redirects', (int) $row['_line'], sprintf('Destination returned HTTP %d; map directly to its final URL.', $code));
            }
        }
        return $issues;
    }

    private function valid_source(string $source): bool
    {
        if (str_starts_with($source, '/')) {
            return ! str_starts_with($source, '//') && ! preg_match('/[\x00-\x1F\x7F]/', $source);
        }
        return (bool) wp_http_validate_url($source);
    }

    private function valid_destination(string $destination): bool
    {
        return str_starts_with($destination, '/') ? $this->valid_source($destination) : (bool) wp_http_validate_url($destination);
    }

    /** @return array{level:string,code:string,row:int,message:string} */
    private function issue(string $level, string $code, int $row, string $message): array
    {
        return compact('level', 'code', 'row', 'message');
    }
}
