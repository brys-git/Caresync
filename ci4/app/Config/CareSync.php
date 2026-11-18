<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * CareSync business rules for contributions that are overdue.
 *
 * ONE place for the numbers. Every screen and the daily check read them
 * through PlanLedgerService, so changing a value here changes the client
 * details page, the client's own dashboard, Existing member records, the
 * reports, the reminders and the daily check together.
 *
 * Every value below still needs to be confirmed against the Kaagapay
 * contract.
 *
 * Can also be overridden from .env, e.g.  caresync.gracePeriodDays = 45
 */
class CareSync extends BaseConfig
{
    /**
     * Days a member has, counted from the due date of the OLDEST unpaid
     * contribution, to pay everything overdue before the plan lapses.
     * Confirm with Kaagapay contract.
     */
    public int $gracePeriodDays = 60;

    /** Contact details shown to plan holders (Need assistance? box on the dashboard). */
    public string $supportHotline = 'Smart 0962-571-9780 / Globe 0997-512-7828';
    public string $supportEmail   = 'kaagapay00@gmail.com';
    public string $officeHours    = 'Mon-Sat, 8:00 AM - 5:00 PM';

    /**
     * Where the grace period starts counting.
     * 'first_missed_due_date' = the due date of the oldest unpaid contribution
     * (the only mode implemented). Confirm with Kaagapay contract.
     */
    public string $graceStartsFrom = 'first_missed_due_date';

    /**
     * Peso amount charged ON TOP of the overdue contributions to reinstate a
     * Lapsed plan. 0 until confirmed. Confirm with Kaagapay contract.
     */
    public float $reinstatementFee = 0;

    /**
     * How many days a plan may stay Lapsed before it becomes Forfeited.
     * null = never forfeit automatically (a Lapsed plan just stays on hold
     * and keeps all its history). Confirm with Kaagapay contract.
     */
    public ?int $forfeitAfterLapseDays = null;

    /**
     * Whether a Lapsed (on hold) plan may still request services / claim its
     * package. A Forfeited plan never can. Confirm with Kaagapay contract.
     */
    public bool $lapsedCanClaimServices = false;

    /* ------------------------------------------------------------------
     * Claim / avail rules (ClaimAvailService reads these - nothing else
     * hard-codes them). Kaagapay can change any number here, or in .env
     * (e.g. caresync.availDownPaymentRate = 0.20), without touching code.
     * The down-payment rate and the term are copied onto each avail when it
     * is approved, so a later change never alters an existing avail.
     * The 30% down payment and the 6/12/24-month terms are Kaagapay's
     * proposal and still need to be confirmed.
     * ------------------------------------------------------------------ */

    /** Verified monthly contributions needed in the current cycle before a claim or an avail. */
    public int $claimMinMonthsPaid = 2;

    /** Share of the package price paid as the down payment when an avail is approved (0.30 = 30%). */
    public float $availDownPaymentRate = 0.30;

    /**
     * Days after approval the down payment may be paid. If it is not verified in
     * time the avail is cancelled ("cancelled - no down payment") and its package
     * amount leaves the balance.
     */
    public int $availDownPaymentDays = 30;

    /**
     * Smallest amount worth financing. When what is left after the down payment is below this (for
     * example a PHP 1,500 add-on), it is paid in full up front instead of in instalments.
     */
    public float $availMinFinancedAmount = 5000.0;

    /** Longest instalment term in months a Branch Admin may choose. */
    public int $availMaxTermMonths = 24;

    /** Terms a Branch Admin may choose from (only those up to availMaxTermMonths are offered). */
    public array $availTermOptions = [6, 12, 24];

    /** The terms actually offered: the options that do not exceed availMaxTermMonths. @return list<int> */
    public function availTerms(): array
    {
        $terms = array_values(array_filter(
            array_map('intval', $this->availTermOptions),
            fn (int $t): bool => $t > 0 && $t <= $this->availMaxTermMonths
        ));
        sort($terms);

        return $terms;
    }
}
