<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;
use App\Services\AttentionService;

class DashboardController
{
    public function index(Request $request): void
    {
        View::render('admin/dashboard/index', [
            'title' => __('dashboard.title'),
            'active' => 'dashboard',
            'eventCounts' => Event::counts(),
            'bookingStats' => Booking::dashboardStats(),
            'revenue' => Lot::revenueBooked(),
            'lotStatusCounts' => Lot::statusCountsGlobal(),
            'revenueByMethod' => Booking::revenueByPaymentMethod(),
            'recentBookings' => Booking::recentForAdmin(8),
            // Only events that have not ended yet, soonest first.
            'upcomingEvents' => array_slice(array_values(array_filter(
                Event::allForAdmin(),
                static fn (array $e) => $e['end_date'] >= date('Y-m-d')
            )), 0, 5),
            'attention' => AttentionService::items(),
        ], 'admin');
    }
}
