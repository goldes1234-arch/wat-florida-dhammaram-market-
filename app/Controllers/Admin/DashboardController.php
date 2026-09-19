<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;

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
            'upcomingEvents' => array_slice(Event::allForAdmin(), 0, 5),
        ], 'admin');
    }
}
