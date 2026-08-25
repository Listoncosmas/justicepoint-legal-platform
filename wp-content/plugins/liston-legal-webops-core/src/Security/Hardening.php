<?php

declare(strict_types=1);

namespace Liston\LegalWebOps\Security;

final class Hardening
{
    public function register(): void
    {
        add_filter('rest_pre_dispatch', [$this, 'restrict_user_routes'], 10, 3);
    }

    public function restrict_user_routes(mixed $result, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        unset($server);
        if (! is_user_logged_in() && preg_match('#^/wp/v2/users(?:/|$)#', $request->get_route())) {
            return new \WP_Error(
                'rest_not_logged_in',
                'Authentication is required to access user records.',
                ['status' => rest_authorization_required_code()]
            );
        }
        return $result;
    }
}
