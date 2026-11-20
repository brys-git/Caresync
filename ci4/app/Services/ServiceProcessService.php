<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * What happens to a service after it is approved - the ONE place for the rules.
 *
 *   Stages (services.status):  pending (= approved) -> scheduled -> preparing -> ongoing -> burial_day -> completed -> closed
 *                              (cancelled is the exit). Stages move forward automatically:
 *     scheduled   the Branch Admin saved the schedule (wake, burial, cemetery) and the family contact
 *     preparing   at least one task is assigned to a staff member
 *     ongoing     the first task is started (only when the down payment is cleared or there is none)
 *     burial_day  the burial date has arrived (from ongoing)
 *     completed   the Branch Admin records the family's confirmation (all tasks must be done)
 *     closed      the Branch Admin closes the finished service
 *   "Ready to complete" is not a stage: it is the state "every task is done" while the service is ongoing / burial day.
 *
 *   Tasks (service_tasks) are built from the package's inclusions (+ add-ons) with a template per inclusion, and each one is
 *   assigned to a staff member of the branch. A staff member can only touch their OWN tasks of THEIR branch.
 *
 * Uploads live under writable/ (never public), images / PDF only, 5 MB at most, served through a controller that checks who asks.
 */
class ServiceProcessService
{
    public const STAGES = [
        'pending'    => 'Approved',
        'scheduled'  => 'Scheduled',
        'preparing'  => 'Preparing',
        'ongoing'    => 'Ongoing',
        'burial_day' => 'Burial day',
        'completed'  => 'Completed',
        'closed'     => 'Closed',
        'cancelled'  => 'Cancelled',
    ];

    /** Stages in which a service is still being worked on. */
    public const OPEN = ['pending', 'scheduled', 'preparing', 'ongoing', 'burial_day'];

    public const TASK_STATUS = ['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done', 'problem' => 'Problem'];

    public const MAX_UPLOAD = 5 * 1024 * 1024;

    private const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function ready(): bool
    {
        $db = db_connect();

        return $db->tableExists('service_plans') && $db->tableExists('service_tasks');
    }

    public static function stageLabel(string $status): string
    {
        return self::STAGES[$status] ?? ucfirst($status);
    }

    // ------------------------------------------------------------------ reading

    /** The service with who it is for, the deceased, the package and where it is in the process. */
    public function service(int $serviceId, ?int $branchId = null): ?array
    {
        $b = db_connect()->table('services s')
            ->select('s.*, ph.user_id AS client_user_id, ph.unique_identifier, u.first_name, u.last_name, p.package_name, sl.service_name, sa.application_id, sa.deceased_name, sa.deceased_address, sa.relationship_to_deceased, sa.beneficiary_name, sa.beneficiary_contact, sa.burial_attire_selected')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('packages p', 'p.package_id = s.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->join('service_applications sa', 'sa.service_id = s.service_id', 'left')
            ->where('s.service_id', $serviceId)->where('s.deleted_at', null);
        if ($branchId !== null) {
            $b->where('s.branch_id', $branchId);
        }
        $row = $b->get(1)->getRowArray();
        if ($row === null) {
            return null;
        }
        $row['client_name'] = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
        $row['what'] = (string) ($row['package_name'] ?? $row['service_name'] ?? 'Service');
        $row['stage_label'] = self::stageLabel((string) $row['status']);

        return $row;
    }

    public function plan(int $serviceId): array
    {
        $row = self::ready() ? db_connect()->table('service_plans')->where('service_id', $serviceId)->get(1)->getRowArray() : null;

        return $row ?: [];
    }

    /** @return list<array<string,mixed>> */
    public function tasks(int $serviceId): array
    {
        if (! self::ready()) {
            return [];
        }
        $rows = db_connect()->table('service_tasks t')
            ->select('t.*, st.first_name AS staff_first, st.last_name AS staff_last')
            ->join('users st', 'st.user_id = t.assigned_staff_id', 'left')
            ->where('t.service_id', $serviceId)
            ->orderBy('t.sort_order', 'ASC')->orderBy('t.task_id', 'ASC')
            ->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$r) {
            $r['staff_name'] = trim((string) $r['staff_first'] . ' ' . (string) $r['staff_last']);
            $r['status_label'] = self::TASK_STATUS[$r['status']] ?? $r['status'];
            $r['overdue'] = $r['due_at'] !== null && in_array($r['status'], ['todo', 'in_progress'], true) && strcmp((string) $r['due_at'], $now) < 0;
        }
        unset($r);

        return $rows;
    }

    public function task(int $taskId): ?array
    {
        $row = db_connect()->table('service_tasks')->where('task_id', $taskId)->get(1)->getRowArray();

        return $row ?: null;
    }

    /** Every task is done (and there is at least one). */
    public function allTasksDone(int $serviceId): bool
    {
        $t = $this->tasks($serviceId);

        return $t !== [] && count(array_filter($t, static fn (array $x): bool => $x['status'] !== 'done')) === 0;
    }

    /** "Ready to complete": every task done while the service is ongoing or on its burial day. */
    public function readyToComplete(array $service): bool
    {
        return in_array($service['status'], ['ongoing', 'burial_day'], true) && $this->allTasksDone((int) $service['service_id']);
    }

    /** A link to Google Maps for an address (or empty). */
    public static function mapsUrl(?string $address): string
    {
        $a = trim((string) $address);

        return $a === '' ? '' : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($a);
    }

    // ------------------------------------------------------------------ after approval

    /** Called when a request is approved and its service row exists: the plan row and the task list are created. */
    public function onApproved(int $serviceId, int $actorUserId): void
    {
        if (! self::ready() || $serviceId <= 0) {
            return;
        }
        $this->ensurePlan($serviceId, $actorUserId);
        $this->generateTasks($serviceId, $actorUserId);
    }

    public function ensurePlan(int $serviceId, int $actorUserId = 0): void
    {
        $db = db_connect();
        if ($db->table('service_plans')->where('service_id', $serviceId)->countAllResults() > 0) {
            return;
        }
        $app = $db->table('service_applications')->select('beneficiary_name, beneficiary_contact, relationship_to_deceased, deceased_address, claimant_name, claimant_relationship')->where('service_id', $serviceId)->get(1)->getRowArray() ?: [];
        $now = date('Y-m-d H:i:s');
        // The family contact starts from what the request already says (the Branch Admin can correct it).
        $db->table('service_plans')->insert([
            'service_id' => $serviceId,
            'family_name' => trim((string) (($app['claimant_name'] ?? '') !== '' ? $app['claimant_name'] : ($app['beneficiary_name'] ?? ''))) ?: null,
            'family_mobile' => trim((string) ($app['beneficiary_contact'] ?? '')) ?: null,
            'family_relationship' => trim((string) (($app['claimant_relationship'] ?? '') !== '' ? $app['claimant_relationship'] : ($app['relationship_to_deceased'] ?? ''))) ?: null,
            'wake_address' => trim((string) ($app['deceased_address'] ?? '')) ?: null,
            'updated_by' => $actorUserId ?: null, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /**
     * Builds the checklist from what the service includes: one task per package inclusion (package_items, using the template
     * whose match_text starts the inclusion's name), one for the burial-attire add-on, or - for a service that is not a
     * package - one task for the service itself. Safe to run again: inclusions that already have a task are skipped.
     *
     * @return int tasks created
     */
    public function generateTasks(int $serviceId, int $actorUserId = 0): int
    {
        $db = db_connect();
        $svc = $db->table('services')->where('service_id', $serviceId)->get(1)->getRowArray();
        if (! $svc) {
            return 0;
        }
        $templates = $db->table('service_task_templates')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();
        $match = static function (string $itemName) use ($templates): ?array {
            $n = strtolower(trim($itemName));
            foreach ($templates as $t) {
                if (str_starts_with($n, strtolower((string) $t['match_text'])) || str_contains($n, strtolower((string) $t['match_text']))) {
                    return $t;
                }
            }

            return null;
        };

        $existing = array_map(static fn (array $r): string => strtolower((string) $r['name']), $db->table('service_tasks')->select('name')->where('service_id', $serviceId)->get()->getResultArray());
        $sort = (int) ($db->table('service_tasks')->selectMax('sort_order', 'm')->where('service_id', $serviceId)->get()->getRowArray()['m'] ?? 0);
        $now = date('Y-m-d H:i:s');
        $made = 0;
        $seenTemplates = [];
        $add = function (string $name, ?string $instr, int $doc, string $source, ?int $tpl) use ($db, $serviceId, &$sort, &$made, &$existing, $now, $actorUserId): void {
            if (in_array(strtolower($name), $existing, true)) {
                return;
            }
            $sort += 10;
            $db->table('service_tasks')->insert([
                'service_id' => $serviceId, 'name' => mb_substr($name, 0, 150), 'instructions' => $instr, 'sort_order' => $sort, 'status' => 'todo',
                'requires_document' => $doc, 'source' => $source, 'template_id' => $tpl, 'created_by' => $actorUserId ?: null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $existing[] = strtolower($name);
            $made++;
        };

        if ((int) ($svc['package_id'] ?? 0) > 0) {
            $items = $db->table('package_items')->where('package_id', (int) $svc['package_id'])->orderBy('item_id')->get()->getResultArray();
            foreach ($items as $it) {
                $t = $match((string) $it['item_name']);
                if ($t !== null && isset($seenTemplates[(int) $t['template_id']])) {
                    continue; // "Viewing Setup: Lights / Curtains / Registry Stand" is ONE task, not three
                }
                if ($t !== null) {
                    $seenTemplates[(int) $t['template_id']] = true;
                }
                $add($t !== null ? (string) $t['name'] : (string) $it['item_name'], $t !== null ? (string) $t['instructions'] : (trim((string) ($it['description'] ?? '')) ?: null), $t !== null ? (int) $t['requires_document'] : 0, 'package', $t !== null ? (int) $t['template_id'] : null);
            }
            $attire = (int) ($db->table('service_applications')->select('burial_attire_selected')->where('service_id', $serviceId)->get(1)->getRowArray()['burial_attire_selected'] ?? 0) === 1;
            if ($attire) {
                $t = $match('burial attire');
                $add($t !== null ? (string) $t['name'] : 'Prepare the burial attire', $t !== null ? (string) $t['instructions'] : null, 0, 'addon', $t !== null ? (int) $t['template_id'] : null);
            }
        } elseif ((int) ($svc['service_list_id'] ?? 0) > 0) {
            $sl = $db->table('service_list')->select('service_name, description')->where('service_list_id', (int) $svc['service_list_id'])->get(1)->getRowArray() ?: [];
            $add((string) ($sl['service_name'] ?? 'Provide the service'), trim((string) ($sl['description'] ?? '')) ?: null, 0, 'package', null);
        }
        $addons = $db->table('package_avails')->select('addons_label')->where('service_id', $serviceId)->get(1)->getRowArray();
        if ($addons && trim((string) ($addons['addons_label'] ?? '')) !== '' && stripos((string) $addons['addons_label'], 'attire') === false) {
            $add('Add-on: ' . trim((string) $addons['addons_label']), 'Add-on chosen by the family.', 0, 'addon', null);
        }

        return $made;
    }

    // ------------------------------------------------------------------ the plan (schedule + family)

    /**
     * Saves the schedule and the family contact. When wake, burial, cemetery and the family contact are all filled in, an
     * approved service becomes 'scheduled'.
     *
     * @param array<string,mixed> $in
     *
     * @return array{ok:bool, message:string}
     */
    public function savePlan(int $serviceId, int $branchId, int $actorUserId, array $in): array
    {
        $svc = $this->service($serviceId, $branchId);
        if ($svc === null) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if (! in_array($svc['status'], self::OPEN, true)) {
            return ['ok' => false, 'message' => 'This service is already ' . strtolower(self::stageLabel((string) $svc['status'])) . '; the plan can no longer be changed.'];
        }
        $dt = static function (?string $v): ?string {
            $v = trim((string) $v);
            if ($v === '') {
                return null;
            }
            $ts = strtotime(str_replace('T', ' ', $v));

            return $ts === false ? '' : date('Y-m-d H:i:s', $ts);
        };
        $txt = static fn (?string $v, int $max): ?string => ($s = trim((string) $v)) === '' ? null : mb_substr($s, 0, $max);

        $row = [
            'pickup_at' => $dt($in['pickup_at'] ?? null), 'pickup_place' => $txt($in['pickup_place'] ?? null, 255),
            'wake_start' => $dt($in['wake_start'] ?? null), 'wake_end' => $dt($in['wake_end'] ?? null), 'wake_address' => $txt($in['wake_address'] ?? null, 255),
            'burial_at' => $dt($in['burial_at'] ?? null), 'cemetery' => $txt($in['cemetery'] ?? null, 255),
            'family_name' => $txt($in['family_name'] ?? null, 150), 'family_mobile' => $txt($in['family_mobile'] ?? null, 30), 'family_relationship' => $txt($in['family_relationship'] ?? null, 100),
        ];
        foreach (['pickup_at', 'wake_start', 'wake_end', 'burial_at'] as $k) {
            if ($row[$k] === '') {
                return ['ok' => false, 'message' => 'One of the dates or times is not valid.'];
            }
        }
        if ($row['wake_start'] && $row['wake_end'] && strcmp($row['wake_end'], $row['wake_start']) < 0) {
            return ['ok' => false, 'message' => 'The wake cannot end before it starts.'];
        }
        if ($row['burial_at'] && $row['wake_start'] && strcmp($row['burial_at'], $row['wake_start']) < 0) {
            return ['ok' => false, 'message' => 'The burial cannot be before the wake starts.'];
        }
        if ($row['family_mobile'] !== null && ! preg_match('/^[0-9+\-\s()]{7,20}$/', $row['family_mobile'])) {
            return ['ok' => false, 'message' => 'The family\'s mobile number is not valid.'];
        }

        $this->ensurePlan($serviceId, $actorUserId);
        $row['updated_by'] = $actorUserId;
        $row['updated_at'] = date('Y-m-d H:i:s');
        db_connect()->table('service_plans')->where('service_id', $serviceId)->update($row);
        // the date of the service itself follows the burial
        if ($row['burial_at']) {
            db_connect()->table('services')->where('service_id', $serviceId)->update(['service_date' => substr($row['burial_at'], 0, 10), 'service_time' => substr($row['burial_at'], 11, 8), 'burial_location' => $row['cemetery']]);
        }

        $before = (string) $svc['status'];
        $this->syncStage($serviceId);
        $after = (string) db_connect()->table('services')->select('status')->where('service_id', $serviceId)->get(1)->getRowArray()['status'];
        if ($before === 'pending' && $after === 'scheduled') {
            $p = $this->plan($serviceId);
            (new NotificationService())->notify((int) $svc['client_user_id'], 'Your service (' . $svc['what'] . ') is scheduled: wake from ' . date('F j, g:ia', strtotime((string) $p['wake_start'])) . ' at ' . $p['wake_address'] . ', burial on ' . date('F j, g:ia', strtotime((string) $p['burial_at'])) . ' at ' . $p['cemetery'] . '.', 'general');
        }

        return ['ok' => true, 'message' => 'Plan saved.' . ($after === 'scheduled' && $before === 'pending' ? ' The service is now scheduled.' : '')];
    }

    /** Is the schedule + family contact complete (so the service can be 'scheduled')? */
    public function planComplete(array $p): bool
    {
        foreach (['wake_start', 'wake_address', 'burial_at', 'cemetery', 'family_name', 'family_mobile'] as $k) {
            if (trim((string) ($p[$k] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    // ------------------------------------------------------------------ task management (Branch Admin)

    /** @return list<array{user_id:int,name:string}> staff of the branch a task can be assigned to */
    public function branchStaff(int $branchId): array
    {
        $rows = db_connect()->table('users')->select('user_id, first_name, last_name')->where('role_id', 3)->where('branch_id', $branchId)->where('status', 'active')->where('deleted_at', null)->orderBy('first_name')->get()->getResultArray();

        return array_map(static fn (array $r): array => ['user_id' => (int) $r['user_id'], 'name' => trim((string) $r['first_name'] . ' ' . (string) $r['last_name'])], $rows);
    }

    /**
     * @param array<string,mixed> $in name, instructions, assigned_staff_id, due_at, requires_document
     *
     * @return array{ok:bool, message:string, task_id?:int}
     */
    public function addTask(int $serviceId, int $branchId, int $actorUserId, array $in): array
    {
        $svc = $this->service($serviceId, $branchId);
        if ($svc === null) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if (! in_array($svc['status'], self::OPEN, true)) {
            return ['ok' => false, 'message' => 'Tasks cannot be added to a ' . strtolower(self::stageLabel((string) $svc['status'])) . ' service.'];
        }
        $clean = $this->cleanTask($in, $branchId);
        if (! $clean['ok']) {
            return $clean;
        }
        $db = db_connect();
        $sort = (int) ($db->table('service_tasks')->selectMax('sort_order', 'm')->where('service_id', $serviceId)->get()->getRowArray()['m'] ?? 0) + 10;
        $now = date('Y-m-d H:i:s');
        $db->table('service_tasks')->insert($clean['row'] + ['service_id' => $serviceId, 'sort_order' => $sort, 'status' => 'todo', 'source' => 'manual', 'created_by' => $actorUserId, 'created_at' => $now, 'updated_at' => $now]);
        $id = (int) $db->insertID();
        $this->afterAssignment($serviceId, $svc, $id, null, $clean['row']['assigned_staff_id'] ?? null);
        $this->syncStage($serviceId);

        return ['ok' => true, 'message' => 'Task added.', 'task_id' => $id];
    }

    /** @return array{ok:bool, message:string} */
    public function updateTask(int $taskId, int $branchId, array $in): array
    {
        $t = $this->task($taskId);
        $svc = $t ? $this->service((int) $t['service_id'], $branchId) : null;
        if ($t === null || $svc === null) {
            return ['ok' => false, 'message' => 'Task not found.'];
        }
        if (! in_array($svc['status'], self::OPEN, true)) {
            return ['ok' => false, 'message' => 'The service is already ' . strtolower(self::stageLabel((string) $svc['status'])) . '.'];
        }
        $clean = $this->cleanTask($in, $branchId);
        if (! $clean['ok']) {
            return $clean;
        }
        if ($t['status'] === 'done') {
            // a finished task keeps its history: only the note/instructions of the plan may change
            unset($clean['row']['assigned_staff_id'], $clean['row']['due_at'], $clean['row']['requires_document']);
        }
        $clean['row']['updated_at'] = date('Y-m-d H:i:s');
        db_connect()->table('service_tasks')->where('task_id', $taskId)->update($clean['row']);
        $this->afterAssignment((int) $t['service_id'], $svc, $taskId, $t['assigned_staff_id'] !== null ? (int) $t['assigned_staff_id'] : null, $clean['row']['assigned_staff_id'] ?? ($t['assigned_staff_id'] !== null ? (int) $t['assigned_staff_id'] : null));
        $this->syncStage((int) $t['service_id']);

        return ['ok' => true, 'message' => 'Task saved.'];
    }

    /** @return array{ok:bool, message:string} */
    public function removeTask(int $taskId, int $branchId): array
    {
        $t = $this->task($taskId);
        $svc = $t ? $this->service((int) $t['service_id'], $branchId) : null;
        if ($t === null || $svc === null) {
            return ['ok' => false, 'message' => 'Task not found.'];
        }
        if (in_array($t['status'], ['done', 'in_progress'], true)) {
            return ['ok' => false, 'message' => 'A task that was started or finished cannot be removed.'];
        }
        db_connect()->table('service_tasks')->where('task_id', $taskId)->delete();
        if (! empty($t['document_path'])) {
            @unlink(WRITEPATH . $t['document_path']);
        }
        $this->syncStage((int) $t['service_id']);

        return ['ok' => true, 'message' => 'Task removed.'];
    }

    /** Moves a task up (-1) or down (+1) in the list. */
    public function moveTask(int $taskId, int $branchId, int $direction): array
    {
        $t = $this->task($taskId);
        if ($t === null || $this->service((int) $t['service_id'], $branchId) === null) {
            return ['ok' => false, 'message' => 'Task not found.'];
        }
        $all = $this->tasks((int) $t['service_id']);
        $ids = array_map(static fn (array $r): int => (int) $r['task_id'], $all);
        $i = array_search($taskId, $ids, true);
        $j = $i + ($direction < 0 ? -1 : 1);
        if ($i === false || ! isset($ids[$j])) {
            return ['ok' => true, 'message' => ''];
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $pos => $id) {
            db_connect()->table('service_tasks')->where('task_id', $id)->update(['sort_order' => ($pos + 1) * 10]);
        }

        return ['ok' => true, 'message' => ''];
    }

    /** The Branch Admin looked at a reported problem and puts the task back to "to do". */
    public function resolveProblem(int $taskId, int $branchId): array
    {
        $t = $this->task($taskId);
        if ($t === null || $this->service((int) $t['service_id'], $branchId) === null || $t['status'] !== 'problem') {
            return ['ok' => false, 'message' => 'There is no reported problem on this task.'];
        }
        db_connect()->table('service_tasks')->where('task_id', $taskId)->update(['status' => 'todo', 'problem_reason' => null, 'problem_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        if ($t['assigned_staff_id']) {
            (new NotificationService())->notify((int) $t['assigned_staff_id'], 'The problem you reported on "' . $t['name'] . '" was looked at - the task is back on your list.', 'general');
        }

        return ['ok' => true, 'message' => 'The task is back to "to do".'];
    }

    /** @return array{ok:bool, message:string, row?:array<string,mixed>} */
    private function cleanTask(array $in, int $branchId): array
    {
        $name = trim((string) ($in['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            return ['ok' => false, 'message' => 'Give the task a name (up to 150 characters).'];
        }
        $row = ['name' => $name, 'instructions' => ($i = trim((string) ($in['instructions'] ?? ''))) === '' ? null : mb_substr($i, 0, 2000), 'requires_document' => ! empty($in['requires_document']) ? 1 : 0];
        $staff = (int) ($in['assigned_staff_id'] ?? 0);
        if ($staff > 0) {
            $ok = db_connect()->table('users')->where('user_id', $staff)->where('role_id', 3)->where('branch_id', $branchId)->where('status', 'active')->where('deleted_at', null)->countAllResults() > 0;
            if (! $ok) {
                return ['ok' => false, 'message' => 'That staff member does not belong to this branch.'];
            }
        }
        $row['assigned_staff_id'] = $staff > 0 ? $staff : null;
        $due = trim((string) ($in['due_at'] ?? ''));
        if ($due !== '') {
            $ts = strtotime(str_replace('T', ' ', $due));
            if ($ts === false) {
                return ['ok' => false, 'message' => 'The due date and time is not valid.'];
            }
            $row['due_at'] = date('Y-m-d H:i:s', $ts);
        } else {
            $row['due_at'] = null;
        }

        return ['ok' => true, 'message' => '', 'row' => $row];
    }

    /** Tells the staff member when a task is given to them; keeps services.assigned_staff pointing at the first assignee. */
    private function afterAssignment(int $serviceId, array $svc, int $taskId, ?int $oldStaff, ?int $newStaff): void
    {
        if ($newStaff === null || $newStaff === $oldStaff) {
            return;
        }
        $t = $this->task($taskId);
        $db = db_connect();
        if (empty($svc['assigned_staff'])) {
            $db->table('services')->where('service_id', $serviceId)->update(['assigned_staff' => $newStaff]);
        }
        (new NotificationService())->notify($newStaff, 'New task for you: "' . ($t['name'] ?? 'task') . '" for ' . ($svc['deceased_name'] ?: $svc['client_name']) . ($t && $t['due_at'] ? ', due ' . date('M j, g:ia', strtotime((string) $t['due_at'])) : '') . '. See My tasks.', 'general');
    }

    // ------------------------------------------------------------------ staff actions (own tasks only)

    /**
     * @return array{ok:bool, message:string, task?:array<string,mixed>, service?:array<string,mixed>}
     */
    private function ownTask(int $taskId, int $staffId, int $branchId): array
    {
        $t = $this->task($taskId);
        $svc = $t ? $this->service((int) $t['service_id'], $branchId) : null;
        if ($t === null || $svc === null || (int) $t['assigned_staff_id'] !== $staffId) {
            return ['ok' => false, 'message' => 'Task not found.'];
        }
        if (! in_array($svc['status'], self::OPEN, true)) {
            return ['ok' => false, 'message' => 'This service is already ' . strtolower(self::stageLabel((string) $svc['status'])) . '.'];
        }

        return ['ok' => true, 'message' => '', 'task' => $t, 'service' => $svc];
    }

    /** @return array{ok:bool, message:string} */
    public function startTask(int $taskId, int $staffId, int $branchId): array
    {
        $o = $this->ownTask($taskId, $staffId, $branchId);
        if (! $o['ok']) {
            return $o;
        }
        $t = $o['task'];
        if ($t['status'] !== 'todo') {
            return ['ok' => false, 'message' => 'This task is already ' . strtolower(self::TASK_STATUS[$t['status']] ?? $t['status']) . '.'];
        }
        $gate = (new ClaimAvailService())->checkServiceStatusChange((int) $t['service_id'], 'ongoing');
        if (! $gate['ok']) {
            return $gate;
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('service_tasks')->where('task_id', $taskId)->update(['status' => 'in_progress', 'started_at' => $now, 'updated_at' => $now]);
        $this->syncStage((int) $t['service_id']);

        return ['ok' => true, 'message' => 'Task started.'];
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function doneTask(int $taskId, int $staffId, int $branchId, string $note, ?UploadedFile $file): array
    {
        $o = $this->ownTask($taskId, $staffId, $branchId);
        if (! $o['ok']) {
            return $o;
        }
        $t = $o['task'];
        $svc = $o['service'];
        if (! in_array($t['status'], ['todo', 'in_progress'], true)) {
            return ['ok' => false, 'message' => 'This task is already ' . strtolower(self::TASK_STATUS[$t['status']] ?? $t['status']) . '.'];
        }
        $gate = (new ClaimAvailService())->checkServiceStatusChange((int) $t['service_id'], 'ongoing');
        if (! $gate['ok']) {
            return $gate;
        }
        $hasFile = $file !== null && $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE;
        if ((int) $t['requires_document'] === 1 && ! $hasFile) {
            return ['ok' => false, 'message' => 'This task needs a document or photo (image or PDF, up to 5 MB) before you can mark it done.'];
        }
        $update = ['status' => 'done', 'done_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'note' => ($n = trim($note)) === '' ? null : mb_substr($n, 0, 1000)];
        if ($t['started_at'] === null) {
            $update['started_at'] = $update['done_at'];
        }
        if ($hasFile) {
            $saved = $this->storeUpload($file, 'service-tasks', true);
            if (! $saved['ok']) {
                return $saved;
            }
            $update['document_path'] = $saved['path'];
            $update['document_name'] = mb_substr((string) $file->getClientName(), 0, 150);
        }
        $wasReady = $this->readyToComplete($svc);
        db_connect()->table('service_tasks')->where('task_id', $taskId)->update($update);
        $this->syncStage((int) $t['service_id']);
        $svc2 = $this->service((int) $t['service_id']);
        if ($svc2 !== null && ! $wasReady && $this->readyToComplete($svc2)) {
            $this->notifyBranchAdmins((int) $svc['branch_id'], 'All tasks of ' . ($svc['deceased_name'] ?: $svc['client_name']) . ' (' . $svc['what'] . ') are done - ready to complete. Record the family\'s confirmation to complete the service.');
        }

        return ['ok' => true, 'message' => 'Task marked as done.'];
    }

    /** @return array{ok:bool, message:string} */
    public function reportProblem(int $taskId, int $staffId, int $branchId, string $reason): array
    {
        $o = $this->ownTask($taskId, $staffId, $branchId);
        if (! $o['ok']) {
            return $o;
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'Say what the problem is (at least 5 characters).'];
        }
        if ($o['task']['status'] === 'done') {
            return ['ok' => false, 'message' => 'This task is already done.'];
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('service_tasks')->where('task_id', $taskId)->update(['status' => 'problem', 'problem_reason' => mb_substr($reason, 0, 500), 'problem_at' => $now, 'updated_at' => $now]);
        $svc = $o['service'];
        $staff = db_connect()->table('users')->select('first_name, last_name')->where('user_id', $staffId)->get(1)->getRowArray() ?: [];
        $this->notifyBranchAdmins((int) $svc['branch_id'], trim((string) ($staff['first_name'] ?? '') . ' ' . (string) ($staff['last_name'] ?? '')) . ' reported a problem on "' . $o['task']['name'] . '" (' . ($svc['deceased_name'] ?: $svc['client_name']) . '): ' . $reason);

        return ['ok' => true, 'message' => 'The problem was reported to your Branch Admin.'];
    }

    /**
     * Tasks of one staff member in their branch, grouped: today (due today, overdue, or reported problems), upcoming, done.
     *
     * @return array{today:list<array<string,mixed>>, upcoming:list<array<string,mixed>>, done:list<array<string,mixed>>}
     */
    public function myTasks(int $staffId, int $branchId, ?string $today = null): array
    {
        $out = ['today' => [], 'upcoming' => [], 'done' => []];
        if (! self::ready()) {
            return $out;
        }
        $today = date('Y-m-d', strtotime($today ?? 'today'));
        $rows = db_connect()->table('service_tasks t')
            ->select('t.*, s.status AS service_status, s.branch_id, sa.deceased_name, ph.unique_identifier, u.first_name AS client_first, u.last_name AS client_last')
            ->join('services s', 's.service_id = t.service_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('service_applications sa', 'sa.service_id = s.service_id', 'left')
            ->where('t.assigned_staff_id', $staffId)->where('s.branch_id', $branchId)->where('s.deleted_at', null)
            ->whereNotIn('s.status', ['cancelled'])
            ->orderBy('t.due_at', 'ASC')->orderBy('t.task_id', 'ASC')
            ->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            $plan = $this->plan((int) $r['service_id']);
            $r['family_name'] = $plan['family_name'] ?? null;
            $r['family_mobile'] = $plan['family_mobile'] ?? null;
            $r['family_relationship'] = $plan['family_relationship'] ?? null;
            $r['wake_address'] = $plan['wake_address'] ?? null;
            $r['wake_start'] = $plan['wake_start'] ?? null;
            $r['burial_at'] = $plan['burial_at'] ?? null;
            $r['cemetery'] = $plan['cemetery'] ?? null;
            $r['maps_url'] = self::mapsUrl($plan['wake_address'] ?? null);
            $r['tel'] = preg_replace('/[^0-9+]/', '', (string) ($plan['family_mobile'] ?? ''));
            $r['client_name'] = trim((string) $r['client_first'] . ' ' . (string) $r['client_last']);
            $r['overdue'] = $r['due_at'] !== null && in_array($r['status'], ['todo', 'in_progress'], true) && strcmp((string) $r['due_at'], $now) < 0;
            $r['status_label'] = self::TASK_STATUS[$r['status']] ?? $r['status'];
            $r['can_act'] = in_array($r['service_status'], self::OPEN, true);
            if ($r['status'] === 'done') {
                $out['done'][] = $r;
            } elseif ($r['status'] === 'problem' || $r['overdue'] || ($r['due_at'] !== null && substr((string) $r['due_at'], 0, 10) <= $today) || $r['status'] === 'in_progress') {
                $out['today'][] = $r;
            } else {
                $out['upcoming'][] = $r;
            }
        }
        $out['done'] = array_slice(array_reverse($out['done']), 0, 50);

        return $out;
    }

    /** Open tasks of this staff member that are due today or overdue (menu badge). */
    public function myOpenCount(int $staffId, int $branchId): int
    {
        if (! self::ready()) {
            return 0;
        }
        $t = $this->myTasks($staffId, $branchId);

        return count(array_filter($t['today'], static fn (array $r): bool => $r['can_act']));
    }

    // ------------------------------------------------------------------ stages

    /**
     * Moves the service to the stage its tasks and plan say it is in (forward only). Called after every change.
     */
    public function syncStage(int $serviceId, ?string $today = null): void
    {
        $db = db_connect();
        $svc = $db->table('services')->where('service_id', $serviceId)->get(1)->getRowArray();
        if (! $svc || ! in_array($svc['status'], self::OPEN, true)) {
            return;
        }
        $order = array_keys(self::STAGES);
        $current = (string) $svc['status'];
        $tasks = $db->table('service_tasks')->where('service_id', $serviceId)->get()->getResultArray();
        $plan = $this->plan($serviceId);
        $today = date('Y-m-d', strtotime($today ?? 'today'));
        $cleared = (new ClaimAvailService())->downPaymentCleared($serviceId);

        $target = $current;
        $started = count(array_filter($tasks, static fn (array $t): bool => in_array($t['status'], ['in_progress', 'done'], true))) > 0;
        $assigned = count(array_filter($tasks, static fn (array $t): bool => $t['assigned_staff_id'] !== null)) > 0;
        if ($started && $cleared) {
            $target = 'ongoing';
        } elseif ($assigned && $this->planComplete($plan)) {
            $target = 'preparing';
        } elseif ($this->planComplete($plan)) {
            $target = 'scheduled';
        }
        // a service that is already ongoing stays at least ongoing; burial day arrives with the date
        if (($target === 'ongoing' || $current === 'ongoing' || $current === 'burial_day') && $cleared && ! empty($plan['burial_at']) && substr((string) $plan['burial_at'], 0, 10) <= $today && $started) {
            $target = 'burial_day';
        }
        if (array_search($target, $order, true) > array_search($current, $order, true)) {
            $db->table('services')->where('service_id', $serviceId)->update(['status' => $target]);
            if ($target === 'ongoing' && $current !== 'burial_day') {
                (new ClaimAvailService())->onServiceStatusChanged($serviceId, $current, 'ongoing');
            }
            if ($target === 'burial_day') {
                $svcInfo = $this->service($serviceId);
                if ($svcInfo) {
                    (new NotificationService())->notify((int) $svcInfo['client_user_id'], 'Today is the burial day of your service (' . $svcInfo['what'] . '). We are with you and your family.', 'general');
                }
            }
        }
    }

    /** Runs syncStage for every open service of a branch (so "burial day" arrives with the date even if nobody touched the service). */
    public function syncBranch(int $branchId, ?string $today = null): void
    {
        if (! self::ready()) {
            return;
        }
        foreach (db_connect()->table('services')->select('service_id')->where('branch_id', $branchId)->whereIn('status', self::OPEN)->where('deleted_at', null)->get()->getResultArray() as $r) {
            $this->syncStage((int) $r['service_id'], $today);
        }
    }

    /**
     * Cancels a service that is still open (the only status the Branch Admin sets by hand; every other stage moves by itself).
     *
     * @return array{ok:bool, message:string}
     */
    public function cancel(int $serviceId, int $branchId, int $actorUserId): array
    {
        $svc = $this->service($serviceId, $branchId);
        if ($svc === null) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if (! in_array($svc['status'], self::OPEN, true)) {
            return ['ok' => false, 'message' => 'Only a service that is still open can be cancelled.'];
        }
        db_connect()->table('services')->where('service_id', $serviceId)->update(['status' => 'cancelled']);
        (new ActivityLogService())->log($actorUserId, 'updated', 'service', $serviceId, 'Service cancelled');

        return ['ok' => true, 'message' => 'Service cancelled.'];
    }

    // ------------------------------------------------------------------ completion, closing, rating

    /**
     * The Branch Admin records the family's confirmation and completes the service. Needs: the service ongoing / on its burial
     * day, every task done, the down payment cleared, and the family's name, relationship and date (a photo of the signed form
     * is optional).
     *
     * @return array{ok:bool, message:string}
     */
    public function complete(int $serviceId, int $branchId, int $actorUserId, array $in, ?UploadedFile $photo): array
    {
        $svc = $this->service($serviceId, $branchId);
        if ($svc === null) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if (! in_array($svc['status'], ['ongoing', 'burial_day'], true)) {
            return ['ok' => false, 'message' => 'Only a service that is ongoing or on its burial day can be completed (this one is ' . strtolower(self::stageLabel((string) $svc['status'])) . ').'];
        }
        $gate = (new ClaimAvailService())->checkServiceStatusChange($serviceId, 'completed');
        if (! $gate['ok']) {
            return $gate;
        }
        if (! $this->allTasksDone($serviceId)) {
            return ['ok' => false, 'message' => 'Every task must be done before the service is completed.'];
        }
        $name = trim((string) ($in['confirmed_name'] ?? ''));
        $rel = trim((string) ($in['confirmed_relationship'] ?? ''));
        $on = trim((string) ($in['confirmed_on'] ?? ''));
        if (mb_strlen($name) < 3 || $rel === '' || $on === '') {
            return ['ok' => false, 'message' => 'Record the family\'s confirmation: who confirmed (name), their relationship, and the date.'];
        }
        $ts = strtotime($on);
        if ($ts === false || date('Y-m-d', $ts) > date('Y-m-d')) {
            return ['ok' => false, 'message' => 'The confirmation date cannot be in the future.'];
        }
        $path = null;
        if ($photo !== null && $photo->isValid() && $photo->getError() !== UPLOAD_ERR_NO_FILE) {
            $saved = $this->storeUpload($photo, 'service-confirmations', true);
            if (! $saved['ok']) {
                return $saved;
            }
            $path = $saved['path'];
        }
        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transStart();
        $this->ensurePlan($serviceId, $actorUserId);
        $db->table('service_plans')->where('service_id', $serviceId)->update([
            'confirmed_name' => mb_substr($name, 0, 150), 'confirmed_relationship' => mb_substr($rel, 0, 100), 'confirmed_on' => date('Y-m-d', $ts),
            'confirmed_photo_path' => $path, 'confirmed_by' => $actorUserId, 'completed_at' => $now, 'updated_at' => $now,
        ]);
        $db->table('services')->where('service_id', $serviceId)->update(['status' => 'completed']);
        $db->transComplete();
        if (! $db->transStatus()) {
            return ['ok' => false, 'message' => 'The service could not be completed. Nothing was changed.'];
        }
        (new ActivityLogService())->log($actorUserId, 'updated', 'service', $serviceId, 'Service completed (family confirmation by ' . $name . ')');
        (new NotificationService())->notify((int) $svc['client_user_id'], 'Your service (' . $svc['what'] . ') has been completed. Thank you for trusting us. Please tell us how we did: open My claims and rate the service (1 to 5).', 'service_completed');

        return ['ok' => true, 'message' => 'Service completed. The family was thanked and can now rate it.'];
    }

    /** @return array{ok:bool, message:string} */
    public function close(int $serviceId, int $branchId, int $actorUserId): array
    {
        $svc = $this->service($serviceId, $branchId);
        if ($svc === null) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if ($svc['status'] !== 'completed') {
            return ['ok' => false, 'message' => 'Only a completed service can be closed.'];
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('services')->where('service_id', $serviceId)->update(['status' => 'closed']);
        db_connect()->table('service_plans')->where('service_id', $serviceId)->update(['closed_at' => $now, 'closed_by' => $actorUserId]);
        (new ActivityLogService())->log($actorUserId, 'updated', 'service', $serviceId, 'Service closed');

        return ['ok' => true, 'message' => 'Service closed.'];
    }

    /**
     * The client rates a completed service once (1 to 5, with optional feedback).
     *
     * @return array{ok:bool, message:string}
     */
    public function rate(int $serviceId, int $clientUserId, int $rating, string $feedback): array
    {
        $svc = $this->service($serviceId);
        if ($svc === null || (int) $svc['client_user_id'] !== $clientUserId) {
            return ['ok' => false, 'message' => 'Service not found.'];
        }
        if (! in_array($svc['status'], ['completed', 'closed'], true)) {
            return ['ok' => false, 'message' => 'You can rate a service after it is completed.'];
        }
        if ($rating < 1 || $rating > 5) {
            return ['ok' => false, 'message' => 'Choose 1 to 5 stars.'];
        }
        $plan = $this->plan($serviceId);
        if (! empty($plan['rated_at'])) {
            return ['ok' => false, 'message' => 'You already rated this service. Thank you!'];
        }
        $this->ensurePlan($serviceId);
        $now = date('Y-m-d H:i:s');
        db_connect()->table('service_plans')->where('service_id', $serviceId)->update(['rating' => $rating, 'feedback' => ($f = trim($feedback)) === '' ? null : mb_substr($f, 0, 1000), 'rated_at' => $now, 'updated_at' => $now]);
        $this->notifyBranchAdmins((int) $svc['branch_id'], 'The family of ' . ($svc['deceased_name'] ?: $svc['client_name']) . ' rated the service ' . $rating . '/5.' . (trim($feedback) !== '' ? ' "' . mb_substr(trim($feedback), 0, 200) . '"' : ''));

        return ['ok' => true, 'message' => 'Thank you for your feedback.'];
    }

    // ------------------------------------------------------------------ board, calendar, attention

    /**
     * The Branch Admin's board: every service of the branch in its stage, with tasks done / total, overdue tasks and problems.
     *
     * @return array<string, list<array<string,mixed>>> by stage
     */
    public function board(int $branchId, ?string $today = null): array
    {
        $this->syncBranch($branchId, $today);
        $cols = [];
        foreach (array_keys(self::STAGES) as $s) {
            if ($s !== 'cancelled') {
                $cols[$s] = [];
            }
        }
        $rows = db_connect()->table('services s')->select('s.service_id')->where('s.branch_id', $branchId)->where('s.deleted_at', null)->whereNotIn('s.status', ['cancelled'])->orderBy('s.service_id', 'DESC')->get()->getResultArray();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            $svc = $this->service((int) $r['service_id']);
            if ($svc === null) {
                continue;
            }
            $plan = $this->plan((int) $svc['service_id']);
            if ($svc['status'] === 'closed' && ! empty($plan['closed_at']) && strcmp((string) $plan['closed_at'], date('Y-m-d H:i:s', strtotime('-30 days'))) < 0) {
                continue;
            }
            $tasks = $this->tasks((int) $svc['service_id']);
            $svc['tasks_total'] = count($tasks);
            $svc['tasks_done'] = count(array_filter($tasks, static fn (array $t): bool => $t['status'] === 'done'));
            $svc['tasks_overdue'] = count(array_filter($tasks, static fn (array $t): bool => $t['overdue']));
            $svc['tasks_problem'] = count(array_filter($tasks, static fn (array $t): bool => $t['status'] === 'problem'));
            $svc['ready'] = $this->readyToComplete($svc);
            $svc['plan'] = $plan;
            $cols[(string) $svc['status']][] = $svc;
        }

        return $cols;
    }

    /**
     * Wakes and burials of a month for the calendar.
     *
     * @return array<string, list<array{type:string, label:string, at:string, service_id:int, who:string, place:string}>> by date
     */
    public function calendar(int $branchId, string $ym): array
    {
        $ym = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym) ? $ym : date('Y-m');
        $first = $ym . '-01';
        $last = date('Y-m-t', strtotime($first));
        $rows = db_connect()->table('service_plans sp')
            ->select('sp.*, s.status, sa.deceased_name, u.first_name, u.last_name')
            ->join('services s', 's.service_id = sp.service_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = s.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('service_applications sa', 'sa.service_id = s.service_id', 'left')
            ->where('s.branch_id', $branchId)->where('s.deleted_at', null)->whereNotIn('s.status', ['cancelled'])
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $who = (string) ($r['deceased_name'] ?: trim((string) $r['first_name'] . ' ' . (string) $r['last_name']));
            foreach ([['wake_start', 'Wake starts', 'wake_address'], ['wake_end', 'Wake ends', 'wake_address'], ['burial_at', 'Burial', 'cemetery'], ['pickup_at', 'Pick-up', 'pickup_place']] as [$col, $label, $placeCol]) {
                if (empty($r[$col])) {
                    continue;
                }
                $d = substr((string) $r[$col], 0, 10);
                if ($d < $first || $d > $last) {
                    continue;
                }
                $out[$d][] = ['type' => $col, 'label' => $label, 'at' => (string) $r[$col], 'service_id' => (int) $r['service_id'], 'who' => $who, 'place' => (string) ($r[$placeCol] ?? '')];
            }
        }
        ksort($out);
        foreach ($out as &$list) {
            usort($list, static fn (array $a, array $b): int => strcmp($a['at'], $b['at']));
        }
        unset($list);

        return $out;
    }

    /**
     * "Needs attention" figures for the Branch Admin: overdue tasks, reported problems, services ready to complete.
     *
     * @return array{overdue:int, problems:int, ready:int}
     */
    public function attention(int $branchId): array
    {
        $out = ['overdue' => 0, 'problems' => 0, 'ready' => 0];
        if (! self::ready()) {
            return $out;
        }
        $now = date('Y-m-d H:i:s');
        $base = fn () => db_connect()->table('service_tasks t')->join('services s', 's.service_id = t.service_id', 'inner')->where('s.branch_id', $branchId)->where('s.deleted_at', null)->whereIn('s.status', self::OPEN);
        $out['overdue'] = (int) $base()->whereIn('t.status', ['todo', 'in_progress'])->where('t.due_at <', $now)->countAllResults();
        $out['problems'] = (int) $base()->where('t.status', 'problem')->countAllResults();
        foreach (db_connect()->table('services')->select('service_id')->where('branch_id', $branchId)->whereIn('status', ['ongoing', 'burial_day'])->where('deleted_at', null)->get()->getResultArray() as $r) {
            if ($this->allTasksDone((int) $r['service_id'])) {
                $out['ready']++;
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ files

    /**
     * Checks and stores an uploaded image / PDF under writable/uploads/<folder>/ with a random name.
     *
     * @return array{ok:bool, message:string, path?:string}
     */
    public function storeUpload(UploadedFile $file, string $folder, bool $allowPdf): array
    {
        $check = self::checkUpload($file, $allowPdf);
        if (! $check['ok']) {
            return $check;
        }
        $dir = WRITEPATH . 'uploads/' . $folder;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $check['ext'];
        $file->move($dir, $name);

        return ['ok' => true, 'message' => '', 'path' => 'uploads/' . $folder . '/' . $name];
    }

    /**
     * Image (jpg, png, webp) - or PDF when allowed - up to 5 MB, judged by what the file really is, not by its name.
     *
     * @return array{ok:bool, message:string, ext?:string}
     */
    public static function checkUpload(UploadedFile $file, bool $allowPdf): array
    {
        if (! $file->isValid()) {
            return ['ok' => false, 'message' => 'The file could not be uploaded. Please try again.'];
        }
        if ($file->getSize() > self::MAX_UPLOAD) {
            return ['ok' => false, 'message' => 'The file is too big (5 MB at most).'];
        }
        $path = $file->getTempName();
        $mime = function_exists('finfo_open') ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path) : (string) $file->getMimeType();
        if (isset(self::IMAGE_MIMES[$mime])) {
            if (@getimagesize($path) === false) {
                return ['ok' => false, 'message' => 'That image file is damaged.'];
            }

            return ['ok' => true, 'message' => '', 'ext' => self::IMAGE_MIMES[$mime]];
        }
        if ($allowPdf && $mime === 'application/pdf') {
            return ['ok' => true, 'message' => '', 'ext' => 'pdf'];
        }

        return ['ok' => false, 'message' => $allowPdf ? 'Upload an image (JPG, PNG, WebP) or a PDF.' : 'Upload an image (JPG, PNG or WebP).'];
    }

    private function notifyBranchAdmins(int $branchId, string $message): void
    {
        $notify = new NotificationService();
        foreach (db_connect()->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', $branchId)->where('status', 'active')->where('deleted_at', null)->get()->getResultArray() as $ba) {
            $notify->notify((int) $ba['user_id'], $message, 'general');
        }
    }
}
