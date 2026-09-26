<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Advertisement;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Support\Validator;

class AdvertisementController
{
    public function form(Request $request): void
    {
        View::render('public/advertise/form', [
            'title' => __('ads.public_form_title'),
        ], 'public');
    }

    public function store(Request $request): void
    {
        // Honeypot: real visitors never see/fill this field.
        if ($request->trimmed('website') !== '') {
            redirect('advertise');
        }

        $settings = Setting::get();
        if (RateLimiter::tooMany($request->ip(), 'advertise', (int) $settings['booking_rate_limit_per_hour'])) {
            Flash::error(__('booking.rate_limited'));
            redirect('advertise');
        }

        $businessName = $request->trimmed('business_name');
        $description = $request->trimmed('description');
        $contactName = $request->trimmed('contact_name');
        $contactPhone = $request->trimmed('contact_phone');
        $linkUrl = $request->trimmed('link_url');
        $photoFile = $request->file('image');

        $errors = [];
        if (!Validator::required($businessName)) {
            $errors[] = __('validation.required', ['field' => __('settings.ads_business_name')]);
        }
        if (!Validator::required($description)) {
            $errors[] = __('validation.required', ['field' => __('ads.public_form_description')]);
        }
        if (!Validator::required($contactName)) {
            $errors[] = __('validation.required', ['field' => __('ads.public_form_contact_name')]);
        }
        if (!Validator::required($contactPhone)) {
            $errors[] = __('validation.required', ['field' => __('ads.public_form_contact_phone')]);
        }
        if (!$photoFile) {
            $errors[] = __('validation.required', ['field' => __('settings.ads_image')]);
        }
        if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            $errors[] = __('settings.ads_invalid_link');
        }

        if ($errors) {
            Flash::error(implode(' ', $errors));
            Flash::setOld($request->post);
            redirect('advertise');
        }

        $error = null;
        $path = Upload::storeImage($photoFile, 'ads', $error);
        if (!$path) {
            Flash::error($error);
            redirect('advertise');
        }

        $id = Advertisement::create($businessName, $path, $linkUrl ?: null, $description, 'pending', $contactName, $contactPhone);
        NotificationService::sendAdminAdSubmissionAlert(Advertisement::find($id));

        Flash::success(__('ads.public_form_success'));
        redirect('advertise');
    }
}
