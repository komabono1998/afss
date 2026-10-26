<?php
/**
 * Role-based access control for the admin panel.
 * A role grants access to modules; some roles are further limited ("scoped") to certain
 * categories of content, e.g. the Sports Tutor can only manage Sports news and events.
 */

const ADMIN_MODULES = [
    'slides' => 'Hero slides', 'pages' => 'Pages', 'news' => 'News & stories', 'issues' => 'Monthly newsletter',
    'events' => 'Events', 'albums' => 'Photo albums', 'team' => 'Management profiles',
    'jobs' => 'Job vacancies', 'job_apps' => 'Job applications',
    'admissions' => 'Admission applications', 'scholarships' => 'Scholarships & bursaries', 'scholarship_apps' => 'Scholarship applications',
    'messages' => 'Contact messages', 'newsletter' => 'Subscribers & email',
    'population' => 'School population', 'settings' => 'Site settings', 'users' => 'Admin users',
];

function roles(): array
{
    return [
        'admin' => [
            'label' => 'Administrator',
            'desc' => 'Full access, including admin users and site settings.',
            'modules' => array_keys(ADMIN_MODULES),
        ],
        'headteacher' => [
            'label' => 'Headteacher',
            'desc' => 'Manages all school content, applications and messages. Cannot manage admin users.',
            'modules' => array_values(array_diff(array_keys(ADMIN_MODULES), ['users'])),
        ],
        'deputy_academics' => [
            'label' => 'Deputy Headteacher (Academics)',
            'desc' => 'Academic and About pages, news, events, photo albums, admissions and scholarship applications.',
            'modules' => ['pages', 'news', 'events', 'albums', 'admissions', 'scholarships', 'scholarship_apps', 'population'],
            'scope' => ['pages' => ['menu_group', ['academics', 'about']]],
        ],
        'deputy_admin' => [
            'label' => 'Deputy Headteacher (Administration)',
            'desc' => 'Management profiles, job vacancies and applications, contact messages, events, news and student-life pages.',
            'modules' => ['pages', 'news', 'events', 'albums', 'team', 'jobs', 'job_apps', 'messages', 'admissions', 'population'],
            'scope' => ['pages' => ['menu_group', ['about', 'students', 'none']]],
        ],
        'bursar' => [
            'label' => 'Bursar',
            'desc' => 'Fees and admissions pages, scholarships and bursaries, and their applications.',
            'modules' => ['pages', 'scholarships', 'scholarship_apps', 'admissions'],
            'scope' => ['pages' => ['menu_group', ['admissions']]],
        ],
        'sports_tutor' => [
            'label' => 'Sports Tutor',
            'desc' => 'Sports news, sports events and sports photo albums only.',
            'modules' => ['news', 'events', 'albums'],
            'scope' => ['news' => ['category', ['Sports']], 'events' => ['category', ['Sports']], 'albums' => ['category', 'sport']],
        ],
        'newsletter_editor' => [
            'label' => 'Newsletter Editor',
            'desc' => 'Monthly newsletter issues, subscriber emails, news and photo albums.',
            'modules' => ['issues', 'newsletter', 'news', 'albums'],
        ],
    ];
}
function role_label(string $role): string { return roles()[$role]['label'] ?? ucfirst($role); }

function can(string $module): bool
{
    $u = admin_user();
    if (!$u) return false;
    return in_array($module, roles()[$u['role']]['modules'] ?? [], true);
}
function deny(): never
{
    http_response_code(403);
    admin_head('Access denied');
    echo '<div class="card"><h2>You do not have permission to open this section</h2><p class="muted">Your role is <strong>'
        . e(role_label(admin_user()['role'] ?? '')) . '</strong>. Ask an Administrator if you need access.</p><a class="btn" href="index.php">Back to dashboard</a></div>';
    admin_foot();
    exit;
}
function require_perm(string $module): void { if (!can($module)) deny(); }

/**
 * Content limits for the signed-in user's role in a module.
 * Returns null (no limit) or ['col' => column, 'values' => [allowed values]].
 */
function scope_for(string $module): ?array
{
    $u = admin_user();
    $spec = roles()[$u['role'] ?? '']['scope'][$module] ?? null;
    if (!$spec) return null;
    [$col, $vals] = $spec;
    if (is_string($vals)) { // substring match against the configurable album/event categories
        $vals = array_values(array_filter(gallery_categories(), fn($c) => stripos($c, $vals) !== false)) ?: ['Sports Day'];
    }
    return ['col' => $col, 'values' => $vals];
}
function in_scope(?array $scope, ?array $record): bool
{
    return !$scope || ($record && in_array($record[$scope['col']] ?? '', $scope['values'], true));
}
function scope_sql(?array $scope): array
{
    if (!$scope) return ['', []];
    return [' AND ' . $scope['col'] . ' IN (' . implode(',', array_fill(0, count($scope['values']), '?')) . ')', $scope['values']];
}
