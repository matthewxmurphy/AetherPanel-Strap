<?php
declare(strict_types=1);

function aetherpanel_base_path(): string
{
    return dirname(__DIR__);
}

function aetherpanel_config_path(): string
{
    $override = trim((string)(getenv('AETHERPANEL_ETC') ?: ''));
    if ($override !== '') {
        return rtrim($override, '/');
    }
    return '/etc/aetherpanel';
}

function aetherpanel_node_env_path(): string
{
    return aetherpanel_config_path() . '/node.env';
}

function aetherpanel_node_env(): array
{
    static $env;

    if (is_array($env)) {
        return $env;
    }

    $env = aetherpanel_parse_env_file(aetherpanel_node_env_path());
    return $env;
}

function aetherpanel_state_path(): string
{
    $override = trim((string)(getenv('AETHERPANEL_VAR') ?: ''));
    if ($override === '') {
        $override = trim((string)(aetherpanel_node_env()['PANEL_VAR'] ?? ''));
    }

    if ($override !== '') {
        return rtrim($override, '/');
    }

    return '/var/lib/aetherpanel';
}

function aetherpanel_state_dir(): string
{
    return aetherpanel_state_path() . '/state';
}

function aetherpanel_state_file(string $filename): string
{
    return aetherpanel_state_dir() . '/' . ltrim($filename, '/');
}

function aetherpanel_config_file(string $filename): string
{
    return aetherpanel_config_path() . '/' . ltrim($filename, '/');
}

function aetherpanel_read_json_from_paths(array $paths, array $fallback = []): array
{
    foreach ($paths as $path) {
        $candidate = trim((string)$path);
        if ($candidate === '') {
            continue;
        }

        if (is_file($candidate)) {
            return aetherpanel_read_json($candidate, $fallback);
        }
    }

    return $fallback;
}

function aetherpanel_mutable_json_path(string $filename): string
{
    $statePath = aetherpanel_state_file($filename);
    if (is_file($statePath) || is_dir(dirname($statePath))) {
        return $statePath;
    }

    return aetherpanel_config_file($filename);
}

function aetherpanel_htpasswd_path(): string
{
    return aetherpanel_config_file('users.htpasswd');
}

function aetherpanel_parse_env_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $values[trim((string)$key)] = trim((string)$value);
    }

    return $values;
}

function aetherpanel_read_json(string $path, array $fallback = []): array
{
    if (!is_file($path)) {
        return $fallback;
    }

    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function aetherpanel_write_json(string $path, array $payload): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) || (!is_writable($dir) && !is_writable($path))) {
        return false;
    }

    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || $encoded === '') {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL) !== false;
}

function aetherpanel_default_branding(): array
{
    return [
        'project_name' => 'AetherPanel',
        'business_end' => 'AI Control Host',
        'owner' => 'Matthew Murphy',
        'organization_name' => 'Net30 Hosting',
        'support_url' => 'https://www.net30hosting.com/support',
        'ecommerce_url' => 'https://www.net30hosting.com',
        'billing_url' => 'https://billing.net30hosting.com',
        'system_email_from' => 'hello@net30hosting.com',
        'create_account_url' => 'https://www.net30hosting.com',
        'logout_destination_url' => 'https://www.net30hosting.com',
        'brand_color' => '#F8931F',
        'secondary_color' => '#111111',
        'font_family' => 'Noto Sans',
        'border_radius' => 18,
        'default_dark_mode' => true,
        'login_page_image' => '',
        'logo_light' => '',
        'logo_dark' => '',
        'logo_compact' => '',
        'favicon' => '',
        'inverse_icon' => '',
        'compact_mark_text' => 'N30',
        'public_domains' => [
            'www.net30hosting.com',
            'www.matthewxmurphy.com',
        ],
    ];
}

function aetherpanel_load_branding(): array
{
    $branding = aetherpanel_read_json_from_paths([
        aetherpanel_state_file('branding.json'),
        aetherpanel_config_file('branding.json'),
    ], []);
    return array_replace(aetherpanel_default_branding(), $branding);
}

function aetherpanel_default_access_model(): array
{
    return [
        'roles' => [
            [
                'id' => 'platform_admin',
                'label' => 'Platform Admin',
                'description' => 'Full control of branding, roles, nodes, migrations, and firewall posture.',
                'permissions' => [
                    'edit branding',
                    'manage users',
                    'manage roles',
                    'manage nodes',
                    'manage firewall posture',
                    'approve migrations',
                    'view billing summaries',
                ],
            ],
            [
                'id' => 'fleet_operator',
                'label' => 'Fleet Operator',
                'description' => 'Can manage nodes, vhosts, certificates, and migrations.',
                'permissions' => [
                    'manage nodes',
                    'manage vhosts',
                    'apply certificates',
                    'start migrations',
                ],
            ],
            [
                'id' => 'web_operator',
                'label' => 'Web Operator',
                'description' => 'Can manage sites, PHP packages, and jailed SFTP users.',
                'permissions' => [
                    'create vhosts',
                    'edit php packages',
                    'manage jailed sftp users',
                    'view vhost telemetry',
                ],
            ],
            [
                'id' => 'mail_operator',
                'label' => 'Mail Operator',
                'description' => 'Can manage outbound email posture and mail-role changes.',
                'permissions' => [
                    'manage outbound mail posture',
                    'move mail role',
                    'view mail host assignments',
                ],
            ],
            [
                'id' => 'dns_operator',
                'label' => 'DNS Operator',
                'description' => 'Can manage DNS role placement and DNS changes.',
                'permissions' => [
                    'manage dns role assignments',
                    'move dns role',
                    'view dns host assignments',
                ],
            ],
            [
                'id' => 'billing_viewer',
                'label' => 'Billing Viewer',
                'description' => 'Read-only access to bandwidth, disk, and billing summaries.',
                'permissions' => [
                    'view disk summaries',
                    'view bandwidth summaries',
                    'view billing summaries',
                ],
            ],
        ],
        'users' => [
            [
                'username' => 'admin',
                'display_name' => 'Bootstrap Admin',
                'roles' => ['platform_admin'],
            ],
        ],
    ];
}

function aetherpanel_load_access_model(): array
{
    $model = aetherpanel_read_json_from_paths([
        aetherpanel_state_file('users.json'),
        aetherpanel_config_file('users.json'),
    ], []);
    $defaults = aetherpanel_default_access_model();

    if (!isset($model['roles']) || !is_array($model['roles'])) {
        $model['roles'] = $defaults['roles'];
    }
    if (!isset($model['users']) || !is_array($model['users'])) {
        $model['users'] = $defaults['users'];
    }

    return $model;
}

function aetherpanel_access_model_path(): string
{
    return aetherpanel_mutable_json_path('users.json');
}

function aetherpanel_save_access_model(array $model): bool
{
    return aetherpanel_write_json(aetherpanel_access_model_path(), $model);
}

function aetherpanel_node_context(): array
{
    $env = aetherpanel_node_env();
    return [
        'node_name' => $env['NODE_NAME'] ?? php_uname('n'),
        'roles' => array_values(array_filter(array_map('trim', explode(',', (string)($env['ROLES'] ?? 'controller,web,database'))))),
        'controller_url' => $env['CONTROLLER_URL'] ?? 'https://my.net30hosting.com',
        'public_hostname' => $env['PUBLIC_HOSTNAME'] ?? '',
        'tailscale_ip' => $env['TAILSCALE_IP'] ?? '',
        'panel_port' => $env['PANEL_PORT'] ?? '8844',
        'version' => $env['AETHERPANEL_VERSION'] ?? '0.1.0',
    ];
}

function aetherpanel_current_username(): string
{
    $candidates = [
        $_SERVER['REMOTE_USER'] ?? '',
        $_SERVER['PHP_AUTH_USER'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $value = trim((string)$candidate);
        if ($value !== '') {
            return $value;
        }
    }

    return 'unknown';
}

function aetherpanel_current_user(array $accessModel): array
{
    $username = aetherpanel_current_username();
    foreach ($accessModel['users'] as $user) {
        if (!is_array($user)) {
            continue;
        }
        if (strcasecmp((string)($user['username'] ?? ''), $username) === 0) {
            return $user + ['username' => $username];
        }
    }

    return [
        'username' => $username,
        'display_name' => 'Unassigned User',
        'roles' => [],
    ];
}

function aetherpanel_roles_for_user(array $user, array $accessModel): array
{
    $wanted = array_map('strval', (array)($user['roles'] ?? []));
    $roles = [];
    foreach ($accessModel['roles'] as $role) {
        if (!is_array($role)) {
            continue;
        }
        $id = (string)($role['id'] ?? '');
        if ($id !== '' && in_array($id, $wanted, true)) {
            $roles[] = $role;
        }
    }
    return $roles;
}

function aetherpanel_permission_list(array $roles): array
{
    $permissions = [];
    foreach ($roles as $role) {
        foreach ((array)($role['permissions'] ?? []) as $permission) {
            $label = trim((string)$permission);
            if ($label !== '') {
                $permissions[$label] = true;
            }
        }
    }
    $labels = array_keys($permissions);
    sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    return $labels;
}

function aetherpanel_login_endpoint(array $node): string
{
    $ip = trim((string)($node['tailscale_ip'] ?? ''));
    $port = trim((string)($node['panel_port'] ?? '8844'));
    if ($ip === '') {
        return 'Tailscale bind pending';
    }
    return sprintf('http://%s:%s', $ip, $port);
}

function aetherpanel_bool_label(bool $value): string
{
    return $value ? 'Enabled' : 'Disabled';
}

function aetherpanel_default_onboarding(): array
{
    return [
        'title' => 'Your account setup',
        'subtitle' => 'Finish the first fleet steps before handing real websites and customers to AetherPanel.',
        'items' => [
            [
                'id' => 'register_controller_identity',
                'title' => 'Register controller identity',
                'summary' => 'Confirm my.net30hosting.com and the first controller node metadata.',
                'done' => false,
            ],
            [
                'id' => 'add_first_node',
                'title' => 'Add first node',
                'summary' => 'Track the first hybrid controller/app/database node in fleet inventory.',
                'done' => false,
            ],
            [
                'id' => 'confirm_tailscale_bind',
                'title' => 'Confirm Tailscale bind',
                'summary' => 'Keep the local lighttpd panel reachable only over the tailnet.',
                'done' => false,
            ],
            [
                'id' => 'lock_ssh_to_known_ips',
                'title' => 'Lock SSH to known IPs',
                'summary' => 'Restrict port 22 to Tailscale and approved known IP lanes.',
                'done' => false,
            ],
            [
                'id' => 'set_default_web_stack',
                'title' => 'Set default web stack',
                'summary' => 'Use Apache, PHP 8.5, website-local database access, Let’s Encrypt, msmtp, and jailed SFTP as the baseline.',
                'done' => false,
            ],
            [
                'id' => 'set_backup_target',
                'title' => 'Set backup target',
                'summary' => 'Point backups at Wasabi and verify restore posture.',
                'done' => false,
            ],
            [
                'id' => 'add_first_role_user',
                'title' => 'Add first role user',
                'summary' => 'Create the first operator beyond the bootstrap admin account.',
                'done' => false,
            ],
            [
                'id' => 'add_first_hosting_package',
                'title' => 'Add first hosting package',
                'summary' => 'Create the default package for shared and dedicated site placement.',
                'done' => false,
            ],
            [
                'id' => 'import_existing_sites',
                'title' => 'Import existing sites',
                'summary' => 'Start bringing current website inventory and external panel state into AetherPanel.',
                'done' => false,
            ],
        ],
    ];
}

function aetherpanel_onboarding_path(): string
{
    return aetherpanel_mutable_json_path('onboarding.json');
}

function aetherpanel_onboarding_flag_map(): array
{
    return [
        'lock_ssh_to_known_ips' => 'ssh_known_ip_lock',
        'set_backup_target' => 'backup_target_ready',
        'add_first_hosting_package' => 'hosting_package_ready',
        'import_existing_sites' => 'import_ready',
    ];
}

function aetherpanel_is_persisted_onboarding_step(string $stepId): bool
{
    return array_key_exists($stepId, aetherpanel_onboarding_flag_map());
}

function aetherpanel_set_onboarding_step_state(string $stepId, bool $done): bool
{
    $map = aetherpanel_onboarding_flag_map();
    $path = aetherpanel_onboarding_path();
    $config = aetherpanel_read_json($path, aetherpanel_default_onboarding());

    if (isset($map[$stepId])) {
        $config[$map[$stepId]] = $done;
    } else {
        $overrides = is_array($config['manual_overrides'] ?? null) ? $config['manual_overrides'] : [];
        $overrides[$stepId] = $done;
        $config['manual_overrides'] = $overrides;
    }

    if (isset($config['items']) && is_array($config['items'])) {
        foreach ($config['items'] as &$item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['id'] ?? '') === $stepId) {
                $item['done'] = $done;
                break;
            }
        }
        unset($item);
    }

    return aetherpanel_write_json($path, $config);
}

function aetherpanel_load_onboarding(array $node, array $branding, array $accessModel): array
{
    $seed = aetherpanel_default_onboarding();
    $config = aetherpanel_read_json(aetherpanel_onboarding_path(), []);
    $title = (string)($config['title'] ?? $seed['title']);
    $subtitle = (string)($config['subtitle'] ?? $seed['subtitle']);
    $items = is_array($config['items'] ?? null) ? $config['items'] : $seed['items'];
    $overrides = is_array($config['manual_overrides'] ?? null) ? $config['manual_overrides'] : [];

    $knownUsers = array_values(array_filter((array)($accessModel['users'] ?? []), static fn ($user): bool => is_array($user)));
    $hasExtraUser = count($knownUsers) > 1;
    $hasController = trim((string)($node['controller_url'] ?? '')) !== '';
    $hasTailnet = trim((string)($node['tailscale_ip'] ?? '')) !== '';
    $hasWebRole = in_array('web', array_map('strval', (array)($node['roles'] ?? [])), true);
    $hasBranding = trim((string)($branding['organization_name'] ?? '')) !== '' && trim((string)($branding['system_email_from'] ?? '')) !== '';

    $computed = [
        'register_controller_identity' => $hasController && $hasBranding,
        'add_first_node' => trim((string)($node['node_name'] ?? '')) !== '',
        'confirm_tailscale_bind' => $hasTailnet,
        'lock_ssh_to_known_ips' => (bool)($config['ssh_known_ip_lock'] ?? false),
        'set_default_web_stack' => $hasWebRole,
        'set_backup_target' => (bool)($config['backup_target_ready'] ?? false),
        'add_first_role_user' => $hasExtraUser,
        'add_first_hosting_package' => (bool)($config['hosting_package_ready'] ?? false),
        'import_existing_sites' => (bool)($config['import_ready'] ?? false),
    ];

    $resolved = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = trim((string)($item['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $resolved[] = [
            'id' => $id,
            'title' => (string)($item['title'] ?? $id),
            'summary' => (string)($item['summary'] ?? ''),
            'done' => array_key_exists($id, $computed)
                ? $computed[$id]
                : (array_key_exists($id, $overrides) ? (bool)$overrides[$id] : (bool)($item['done'] ?? false)),
            'persisted' => aetherpanel_is_persisted_onboarding_step($id),
        ];
    }

    return [
        'title' => $title,
        'subtitle' => $subtitle,
        'items' => $resolved,
    ];
}

function aetherpanel_onboarding_progress(array $onboarding): array
{
    $items = array_values(array_filter((array)($onboarding['items'] ?? []), static fn ($item): bool => is_array($item)));
    $total = count($items);
    $done = 0;
    foreach ($items as $item) {
        if (!empty($item['done'])) {
            $done++;
        }
    }

    return [
        'done' => $done,
        'total' => $total,
        'pending' => max(0, $total - $done),
    ];
}

function aetherpanel_username_is_valid(string $username): bool
{
    return (bool)preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,31}$/', $username);
}

function aetherpanel_random_password(int $length = 24): string
{
    $bytes = bin2hex(random_bytes((int)max(8, ceil($length / 2))));
    return substr($bytes, 0, $length);
}

function aetherpanel_role_ids(array $accessModel): array
{
    $ids = [];
    foreach ((array)($accessModel['roles'] ?? []) as $role) {
        if (!is_array($role)) {
            continue;
        }

        $id = trim((string)($role['id'] ?? ''));
        if ($id !== '') {
            $ids[$id] = true;
        }
    }

    return array_keys($ids);
}

function aetherpanel_write_htpasswd_entry(string $username, string $password): bool
{
    $path = aetherpanel_htpasswd_path();
    $dir = dirname($path);
    if ((!is_file($path) || !is_writable($path)) && !is_writable($dir)) {
        return false;
    }

    $lines = [];
    if (is_file($path)) {
        $existing = file($path, FILE_IGNORE_NEW_LINES);
        if (is_array($existing)) {
            foreach ($existing as $line) {
                $line = trim((string)$line);
                if ($line === '' || !str_contains($line, ':')) {
                    continue;
                }

                [$existingUser] = explode(':', $line, 2);
                if (strcasecmp(trim((string)$existingUser), $username) !== 0) {
                    $lines[] = $line;
                }
            }
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if (!is_string($hash) || $hash === '') {
        return false;
    }

    $lines[] = $username . ':' . $hash;
    $payload = implode(PHP_EOL, $lines) . PHP_EOL;

    return file_put_contents($path, $payload) !== false;
}

function aetherpanel_add_local_user(string $username, string $displayName, array $roleIds, string $password = ''): array
{
    $username = trim($username);
    $displayName = trim($displayName);
    $roleIds = array_values(array_unique(array_filter(array_map('trim', $roleIds))));

    if (!aetherpanel_username_is_valid($username)) {
        return ['ok' => false, 'message' => 'Use a simple username with letters, numbers, dot, dash, or underscore.'];
    }

    if ($displayName === '') {
        return ['ok' => false, 'message' => 'Display name is required.'];
    }

    $accessModel = aetherpanel_load_access_model();
    $validRoleIds = aetherpanel_role_ids($accessModel);
    $selectedRoles = array_values(array_intersect($roleIds, $validRoleIds));

    if ($selectedRoles === []) {
        return ['ok' => false, 'message' => 'Choose at least one role for the new user.'];
    }

    foreach ((array)($accessModel['users'] ?? []) as $user) {
        if (!is_array($user)) {
            continue;
        }

        if (strcasecmp((string)($user['username'] ?? ''), $username) === 0) {
            return ['ok' => false, 'message' => 'That username already exists in the local panel.'];
        }
    }

    $plainPassword = trim($password) !== '' ? trim($password) : aetherpanel_random_password();

    if (!aetherpanel_write_htpasswd_entry($username, $plainPassword)) {
        return ['ok' => false, 'message' => 'Could not update the local lighttpd password file yet.'];
    }

    $accessModel['users'][] = [
        'username' => $username,
        'display_name' => $displayName,
        'roles' => $selectedRoles,
    ];

    usort($accessModel['users'], static function (array $left, array $right): int {
        return strcasecmp((string)($left['username'] ?? ''), (string)($right['username'] ?? ''));
    });

    if (!aetherpanel_save_access_model($accessModel)) {
        return ['ok' => false, 'message' => 'The user password was written, but the role assignment file could not be saved.'];
    }

    return [
        'ok' => true,
        'message' => 'Local operator created.',
        'password' => $plainPassword,
        'username' => $username,
    ];
}
