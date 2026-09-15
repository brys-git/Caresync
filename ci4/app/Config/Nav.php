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
                        ['key' => 'analytics', 'label' => 'Analytics',  'icon' => 'ti-chart-histogram',  'url' => 'admin/analytics'],
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
                        ['key' => 'offers',   'label' => 'Services & packages', 'icon' => 'ti-package',            'url' => 'admin/service-offer'],
                        ['key' => 'plans',    'label' => 'Plan builder',        'icon' => 'ti-clipboard-plus',     'url' => 'packages'],
                        ['key' => 'users',    'label' => 'User accounts',       'icon' => 'ti-user-plus',          'url' => 'users/create'],
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
                        ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'ti-chart-histogram',  'url' => 'branch-admin/analytics'],
                    ],
                ],
                [
                    'title' => 'Members',
                    'items' => [
                        ['key' => 'clients',  'label' => 'Plan holders',     'icon' => 'ti-users',            'url' => 'branch-admin/client-management'],
                        ['key' => 'requests', 'label' => 'Service requests', 'icon' => 'ti-clipboard-list',   'url' => 'branch-admin/service-applications', 'count' => 'pending_requests'],
                    ],
                ],
                [
                    'title' => 'Money',
                    'items' => [
                        ['key' => 'payments', 'label' => 'Payment tracking', 'icon' => 'ti-cash',        'url' => 'branch-admin/payment-tracking'],
                        ['key' => 'cash',     'label' => 'Cash payments',    'icon' => 'ti-coins',       'url' => 'branch-admin/cash-payments'],
                        ['key' => 'reports',  'label' => 'Reports',          'icon' => 'ti-file-text',   'url' => 'branch-admin/reports'],
                    ],
                ],
                [
                    'title' => 'Branch',
                    'items' => [
                        ['key' => 'offers', 'label' => 'Services & packages', 'icon' => 'ti-package',       'url' => 'branch-admin/service-package'],
                        ['key' => 'plans',  'label' => 'Plan builder',        'icon' => 'ti-clipboard-plus', 'url' => 'packages'],
                        ['key' => 'staff',  'label' => 'Staff monitoring',    'icon' => 'ti-id-badge',      'url' => 'branch-admin/staff-monitoring'],
                        ['key' => 'users',  'label' => 'User accounts',       'icon' => 'ti-user-plus',     'url' => 'users/create'],
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
                        ['key' => 'dashboard', 'label' => 'Dashboard',    'icon' => 'ti-layout-dashboard', 'url' => 'dashboard/staff'],
                        ['key' => 'clients',   'label' => 'Plan holders', 'icon' => 'ti-users',            'url' => 'staff/client-management'],
                        ['key' => 'payments',  'label' => 'Payments',     'icon' => 'ti-cash',             'url' => 'staff/payment-management'],
                        ['key' => 'services',  'label' => 'Services',     'icon' => 'ti-clipboard-list',   'url' => 'staff/services', 'count' => 'pending_requests'],
                    ],
                ],
                [
                    'title' => 'Records',
                    'items' => [
                        ['key' => 'plans',   'label' => 'Plan builder', 'icon' => 'ti-clipboard-plus', 'url' => 'packages'],
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
                        // 'payments' (collector/payments) and 'remittance'
                        // (collector/remittance) are deliberately not here -
                        // no controller, view, or route exists for either yet.
                        // Routes/collector.php's own comment says this is
                        // intentional: Collector only got a login+dashboard
                        // in this phase, entry/remittance screens are built
                        // once the payment flow they depend on is reworked.
                        // Add them back here when that phase lands.
                        ['key' => 'dashboard', 'label' => "Today's route", 'icon' => 'ti-route', 'url' => 'dashboard/collector'],
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
                        ['key' => 'services',      'label' => 'Request a service', 'icon' => 'ti-clipboard-list', 'url' => 'client/service'],
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
