<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Navigation for every role, in one place.
 *
 * Previously each role had its own hand-written sidebar partial, so adding a
 * page meant editing up to five files and the links drifted apart. Everything
 * now reads from here.
 *
 * Structure:
 *   role => [
 *     'label'  => shown under the CareSync wordmark
 *     'groups' => [ ['title' => ..., 'items' => [ ['key','label','icon','url','count'] ]] ]
 *   ]
 *
 * 'key'   is matched against $active_nav passed from the controller.
 * 'count' is a key looked up in $nav_counts (e.g. pending approvals).
 * 'capability', when set, hides the item unless that capability check
 * passes for the signed-in user - see partials/sidebar.php. Currently only
 * 'collect' is a real value (can_collect(), caresync_helper.php): a plain
 * Staff account without the collector flag shouldn't see a link that would
 * just 403 for them, since the actual gate stays the route's own
 * CollectorFilter regardless of whether this link is shown.
 */
class Nav extends BaseConfig
{
    public array $roles = [
        'admin' => [
            'label'  => 'System administrator',
            'groups' => [
                [
                    'title' => 'Overview',
                    'items' => [
                        ['key' => 'dashboard', 'label' => 'Dashboard',  'icon' => 'ti-layout-dashboard', 'url' => 'dashboard/admin'],
                    ],
                ],
                [
                    'title' => 'Members',
                    'items' => [
                        ['key' => 'approvals', 'label' => 'Registration approvals', 'icon' => 'ti-user-check', 'url' => 'admin/registration-approvals', 'count' => 'pending_approvals'],
                        ['key' => 'clients',   'label' => 'Plan holders',           'icon' => 'ti-users',      'url' => 'admin/client-management'],
                    ],
                ],
                [
                    'title' => 'Money',
                    'items' => [
                        ['key' => 'payments', 'label' => 'Payment monitoring', 'icon' => 'ti-cash',      'url' => 'admin/payment-monitoring'],
                        ['key' => 'reports',  'label' => 'Reports',            'icon' => 'ti-file-text', 'url' => 'admin/reports'],
                    ],
                ],
                [
                    'title' => 'Setup',
                    'items' => [
                        ['key' => 'branches', 'label' => 'Branches',           'icon' => 'ti-building-community', 'url' => 'admin/branch-management'],
                        ['key' => 'offers',   'label' => 'Packages & services', 'icon' => 'ti-package',            'url' => 'admin/catalog'],
                        ['key' => 'plans',    'label' => 'Plan builder',        'icon' => 'ti-clipboard-plus',     'url' => 'plan-builder'],
                        ['key' => 'users',    'label' => 'Users',               'icon' => 'ti-users',              'url' => 'admin/users'],
                    ],
                ],
            ],
        ],

        'branch_admin' => [
            'label'  => 'Branch administrator',
            'groups' => [
                [
                    'title' => 'Overview',
                    'items' => [
                        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'url' => 'dashboard/branch-admin'],
                    ],
                ],
                [
                    'title' => 'Members',
                    'items' => [
                        ['key' => 'clients',  'label' => 'Plan holders',     'icon' => 'ti-users',            'url' => 'branch-admin/client-management'],
                        ['key' => 'legacy',   'label' => 'Existing member records', 'icon' => 'ti-history',   'url' => 'branch-admin/existing-records'],
                        ['key' => 'id-verification', 'label' => 'ID review', 'icon' => 'ti-id-badge-2',       'url' => 'branch-admin/id-verification', 'count' => 'ids_needing_review'],
                        ['key' => 'requests', 'label' => 'Service requests', 'icon' => 'ti-clipboard-list',   'url' => 'branch-admin/service-package/requests', 'count' => 'pending_requests'],
                    ],
                ],
                [
                    'title' => 'Money',
                    'items' => [
                        ['key' => 'payments',    'label' => 'Payment tracking', 'icon' => 'ti-cash',      'url' => 'branch-admin/payment-tracking'],
                        // 'cash' (branch-admin/cash-payments) used to point
                        // at its own dedicated page (BranchAdmin\
                        // CashPaymentController) before that feature was
                        // retired - it wrote to free-text client_name with
                        // no plan_id, so recorded cash could never advance
                        // plans.months_paid (see 2026-09-15-040000_
                        // RetireCashPaymentRecords.php). This link WAS live
                        // and reachable via this Nav config, unlike what an
                        // earlier check against the dead, pre-CareSync-UI
                        // sidebar_branch_admin.php partial concluded - that
                        // partial was already unused by the time this repo
                        // reached the CareSync UI redesign, and checking it
                        // was the wrong signal for "is this reachable".
                        // branch-admin/cash-payments now redirects to
                        // Payment Tracking (the working cash/GCash entry
                        // point, PaymentTracking::recordCash()), so this
                        // item is removed rather than kept as a second link
                        // to the exact same destination.
                        ['key' => 'collections', 'label' => 'Collection list', 'icon' => 'ti-map-pin',   'url' => 'branch-admin/collection-list'],
                        ['key' => 'collector-assignments', 'label' => 'Collector assignments', 'icon' => 'ti-map-pin-pin', 'url' => 'branch-admin/collector-assignments'],
                        ['key' => 'gcash-review', 'label' => 'GCash review', 'icon' => 'ti-brand-paypal', 'url' => 'gcash-review', 'count' => 'gcash_waiting'],
                        ['key' => 'closure-requests', 'label' => 'Closure requests', 'icon' => 'ti-door-exit', 'url' => 'branch-admin/closure-requests', 'count' => 'closure_requests'],
                        ['key' => 'remittances', 'label' => 'Remittances', 'icon' => 'ti-receipt-2', 'url' => 'branch-admin/remittances', 'count' => 'pending_remittances'],
                        ['key' => 'reports',     'label' => 'Reports',         'icon' => 'ti-file-text', 'url' => 'branch-admin/reports'],
                    ],
                ],
                [
                    'title' => 'Branch',
                    'items' => [
                        ['key' => 'service-board', 'label' => 'Service board', 'icon' => 'ti-layout-kanban', 'url' => 'branch-admin/service-package/ongoing', 'count' => 'service_attention'],
                        ['key' => 'offers', 'label' => 'Services & packages', 'icon' => 'ti-package',       'url' => 'branch-admin/service-package'],
                        ['key' => 'plans',  'label' => 'Plan builder',        'icon' => 'ti-clipboard-plus', 'url' => 'plan-builder'],
                        ['key' => 'staff',  'label' => 'Staff monitoring',    'icon' => 'ti-id-badge',      'url' => 'branch-admin/staff-monitoring'],
                        ['key' => 'create-account', 'label' => 'Create Account', 'icon' => 'ti-user-circle', 'url' => 'branch-admin/create-account'],
                    ],
                ],
            ],
        ],

        'staff' => [
            'label'  => 'Branch staff',
            'groups' => [
                [
                    'title' => 'Daily work',
                    'items' => [
                        ['key' => 'dashboard',    'label' => 'Dashboard',        'icon' => 'ti-layout-dashboard', 'url' => 'dashboard/staff'],
                        ['key' => 'clients',      'label' => 'Plan holders',     'icon' => 'ti-users',            'url' => 'staff/client-management'],
                        ['key' => 'legacy',       'label' => 'Existing member records', 'icon' => 'ti-history',   'url' => 'staff/existing-records'],
                        ['key' => 'id-verification', 'label' => 'ID review',     'icon' => 'ti-id-badge-2',       'url' => 'staff/id-verification', 'count' => 'ids_needing_review'],
                        ['key' => 'payments',     'label' => 'Payments',         'icon' => 'ti-cash',             'url' => 'staff/payment-management'],
                        ['key' => 'services',     'label' => 'Services',         'icon' => 'ti-clipboard-list',   'url' => 'staff/services', 'count' => 'pending_requests'],
                        ['key' => 'my-tasks',     'label' => 'My tasks',         'icon' => 'ti-checklist',        'url' => 'staff/my-tasks', 'count' => 'my_tasks'],
                        ['key' => 'gcash-review', 'label' => 'GCash review',     'icon' => 'ti-brand-paypal',     'url' => 'gcash-review', 'count' => 'gcash_waiting', 'capability' => 'collect'],
                        // Hidden unless this Staff account is also flagged
                        // users.is_collector - most Staff aren't, and a
                        // visible link that 403s for them is worse than no
                        // link. See the 'capability' doc comment above.
                        ['key' => 'collections',  'label' => 'Collection list',  'icon' => 'ti-map-pin',          'url' => 'collector/collection-list', 'capability' => 'collect'],
                        ['key' => 'remit',        'label' => 'Remit collections', 'icon' => 'ti-receipt-2',        'url' => 'collector/remit',           'capability' => 'collect'],
                        ['key' => 'commission',   'label' => 'My commission',    'icon' => 'ti-coin',             'url' => 'collector/commission',      'capability' => 'collect'],
                        ['key' => 'gcash',        'label' => 'My GCash account', 'icon' => 'ti-qrcode',           'url' => 'collector/gcash-account',   'capability' => 'collect'],
                    ],
                ],
                [
                    'title' => 'Records',
                    'items' => [
                        ['key' => 'plans',   'label' => 'Plan builder', 'icon' => 'ti-clipboard-plus', 'url' => 'plan-builder'],
                        ['key' => 'create-account', 'label' => 'Create Account', 'icon' => 'ti-user-circle', 'url' => 'staff/create-account'],
                        ['key' => 'reports', 'label' => 'Reports',      'icon' => 'ti-file-text',      'url' => 'staff/reports'],
                    ],
                ],
            ],
        ],

        'collector' => [
            'label'  => 'Field collector',
            'groups' => [
                [
                    'title' => 'Collections',
                    'items' => [
                        // A dedicated collector/remittance view still
                        // doesn't exist - a collector's own recorded cash
                        // shows up in Branch Admin's existing remittance
                        // report instead (see branch_admin/reports/
                        // remittance.php, extended with a Status column and
                        // an Unverified total for this same feature).
                        ['key' => 'dashboard',    'label' => "Today's route",   'icon' => 'ti-route',   'url' => 'dashboard/collector'],
                        ['key' => 'collections',  'label' => 'Collection list', 'icon' => 'ti-map-pin', 'url' => 'collector/collection-list'],
                        ['key' => 'gcash-review', 'label' => 'GCash review',  'icon' => 'ti-brand-paypal', 'url' => 'gcash-review', 'count' => 'gcash_waiting'],
                        ['key' => 'remit',        'label' => 'Remit collections', 'icon' => 'ti-receipt-2', 'url' => 'collector/remit'],
                        ['key' => 'commission',   'label' => 'My commission',   'icon' => 'ti-coin',    'url' => 'collector/commission'],
                        ['key' => 'gcash',        'label' => 'My GCash account', 'icon' => 'ti-qrcode', 'url' => 'collector/gcash-account'],
                    ],
                ],
            ],
        ],

        'plan_holder' => [
            'label'  => 'Plan holder',
            'groups' => [
                [
                    'title' => 'My plan',
                    'items' => [
                        ['key' => 'dashboard',  'label' => 'Overview',   'icon' => 'ti-home',         'url' => 'client/dashboard'],
                        ['key' => 'membership', 'label' => 'My plan',    'icon' => 'ti-certificate',  'url' => 'client/membership'],
                        ['key' => 'payments',   'label' => 'Payments',   'icon' => 'ti-cash',         'url' => 'client/payment'],
                    ],
                ],
                [
                    'title' => 'Services & Packages',
                    'items' => [
                        ['key' => 'services',      'label' => 'Request a service', 'icon' => 'ti-clipboard-list', 'url' => 'client/service', 'capability' => 'open_plan'],
                        ['key' => 'claims',        'label' => 'My claims',         'icon' => 'ti-file-description', 'url' => 'client/claims'],
                        ['key' => 'notifications', 'label' => 'Notifications',     'icon' => 'ti-bell',           'url' => 'client/notification', 'count' => 'unread'],
                    ],
                ],
            ],
        ],
    ];

    /** Fallback so an unknown role still renders a usable shell. */
    public array $fallback = [
        'label'  => 'CareSync',
        'groups' => [
            ['title' => '', 'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ti-layout-dashboard', 'url' => 'dashboard'],
            ]],
        ],
    ];

    public function for(string $role): array
    {
        return $this->roles[$role] ?? $this->fallback;
    }
}
