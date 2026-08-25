<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Integrations;

final class WebhookClient
{
    /**
     * @param array<string,mixed> $payload
     * @return array{success:bool,request_id:string,status:int,mock:bool,message:string}
     */
    public function send(array $payload): array
    {
        $endpoint = $this->endpoint();
        $request_id = wp_generate_uuid4();
        if ($endpoint === '') {
            $this->log('info', 'Mock CRM accepted consultation.', $request_id, 202);
            return ['success' => true, 'request_id' => $request_id, 'status' => 202, 'mock' => true, 'message' => 'Mock CRM accepted the request.'];
        }

        $token = (string) (defined('JP_CRM_WEBHOOK_TOKEN') ? JP_CRM_WEBHOOK_TOKEN : getenv('JP_CRM_WEBHOOK_TOKEN'));
        $delays = [0, 100000, 250000];
        $last_status = 0;
        $last_message = 'CRM request failed.';

        foreach ($delays as $attempt => $delay) {
            if ($delay > 0) {
                usleep($delay);
            }
            $headers = ['Content-Type' => 'application/json', 'X-JusticePoint-Request-ID' => $request_id];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            $response = wp_safe_remote_post(
                $endpoint,
                [
                    'timeout' => 8,
                    'redirection' => 0,
                    'headers' => $headers,
                    'body' => wp_json_encode($payload),
                    'data_format' => 'body',
                ]
            );
            if (is_wp_error($response)) {
                $last_message = $response->get_error_message();
                continue;
            }
            $last_status = (int) wp_remote_retrieve_response_code($response);
            if ($last_status >= 200 && $last_status < 300) {
                $this->log('info', 'CRM accepted consultation.', $request_id, $last_status);
                return ['success' => true, 'request_id' => $request_id, 'status' => $last_status, 'mock' => false, 'message' => 'CRM accepted the request.'];
            }
            $last_message = sprintf('CRM returned HTTP %d on attempt %d.', $last_status, $attempt + 1);
            if ($last_status > 0 && $last_status < 429) {
                break;
            }
        }

        $this->log('error', 'CRM delivery failed after retries.', $request_id, $last_status);
        return ['success' => false, 'request_id' => $request_id, 'status' => $last_status, 'mock' => false, 'message' => $last_message];
    }

    private function endpoint(): string
    {
        $endpoint = (string) (defined('JP_CRM_WEBHOOK_URL') ? JP_CRM_WEBHOOK_URL : getenv('JP_CRM_WEBHOOK_URL'));
        $validated = wp_http_validate_url($endpoint);
        return $validated ? $validated : '';
    }

    private function log(string $level, string $message, string $request_id, int $status): void
    {
        error_log(
            wp_json_encode(
                [
                    'component'  => 'justicepoint_crm',
                    'level'      => $level,
                    'message'    => $message,
                    'request_id' => $request_id,
                    'status'     => $status,
                ],
                JSON_UNESCAPED_SLASHES
            )
        );
    }
}

