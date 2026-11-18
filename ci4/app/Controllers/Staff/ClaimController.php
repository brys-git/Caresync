<?php

namespace App\Controllers\Staff;

use App\Controllers\BranchAdmin\ClaimController as BranchAdminClaimController;

/**
 * Staff file a claim for a plan holder of their own branch, exactly like the Branch Admin does
 * (same form, same rules, same quote). Staff are recorded as the person who filed it.
 */
class ClaimController extends BranchAdminClaimController
{
    protected function roleId(): int { return 3; }
    protected function roleLabel(): string { return 'Staff'; }
    protected function layout(): string { return 'layouts/staff'; }
    protected function fileClaimPath(): string { return 'staff/services/file-claim'; }
    protected function requestsPath(): string { return 'staff/services/requests'; }
    protected function holderPath(): string { return 'staff/client/view'; }
}
