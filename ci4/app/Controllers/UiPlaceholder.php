<?php

namespace App\Controllers;

class UiPlaceholder extends BaseController
{
    public function admin(string $module): string
    {
        return $this->render('layouts/admin', 'System Admin', $module);
    }

    public function branchAdmin(string $module): string
    {
        return $this->render('layouts/branch_admin', 'Branch Admin', $module);
    }

    public function staff(string $module): string
    {
        return $this->render('layouts/staff', 'Staff', $module);
    }

    public function planHolder(string $module): string
    {
        return $this->render('layouts/plan_holder', 'Plan Holder', $module);
    }

    private function render(string $layout, string $roleLabel, string $module): string
    {
        $moduleLabel = ucwords(str_replace('-', ' ', trim($module)));

        return view('placeholders/module', [
            'role_layout' => $layout,
            'role_label' => $roleLabel,
            'module_label' => $moduleLabel,
            'breadcrumb' => [$moduleLabel],
        ]);
    }
}
