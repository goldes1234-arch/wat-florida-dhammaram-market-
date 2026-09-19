<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Session;

class LocaleController
{
    public function switch(Request $request, string $locale): void
    {
        Session::put('locale', in_array($locale, ['th', 'en'], true) ? $locale : 'th');

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $home = full_url('');
        if ($referer !== '' && str_starts_with($referer, $home)) {
            header('Location: ' . $referer);
            exit;
        }

        redirect('');
    }
}
