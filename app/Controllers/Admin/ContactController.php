<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\ContactMessage;

class ContactController
{
    public function index(Request $request): void
    {
        View::render('admin/contacts/index', [
            'title' => __('contact.admin_title'),
            'active' => 'contacts',
            'messages' => ContactMessage::allForAdmin(),
        ], 'admin');
    }

    public function markRead(Request $request, string $id): void
    {
        ContactMessage::markRead((int) $id);
        redirect('admin/contacts');
    }
}
