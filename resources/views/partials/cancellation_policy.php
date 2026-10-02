<?php
/** The cancellation/refund condition customers agree to — one wording, shown everywhere it matters. */
$policy = \App\Services\BookingService::policyDays();
?>
<div class="card" style="background:var(--color-accent-light);border-color:var(--color-warning-light);text-align:left;">
  <p class="text-sm mb-0">ℹ️ <?= e(__('public.cancellation_policy', $policy)) ?></p>
</div>
