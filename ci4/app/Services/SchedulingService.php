<?php

namespace App\Services;

use DateTime;
use DateInterval;
use Exception;

/**
 * Scheduling Service
 * 
 * Manages all aspects of funeral service scheduling including:
 * - Hearse scheduling and availability
 * - Embalmer assignments
 * - Staff duty scheduling
 * - Service calendar management
 * - Resource conflict detection
 */
class SchedulingService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Schedule a funeral service
     * 
     * @param int $branchId
     * @param int $planHolderId
     * @param DateTime $eventDate
     * @param string $eventType
     * @param string|null $location
     * @param array $options Additional options (hearse_id, embalmer_id, staff_ids)
     * 
     * @return int Calendar event ID
     * @throws Exception
     */
    public function scheduleService(
        int $branchId,
        int $planHolderId,
        DateTime $eventDate,
        string $eventType,
        ?string $location = null,
        array $options = []
    ): int {
        try {
            // Validate event type
            $validTypes = ['funeral', 'viewing', 'burial', 'other'];
            if (! in_array($eventType, $validTypes, true)) {
                throw new Exception("Invalid event type: $eventType");
            }

            // Validate date is in the future
            $now = new DateTime();
            if ($eventDate < $now) {
                throw new Exception('Event date must be in the future');
            }

            $hearseId = $options['hearse_id'] ?? null;
            $embalmedId = $options['embalmer_id'] ?? null;
            $staffIds = $options['staff_ids'] ?? [];

            // Check hearse availability if specified
            if ($hearseId) {
                if (! $this->isHearseAvailable($hearseId, $eventDate)) {
                    throw new Exception('Selected hearse is not available on that date');
                }
            }

            // Check embalmer availability if specified
            if ($embalmedId) {
                if (! $this->isEmbalmingAvailable($embalmedId, $eventDate)) {
                    throw new Exception('Selected embalmer is not available on that date');
                }
            }

            // Check staff availability if specified
            if (! empty($staffIds)) {
                foreach ($staffIds as $staffId) {
                    if (! $this->isStaffAvailable($staffId, $eventDate)) {
                        throw new Exception("Staff member ID $staffId is not available on that date");
                    }
                }
            }

            // Insert calendar event
            $eventId = (int) $this->db->table('service_calendar')->insert([
                'branch_id' => $branchId,
                'plan_holder_id' => $planHolderId,
                'event_type' => $eventType,
                'event_date' => $eventDate->format('Y-m-d'),
                'event_time' => $eventDate->format('H:i:s'),
                'location' => $location,
                'status' => 'scheduled',
                'hearse_id' => $hearseId,
                'embalmer_id' => $embalmedId,
                'assigned_staff_ids' => ! empty($staffIds) ? json_encode($staffIds) : null,
            ], true);

            if ($eventId <= 0) {
                throw new Exception('Unable to create service calendar event');
            }

            return $eventId;
        } catch (Exception $e) {
            throw new Exception('Service scheduling failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if hearse is available on a specific date
     */
    public function isHearseAvailable(int $hearseId, DateTime $eventDate): bool
    {
        $hearse = $this->db->table('hearses')
            ->where('hearse_id', $hearseId)
            ->where('status', 'available')
            ->first();

        if (! $hearse) {
            return false;
        }

        // Check for conflicting events
        $conflicts = $this->db->table('service_calendar')
            ->where('hearse_id', $hearseId)
            ->where('event_date', $eventDate->format('Y-m-d'))
            ->where('status !=', 'cancelled')
            ->countAllResults();

        return $conflicts === 0;
    }

    /**
     * Check if embalmer is available on a specific date
     */
    public function isEmbalmingAvailable(int $embalmedId, DateTime $eventDate): bool
    {
        $embalmer = $this->db->table('embalmers')
            ->where('embalmer_id', $embalmedId)
            ->where('status !=', 'inactive')
            ->first();

        if (! $embalmer) {
            return false;
        }

        // Check for conflicting assignments
        $conflicts = $this->db->table('service_calendar')
            ->where('embalmer_id', $embalmedId)
            ->where('event_date', $eventDate->format('Y-m-d'))
            ->where('status !=', 'cancelled')
            ->countAllResults();

        return $conflicts === 0;
    }

    /**
     * Check if staff member is available on a specific date
     */
    public function isStaffAvailable(int $staffId, DateTime $eventDate): bool
    {
        $user = $this->db->table('users')
            ->where('user_id', $staffId)
            ->where('account_status', 'active')
            ->first();

        if (! $user) {
            return false;
        }

        // Check for conflicting schedules
        $conflicts = $this->db->table('service_calendar')
            ->where("JSON_CONTAINS(assigned_staff_ids, JSON_QUOTE(?))", $staffId)
            ->where('event_date', $eventDate->format('Y-m-d'))
            ->where('status !=', 'cancelled')
            ->countAllResults();

        return $conflicts === 0;
    }

    /**
     * Get available hearses for a date
     */
    public function getAvailableHearses(int $branchId, DateTime $eventDate): array
    {
        $allHearses = $this->db->table('hearses')
            ->where('branch_id', $branchId)
            ->where('status', 'available')
            ->get()
            ->getResultArray();

        $available = [];
        foreach ($allHearses as $hearse) {
            if ($this->isHearseAvailable((int) $hearse['hearse_id'], $eventDate)) {
                $available[] = $hearse;
            }
        }

        return $available;
    }

    /**
     * Get available embalmers for a date
     */
    public function getAvailableEmbalmers(int $branchId, DateTime $eventDate): array
    {
        $allEmbalmers = $this->db->table('embalmers')
            ->where('branch_id', $branchId)
            ->where('status !=', 'inactive')
            ->get()
            ->getResultArray();

        $available = [];
        foreach ($allEmbalmers as $embalmer) {
            if ($this->isEmbalmingAvailable((int) $embalmer['embalmer_id'], $eventDate)) {
                $available[] = $embalmer;
            }
        }

        return $available;
    }

    /**
     * Get available staff for a date
     */
    public function getAvailableStaff(int $branchId, DateTime $eventDate): array
    {
        $allStaff = $this->db->table('users')
            ->where('branch_id', $branchId)
            ->where('role_id', 3)
            ->where('account_status', 'active')
            ->get()
            ->getResultArray();

        $available = [];
        foreach ($allStaff as $staff) {
            if ($this->isStaffAvailable((int) $staff['user_id'], $eventDate)) {
                $available[] = $staff;
            }
        }

        return $available;
    }

    /**
     * Update service calendar event status
     */
    public function updateEventStatus(int $calendarId, string $newStatus): bool
    {
        $validStatuses = ['scheduled', 'in-progress', 'completed', 'cancelled'];
        if (! in_array($newStatus, $validStatuses, true)) {
            throw new Exception("Invalid status: $newStatus");
        }

        $result = $this->db->table('service_calendar')
            ->where('calendar_id', $calendarId)
            ->update(['status' => $newStatus]);

        return $result > 0;
    }

    /**
     * Get service calendar events for a date range
     */
    public function getEventsForDateRange(int $branchId, DateTime $startDate, DateTime $endDate): array
    {
        return $this->db->table('service_calendar')
            ->where('branch_id', $branchId)
            ->where('event_date >=', $startDate->format('Y-m-d'))
            ->where('event_date <=', $endDate->format('Y-m-d'))
            ->orderBy('event_date', 'ASC')
            ->orderBy('event_time', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get upcoming events (next 7 days)
     */
    public function getUpcomingEvents(int $branchId): array
    {
        $startDate = new DateTime();
        $endDate = (new DateTime())->add(new DateInterval('P7D'));

        return $this->getEventsForDateRange($branchId, $startDate, $endDate);
    }

    /**
     * Get conflicts for a resource on a date
     */
    public function getResourceConflicts(int $branchId, ?int $hearseId = null, ?int $embalmedId = null, DateTime $eventDate = null): array
    {
        $eventDate = $eventDate ?? new DateTime();
        $conflicts = [];

        if ($hearseId) {
            $conflictingEvents = $this->db->table('service_calendar')
                ->where('hearse_id', $hearseId)
                ->where('event_date', $eventDate->format('Y-m-d'))
                ->where('status !=', 'cancelled')
                ->get()
                ->getResultArray();
            $conflicts['hearse'] = $conflictingEvents;
        }

        if ($embalmedId) {
            $conflictingEvents = $this->db->table('service_calendar')
                ->where('embalmer_id', $embalmedId)
                ->where('event_date', $eventDate->format('Y-m-d'))
                ->where('status !=', 'cancelled')
                ->get()
                ->getResultArray();
            $conflicts['embalmer'] = $conflictingEvents;
        }

        return $conflicts;
    }

    /**
     * Register staff duty schedule
     */
    public function registerStaffSchedule(int $userId, int $branchId, DateTime $scheduleDate, string $startTime, string $endTime, string $dutyType = 'regular'): int
    {
        $scheduleId = (int) $this->db->table('staff_schedules')->insert([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'schedule_date' => $scheduleDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duty_type' => $dutyType,
            'status' => 'scheduled',
        ], true);

        if ($scheduleId <= 0) {
            throw new Exception('Unable to register staff schedule');
        }

        return $scheduleId;
    }

    /**
     * Get staff schedule for a date
     */
    public function getStaffSchedule(int $userId, DateTime $scheduleDate): ?array
    {
        return $this->db->table('staff_schedules')
            ->where('user_id', $userId)
            ->where('schedule_date', $scheduleDate->format('Y-m-d'))
            ->first();
    }
}
