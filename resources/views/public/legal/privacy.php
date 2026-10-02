<?php
/** @var array $settings */
$org = $settings['org_name'] ?: __('common.app_name');
$contactEmail = $settings['org_email'] ?? '';
?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header"><h1>Privacy Policy</h1></div>
  <p class="text-muted mb-6">How <?= e($org) ?> ("we", "us") handles information collected through this stall-booking website.</p>

  <h2>Information we collect</h2>
  <ul>
    <li><strong>Bookings:</strong> your name, phone number and email address, the stall and event you booked, the payment method you chose, and the booking's status history.</li>
    <li><strong>Regular vendors:</strong> the same contact details, notes the temple keeps about your reservations, and, if you choose to link it, your LINE account identifier.</li>
    <li><strong>Messages you send us:</strong> contact-form and advertising enquiries.</li>
    <li><strong>Technical data:</strong> a session cookie that keeps you signed in or protects forms from forgery, and standard server logs (IP address, browser type, time of request).</li>
  </ul>
  <p>We do not store your card number. Card payments are handled by Stripe; we only receive a confirmation that the payment succeeded and its reference.</p>

  <h2>How we use it</h2>
  <ul>
    <li>To reserve and manage your stall, confirm bookings, issue receipts and process refunds.</li>
    <li>To contact you about your booking or reservation by email, phone or, if you linked it, LINE.</li>
    <li>To keep financial and event records, prevent abuse, and secure the site.</li>
  </ul>
  <p>We do not sell your personal information and we do not use it for advertising.</p>

  <h2>Who we share it with</h2>
  <p>Only service providers needed to run the booking service, and only what they need:</p>
  <ul>
    <li><strong>Stripe</strong>, to process online card payments.</li>
    <li><strong>LINE</strong>, to message vendors who linked their LINE account to the temple's Official Account.</li>
    <li><strong>Our email and hosting providers</strong>, to deliver email and store data.</li>
  </ul>
  <p>We may also disclose information when the law requires it.</p>

  <h2>How long we keep it</h2>
  <p>We keep booking and payment records for as long as needed for the temple's accounting and record-keeping, and keep vendor contact details while you remain a regular vendor. You can ask us to delete your information (see below); we may need to keep records of past bookings and payments where required for accounting or legal reasons.</p>

  <h2>Your choices</h2>
  <ul>
    <li><strong>Regular vendors</strong> can download a copy of their data and request deletion from their vendor portal (reached by messaging the temple's LINE Official Account).</li>
    <li>Anyone can ask us to correct or delete their information, or stop contacting them, by contacting us<?php if ($contactEmail): ?> at <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?>.</li>
  </ul>
  <p>Residents of some states have additional rights under their state's privacy law; contact us and we will honor requests as required by applicable law.</p>

  <h2>Security</h2>
  <p>The site is served over HTTPS, administrator accounts are protected by passwords and optional two-factor authentication, and access to personal information is limited to temple staff who need it. No online service can be guaranteed perfectly secure.</p>

  <h2>Children</h2>
  <p>This service is intended for adults. We do not knowingly collect information from children under 13.</p>

  <h2>Changes</h2>
  <p>We may update this policy from time to time; the current version is always on this page.</p>

  <h2>Contact</h2>
  <p><?= e($org) ?><?php if (!empty($settings['org_address'])): ?><br><?= e($settings['org_address']) ?><?php endif; ?><?php if (!empty($settings['org_phone'])): ?><br><?= e($settings['org_phone']) ?><?php endif; ?><?php if ($contactEmail): ?><br><?= e($contactEmail) ?><?php endif; ?></p>

  <p class="text-muted text-sm mt-6">See also our <a href="<?= base_url('terms') ?>">Terms of Use</a>.</p>
</div>
