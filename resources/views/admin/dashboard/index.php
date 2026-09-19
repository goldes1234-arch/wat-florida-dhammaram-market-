<div class="grid grid-cols-4 mb-6">
  <div class="stat-card">
    <div class="stat-label"><?= __('dashboard.published_events') ?></div>
    <div class="stat-value primary"><?= (int) ($eventCounts['published'] ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('dashboard.pending_bookings') ?></div>
    <div class="stat-value accent"><?= (int) ($bookingStats['pending'] ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('dashboard.confirmed_bookings') ?></div>
    <div class="stat-value success"><?= (int) ($bookingStats['booked'] ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('dashboard.revenue_booked') ?></div>
    <div class="stat-value"><?= money((float) $revenue) ?></div>
  </div>
</div>

<?php
$lotStatusLabels = ['available', 'pending_payment', 'booked', 'disabled'];
$lotStatusColors = ['#16A34A', '#E08B1D', '#5B4FE8', '#E23D5F'];
$lotStatusData = array_map(fn ($s) => (int) ($lotStatusCounts[$s] ?? 0), $lotStatusLabels);
$lotStatusTotal = array_sum($lotStatusData);

$paidCount = (int) ($bookingStats['booked'] ?? 0);
$unpaidCount = (int) ($bookingStats['pending'] ?? 0);
$paymentTotal = $paidCount + $unpaidCount;

$revenueMethodLabels = ['onsite_cash', 'bank_transfer', 'stripe'];
$revenueMethodColors = ['#E08B1D', '#5B4FE8', '#8B5CF6'];
$revenueMethodData = array_map(fn ($m) => (float) ($revenueByMethod[$m] ?? 0), $revenueMethodLabels);
$revenueMethodTotal = array_sum($revenueMethodData);
?>

<div class="grid grid-cols-3 mb-6">
  <div class="card chart-card">
    <div class="card-header"><h3><?= __('dashboard.chart_lot_status') ?></h3></div>
    <?php if ($lotStatusTotal > 0): ?>
      <div class="chart-canvas-wrap"><canvas id="chartLotStatus"></canvas></div>
      <div class="chart-legend">
        <?php foreach ($lotStatusLabels as $i => $s): ?>
          <div class="chart-legend-row">
            <span class="legend-name"><span class="dot" style="background:<?= $lotStatusColors[$i] ?>"></span><?= lot_status_label($s) ?></span>
            <span class="legend-value"><?= (int) $lotStatusData[$i] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="chart-empty"><?= __('dashboard.chart_no_data') ?></div>
    <?php endif; ?>
  </div>

  <div class="card chart-card">
    <div class="card-header"><h3><?= __('dashboard.chart_payment_status') ?></h3></div>
    <?php if ($paymentTotal > 0): ?>
      <div class="chart-canvas-wrap"><canvas id="chartPaymentStatus"></canvas></div>
      <div class="chart-legend">
        <div class="chart-legend-row">
          <span class="legend-name"><span class="dot" style="background:#16A34A"></span><?= __('dashboard.chart_paid') ?></span>
          <span class="legend-value"><?= $paidCount ?></span>
        </div>
        <div class="chart-legend-row">
          <span class="legend-name"><span class="dot" style="background:#E08B1D"></span><?= __('dashboard.chart_unpaid') ?></span>
          <span class="legend-value"><?= $unpaidCount ?></span>
        </div>
      </div>
    <?php else: ?>
      <div class="chart-empty"><?= __('dashboard.chart_no_data') ?></div>
    <?php endif; ?>
  </div>

  <div class="card chart-card">
    <div class="card-header"><h3><?= __('dashboard.chart_revenue_by_method') ?></h3></div>
    <?php if ($revenueMethodTotal > 0): ?>
      <div class="chart-canvas-wrap"><canvas id="chartRevenueMethod"></canvas></div>
      <div class="chart-legend">
        <?php foreach ($revenueMethodLabels as $i => $m): ?>
          <div class="chart-legend-row">
            <span class="legend-name"><span class="dot" style="background:<?= $revenueMethodColors[$i] ?>"></span><?= payment_method_label($m) ?></span>
            <span class="legend-value"><?= money((float) $revenueMethodData[$i]) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="chart-empty"><?= __('dashboard.chart_no_data') ?></div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  function loadChartJs(cb) {
    if (window.Chart) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js';
    s.onload = cb;
    document.head.appendChild(s);
  }

  loadChartJs(function () {
    var centerTextPlugin = {
      id: 'centerText',
      beforeDraw: function (chart) {
        var opts = chart.config.options.plugins && chart.config.options.plugins.centerText;
        if (!opts) return;
        var meta = chart.getDatasetMeta(0).data[0];
        var cx = meta ? meta.x : chart.width / 2;
        var cy = meta ? meta.y : chart.height / 2;
        var ctx = chart.ctx;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#14152B';
        ctx.font = "800 20px Inter, 'Noto Sans Thai', sans-serif";
        ctx.fillText(opts.value, cx, cy - 10);
        ctx.fillStyle = '#66677E';
        ctx.font = "600 11px Inter, 'Noto Sans Thai', sans-serif";
        ctx.fillText(opts.label, cx, cy + 11);
        ctx.restore();
      }
    };
    Chart.register(centerTextPlugin);

    function makeDonut(canvasId, labels, data, colors, centerValue, centerLabel) {
      var el = document.getElementById(canvasId);
      if (!el) return;
      new Chart(el, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: colors,
            borderColor: '#fff',
            borderWidth: 3,
            hoverOffset: 8,
          }]
        },
        options: {
          maintainAspectRatio: false,
          cutout: '72%',
          animation: { animateScale: true, animateRotate: true },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#14152B',
              padding: 10,
              cornerRadius: 8,
              titleFont: { family: 'Inter' },
              bodyFont: { family: 'Inter' },
            },
            centerText: { value: centerValue, label: centerLabel },
          }
        },
        plugins: [centerTextPlugin],
      });
    }

    <?php if ($lotStatusTotal > 0): ?>
    makeDonut(
      'chartLotStatus',
      <?= json_encode(array_map('lot_status_label', $lotStatusLabels), JSON_UNESCAPED_UNICODE) ?>,
      <?= json_encode($lotStatusData) ?>,
      <?= json_encode($lotStatusColors) ?>,
      <?= json_encode((string) $lotStatusTotal) ?>,
      <?= json_encode(__('dashboard.chart_total'), JSON_UNESCAPED_UNICODE) ?>
    );
    <?php endif; ?>

    <?php if ($paymentTotal > 0): ?>
    makeDonut(
      'chartPaymentStatus',
      <?= json_encode([__('dashboard.chart_paid'), __('dashboard.chart_unpaid')], JSON_UNESCAPED_UNICODE) ?>,
      <?= json_encode([$paidCount, $unpaidCount]) ?>,
      <?= json_encode(['#16A34A', '#E08B1D']) ?>,
      <?= json_encode((string) $paymentTotal) ?>,
      <?= json_encode(__('dashboard.chart_total'), JSON_UNESCAPED_UNICODE) ?>
    );
    <?php endif; ?>

    <?php if ($revenueMethodTotal > 0): ?>
    makeDonut(
      'chartRevenueMethod',
      <?= json_encode(array_map('payment_method_label', $revenueMethodLabels), JSON_UNESCAPED_UNICODE) ?>,
      <?= json_encode($revenueMethodData) ?>,
      <?= json_encode($revenueMethodColors) ?>,
      <?= json_encode(money((float) $revenueMethodTotal)) ?>,
      <?= json_encode(__('dashboard.revenue_booked'), JSON_UNESCAPED_UNICODE) ?>
    );
    <?php endif; ?>
  });
})();
</script>

<div class="grid grid-cols-3" style="grid-template-columns:2fr 1fr;">
  <div class="card">
    <div class="card-header">
      <h3><?= __('dashboard.recent_bookings') ?></h3>
      <a href="<?= base_url('admin/bookings') ?>" class="btn btn-secondary btn-sm"><?= __('common.view') ?></a>
    </div>
    <?php if (!$recentBookings): ?>
      <div class="table-empty"><?= __('booking.plural') ?>: —</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= __('booking.code') ?></th>
              <th><?= __('event.singular') ?></th>
              <th><?= __('booking.booker_name') ?></th>
              <th><?= __('common.status') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr onclick="location.href='<?= base_url('admin/bookings/' . $b['id']) ?>'" style="cursor:pointer;">
                <td><strong><?= e($b['booking_code']) ?></strong></td>
                <td><?= e($b['event_name_th']) ?></td>
                <td><?= e($b['booker_name']) ?></td>
                <td><span class="<?= booking_status_badge_class($b['status']) ?>"><?= booking_status_label($b['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('dashboard.quick_links') ?></h3></div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="<?= base_url('admin/events/create') ?>" class="btn btn-primary"><?= __('dashboard.create_event') ?></a>
      <a href="<?= base_url('admin/bookings?status=pending_payment') ?>" class="btn btn-secondary"><?= __('dashboard.review_bookings') ?></a>
    </div>

    <h3 class="mt-6"><?= __('dashboard.upcoming_events') ?></h3>
    <?php if (!$upcomingEvents): ?>
      <p class="text-muted text-sm"><?= __('event.no_events') ?></p>
    <?php else: ?>
      <?php foreach ($upcomingEvents as $ev): ?>
        <div class="status-log-item" style="padding:10px 0;">
          <div>
            <a href="<?= base_url('admin/events/' . $ev['id'] . '/edit') ?>"><?= e($ev['name_th']) ?></a>
            <div class="status-log-meta"><?= e(date('d/m/Y', strtotime($ev['start_date']))) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
