<?php
/** @var array $events */
/** @var int|null $selectedEventId */
/** @var array $summary */
/** @var array $trend */
/** @var array $revenueByZone */
/** @var array $revenueByMethod */
/** @var array $eventComparison */
/** @var array $topVendors */

$revenueMethodLabels = ['onsite_cash', 'bank_transfer', 'stripe'];
$revenueMethodColors = ['#E08B1D', '#5B4FE8', '#8B5CF6'];
$revenueMethodData = array_map(fn ($m) => (float) ($revenueByMethod[$m] ?? 0), $revenueMethodLabels);
$revenueMethodTotal = array_sum($revenueMethodData);

$zoneColors = ['#5B4FE8', '#16A34A', '#E08B1D', '#E23D5F', '#8B5CF6', '#0EA5A4', '#2563EB', '#DB2777'];

$trendDates = array_map(fn ($r) => $r['d'], $trend);
$trendBookings = array_map(fn ($r) => (int) $r['bookings'], $trend);
$trendCumulativeRevenue = [];
$running = 0.0;
foreach ($trend as $r) {
    $running += (float) $r['revenue'];
    $trendCumulativeRevenue[] = $running;
}
?>

<div class="page-header">
  <h1><?= __('report.title') ?></h1>
</div>

<form method="get" action="<?= base_url('admin/reports') ?>" class="filter-bar mb-6">
  <div class="form-group">
    <label><?= __('report.filter_event') ?></label>
    <select name="event_id" class="form-control" onchange="this.form.submit()">
      <option value=""><?= __('report.all_events') ?></option>
      <?php foreach ($events as $ev): ?>
        <option value="<?= (int) $ev['id'] ?>" <?= $selectedEventId === (int) $ev['id'] ? 'selected' : '' ?>>
          <?= e($ev['name_th']) ?> (<?= e(date('d/m/Y', strtotime($ev['start_date']))) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <noscript><div class="form-group" style="flex:0 0 auto;align-self:flex-end;"><button type="submit" class="btn btn-secondary"><?= __('common.search') ?></button></div></noscript>
</form>

<div class="grid grid-cols-4 mb-6">
  <div class="stat-card">
    <div class="stat-label"><?= __('report.revenue') ?></div>
    <div class="stat-value primary"><?= money((float) $summary['revenue']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('report.booked_lots') ?></div>
    <div class="stat-value success"><?= (int) $summary['booked_lots'] ?> / <?= (int) $summary['total_lots'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('report.sell_through') ?></div>
    <div class="stat-value accent"><?= e((string) $summary['sell_through_pct']) ?>%</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('report.avg_price') ?></div>
    <div class="stat-value"><?= money((float) $summary['avg_price']) ?></div>
  </div>
</div>

<div class="card mb-6">
  <div class="card-header"><h3><?= __('report.chart_trend') ?></h3></div>
  <?php if (!$trend): ?>
    <div class="chart-empty"><?= __('dashboard.chart_no_data') ?></div>
  <?php else: ?>
    <div class="chart-canvas-wrap" style="height:280px;"><canvas id="chartTrend"></canvas></div>
  <?php endif; ?>
</div>

<div class="grid grid-cols-2 mb-6">
  <div class="card chart-card">
    <div class="card-header"><h3><?= __('report.chart_revenue_by_zone') ?></h3></div>
    <?php if (!$revenueByZone): ?>
      <div class="chart-empty"><?= __('dashboard.chart_no_data') ?></div>
    <?php else: ?>
      <div class="chart-canvas-wrap"><canvas id="chartZone"></canvas></div>
      <div class="chart-legend">
        <?php foreach ($revenueByZone as $i => $z): ?>
          <div class="chart-legend-row">
            <span class="legend-name"><span class="dot" style="background:<?= $zoneColors[$i % count($zoneColors)] ?>"></span><?= e($z['zone_name'] ?? __('lot.no_zone')) ?></span>
            <span class="legend-value"><?= money((float) $z['revenue']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card chart-card">
    <div class="card-header"><h3><?= __('dashboard.chart_revenue_by_method') ?></h3></div>
    <?php if ($revenueMethodTotal > 0): ?>
      <div class="chart-canvas-wrap"><canvas id="chartMethod"></canvas></div>
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

<?php if ($selectedEventId === null): ?>
  <div class="card mb-6">
    <div class="card-header">
      <h3><?= __('report.event_comparison') ?></h3>
      <a href="<?= base_url('admin/reports/export-events') ?>" class="btn btn-secondary btn-sm"><?= icon('download') ?> <?= __('common.export_csv') ?></a>
    </div>
    <?php if (!$eventComparison): ?>
      <div class="table-empty"><?= __('event.no_events') ?></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th><?= __('event.singular') ?></th>
              <th><?= __('common.date') ?></th>
              <th><?= __('report.booked_lots') ?></th>
              <th><?= __('report.sell_through') ?></th>
              <th><?= __('report.revenue') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($eventComparison as $ev): ?>
              <?php
              $total = (int) $ev['total_lots'];
              $booked = (int) $ev['booked_lots'];
              $sellThrough = $total > 0 ? round($booked / $total * 100, 1) : 0.0;
              ?>
              <tr onclick="location.href='<?= base_url('admin/reports?event_id=' . $ev['id']) ?>'" style="cursor:pointer;">
                <td><strong><?= e($ev['name_th']) ?></strong></td>
                <td><?= e(date('d/m/Y', strtotime($ev['start_date']))) ?></td>
                <td><?= $booked ?> / <?= $total ?></td>
                <td><?= e((string) $sellThrough) ?>%</td>
                <td><?= money((float) $ev['revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3><?= __('report.top_vendors') ?></h3></div>
  <?php if (!$topVendors): ?>
    <div class="table-empty"><?= __('vendor.none') ?></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><?= __('vendor.name') ?></th>
            <th><?= __('vendor.phone') ?></th>
            <th><?= __('vendor.booking_count') ?></th>
            <th><?= __('report.revenue') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topVendors as $v): ?>
            <tr onclick="location.href='<?= base_url('admin/vendors/' . $v['id']) ?>'" style="cursor:pointer;">
              <td><strong><?= e($v['name']) ?></strong></td>
              <td><?= e($v['phone']) ?></td>
              <td><span class="badge badge-indigo"><?= (int) $v['booking_count'] ?></span></td>
              <td><?= money((float) $v['revenue']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
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
    <?php if ($trend): ?>
    new Chart(document.getElementById('chartTrend'), {
      data: {
        labels: <?= json_encode($trendDates) ?>,
        datasets: [
          {
            type: 'bar',
            label: <?= json_encode(__('report.trend_bookings'), JSON_UNESCAPED_UNICODE) ?>,
            data: <?= json_encode($trendBookings) ?>,
            backgroundColor: '#5B4FE8',
            yAxisID: 'yBookings',
            borderRadius: 4,
          },
          {
            type: 'line',
            label: <?= json_encode(__('report.trend_cumulative_revenue'), JSON_UNESCAPED_UNICODE) ?>,
            data: <?= json_encode($trendCumulativeRevenue) ?>,
            borderColor: '#16A34A',
            backgroundColor: '#16A34A',
            yAxisID: 'yRevenue',
            tension: 0.3,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          yBookings: { position: 'left', beginAtZero: true, ticks: { precision: 0 } },
          yRevenue: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
        },
        plugins: {
          legend: { position: 'bottom' },
        },
      },
    });
    <?php endif; ?>

    <?php if ($revenueByZone): ?>
    new Chart(document.getElementById('chartZone'), {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_map(fn ($z) => $z['zone_name'] ?? __('lot.no_zone'), $revenueByZone), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          data: <?= json_encode(array_map(fn ($z) => (float) $z['revenue'], $revenueByZone)) ?>,
          backgroundColor: <?= json_encode(array_map(fn ($i) => $zoneColors[$i % count($zoneColors)], array_keys($revenueByZone))) ?>,
          borderColor: '#fff',
          borderWidth: 3,
        }],
      },
      options: {
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } },
      },
    });
    <?php endif; ?>

    <?php if ($revenueMethodTotal > 0): ?>
    new Chart(document.getElementById('chartMethod'), {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_map('payment_method_label', $revenueMethodLabels), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          data: <?= json_encode($revenueMethodData) ?>,
          backgroundColor: <?= json_encode($revenueMethodColors) ?>,
          borderColor: '#fff',
          borderWidth: 3,
        }],
      },
      options: {
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } },
      },
    });
    <?php endif; ?>
  });
})();
</script>
