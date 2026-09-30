<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Report;
use App\Models\Vendor;

class ReportController
{
    public function index(Request $request): void
    {
        $eventId = $request->query['event_id'] ?? '';
        $eventId = $eventId !== '' ? (int) $eventId : null;

        View::render('admin/reports/index', [
            'title' => __('report.title'),
            'active' => 'reports',
            'events' => Event::allForAdmin(),
            'selectedEventId' => $eventId,
            'summary' => Report::summary($eventId),
            'trend' => Report::dailyTrend($eventId),
            'revenueByZone' => Report::revenueByZone($eventId),
            'revenueByMethod' => Booking::revenueByPaymentMethod($eventId),
            'eventComparison' => $eventId === null ? Report::eventComparison() : [],
            'topVendors' => Vendor::topByRevenue(10),
        ], 'admin');
    }

    public function exportEvents(Request $request): void
    {
        $rows = Report::eventComparison();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="event-comparison-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            __('event.singular'), __('common.date'), __('report.total_lots'),
            __('report.booked_lots'), __('report.sell_through'), __('report.revenue'),
        ]);
        foreach ($rows as $r) {
            $total = (int) $r['total_lots'];
            $booked = (int) $r['booked_lots'];
            $sellThrough = $total > 0 ? round($booked / $total * 100, 1) : 0.0;
            fputcsv($out, [
                $r['name_th'], $r['start_date'], $total, $booked, $sellThrough . '%', $r['revenue'],
            ]);
        }
        fclose($out);
    }
}
