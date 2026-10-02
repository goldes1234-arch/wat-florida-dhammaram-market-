<?php
/** @var array $settings */
/** @var array $policy */
$org = $settings['org_name'] ?: __('common.app_name');
$contactEmail = $settings['org_email'] ?? '';
?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header"><h1>Terms of Use</h1></div>
  <p class="text-muted mb-6">These terms apply when you use this website to book a stall at an event run by <?= e($org) ?>. By submitting a booking you agree to them.</p>

  <h2>Bookings</h2>
  <ul>
    <li>A stall is held for you once your booking is submitted. Bookings paid by bank transfer or at the temple must be paid as instructed on the confirmation page, and the temple may release the stall if payment is not received in time.</li>
    <li>A booking is confirmed only when its status shows <em>Booked</em>. Keep your booking code; it identifies your booking.</li>
    <li>Please provide accurate contact details so we can reach you about your booking.</li>
  </ul>

  <h2>Cancellation and refunds</h2>
  <ul>
    <li>To release your stall you must cancel at least <?= (int) $policy['days'] ?> days before the event starts. This applies to regular vendors and to every payment method.</li>
    <li>Customers who paid online by card are refunded in full only if they cancelled at least <?= (int) $policy['refund_days'] ?> days before the event starts. Refunds go back to the original card through Stripe and may take several business days to appear.</li>
    <li>After that date, or if you do not attend, please contact the temple; any refund is at the temple's discretion.</li>
    <li>If the temple cancels an event, it will contact you about a refund.</li>
  </ul>

  <h2>Regular vendors</h2>
  <p>Stalls reserved for regular vendors must be confirmed by the deadline shown in the invitation (by email link, LINE, or by telling the temple). Unconfirmed reservations are released automatically so the stall can be offered to others.</p>

  <h2>At the event</h2>
  <ul>
    <li>Follow the instructions of temple staff and volunteers, and keep to your assigned stall.</li>
    <li>Sell only items consistent with the temple's guidelines and applicable law; the temple may ask you to stop selling or leave.</li>
    <li>You are responsible for your own goods, equipment, permits and any taxes that apply to your sales.</li>
  </ul>

  <h2>Using this website</h2>
  <p>Do not misuse the site: no automated bulk booking, attempts to access other people's bookings or the administration area, or interfering with its operation. We may limit or block access to protect the service.</p>

  <h2>Liability</h2>
  <p>The service is provided as is. To the extent allowed by law, the temple is not liable for indirect or consequential losses, or for losses from events outside its control such as severe weather. Nothing here limits any rights you have that cannot be limited by law.</p>

  <h2>Privacy</h2>
  <p>How we handle your information is described in our <a href="<?= base_url('privacy') ?>">Privacy Policy</a>.</p>

  <h2>Changes and contact</h2>
  <p>We may update these terms; the current version is on this page. Questions: <?= e($org) ?><?php if ($contactEmail): ?>, <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?><?php if (!empty($settings['org_phone'])): ?>, <?= e($settings['org_phone']) ?><?php endif; ?>.</p>
</div>
