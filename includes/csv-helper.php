<?php

defined('ABSPATH') || exit;

function ajax_snippets_csv_source($input, array $args = [])
{
        $has_header = !empty($args['has_header']);
        $delimiter = isset($args['delimiter']) ? $args['delimiter'] : ',';
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;
        $limit = array_key_exists('limit', $args) ? (int) $args['limit'] : null;
        if ($limit !== null && $limit <= 0) {
            $limit = null;
        }

        $source_path = $input;
        $downloaded = false;

        if (preg_match('~^https?://~i', $input)) {
            $upload_dir = wp_upload_dir();
            if (!empty($upload_dir['path']) && !empty($upload_dir['basedir'])) {
                $hash = md5($input);
                $filename = 'ajax-snippets-csv-' . $hash . '-' . time() . '.csv';
                $target = trailingslashit($upload_dir['path']) . $filename;
                $latest = trailingslashit($upload_dir['basedir']) . 'ajax-snippets-csv-' . $hash . '-latest.csv';
                $response = wp_remote_get($input, ['timeout' => 20]);
                if (is_wp_error($response)) {
                    echo Ajax_Snippets_Table::render(['error' => $response->get_error_message()], 'CSV Download Error');
                    return '';
                }
                $status = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                if ($status !== 200 || $body === '') {
                    echo Ajax_Snippets_Table::render(['status' => $status, 'error' => 'Empty response'], 'CSV Download Error');
                    return '';
                }
                if (@file_put_contents($target, $body) === false) {
                    echo Ajax_Snippets_Table::render(['error' => 'Cannot write file to uploads'], 'CSV Download Error');
                    return '';
                }
                @file_put_contents($latest, $body);
                $source_path = $target;
                $downloaded = true;
            }
        }

        if (!$source_path || !file_exists($source_path) || !is_readable($source_path)) {
            echo Ajax_Snippets_Table::render(['path' => $source_path, 'error' => 'File not found or unreadable'], 'CSV');
            return '';
        }

        $meta = [
            'path' => $source_path,
            'downloaded' => $downloaded ? 'yes' : 'no',
            'has_header' => $has_header ? 'yes' : 'no',
            'delimiter' => $delimiter,
            'offset' => $offset,
            'limit' => $limit === null ? 'all' : $limit
        ];
        echo Ajax_Snippets_Table::render($meta, 'CSV Source');

        return $source_path;
}
