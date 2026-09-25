<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\GalleryPhoto;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Services\CurrencyService;

class SettingsController
{
    public function edit(Request $request): void
    {
        View::render('admin/settings/edit', [
            'title' => __('settings.title'),
            'active' => 'settings',
            'settings' => Setting::get(true),
            'currencies' => CurrencyService::options(),
            'socialLinks' => SocialLink::all(),
            'galleryPhotos' => GalleryPhoto::all(),
        ], 'admin');
    }

    public function update(Request $request): void
    {
        $data = [
            'org_name' => $request->trimmed('org_name'),
            'org_address' => $request->trimmed('org_address'),
            'org_phone' => $request->trimmed('org_phone'),
            'org_email' => $request->trimmed('org_email'),
            'facebook_url' => $request->trimmed('facebook_url'),
            'line_oa_id' => $request->trimmed('line_oa_id'),
            'google_maps_url' => $request->trimmed('google_maps_url'),
            'website_url' => $request->trimmed('website_url'),
            'youtube_url' => $request->trimmed('youtube_url'),
            'currency_code' => $request->trimmed('currency_code') ?: 'THB',
            'default_locale' => in_array($request->trimmed('default_locale'), ['th', 'en'], true)
                ? $request->trimmed('default_locale') : 'th',
            'cancellation_cutoff_days' => max(0, (int) $request->input('cancellation_cutoff_days', 0)),
            'booking_rate_limit_per_hour' => max(1, (int) $request->input('booking_rate_limit_per_hour', 5)),
            'stripe_publishable_key' => $request->trimmed('stripe_publishable_key'),
            'stripe_secret_key' => $request->trimmed('stripe_secret_key'),
            'stripe_webhook_secret' => $request->trimmed('stripe_webhook_secret'),
            'stripe_pass_fee_to_customer' => $request->input('stripe_pass_fee_to_customer') ? 1 : 0,
            'stripe_fee_percent' => max(0, (float) $request->input('stripe_fee_percent', 2.9)),
            'stripe_fee_fixed' => max(0, (float) $request->input('stripe_fee_fixed', 0.30)),
            'line_oa_channel_access_token' => $request->trimmed('line_oa_channel_access_token'),
            'smtp_host' => $request->trimmed('smtp_host'),
            'smtp_port' => $request->input('smtp_port') !== '' && $request->input('smtp_port') !== null
                ? (int) $request->input('smtp_port') : null,
            'smtp_encryption' => in_array($request->trimmed('smtp_encryption'), ['tls', 'ssl', 'none'], true)
                ? $request->trimmed('smtp_encryption') : 'tls',
            'smtp_username' => $request->trimmed('smtp_username'),
            'smtp_password' => $request->trimmed('smtp_password'),
            'smtp_from_email' => $request->trimmed('smtp_from_email'),
            'smtp_from_name' => $request->trimmed('smtp_from_name'),
        ];

        $logoFile = $request->file('logo');
        if ($logoFile) {
            $error = null;
            $path = Upload::storeImage($logoFile, 'branding', $error);
            if ($path) {
                Upload::delete(Setting::get()['logo_path'] ?? null);
                $data['logo_path'] = $path;
            } else {
                Flash::error($error);
            }
        } elseif ($request->input('remove_logo')) {
            Upload::delete(Setting::get()['logo_path'] ?? null);
            $data['logo_path'] = null;
        }

        $heroFile = $request->file('hero_banner_image');
        if ($heroFile) {
            $error = null;
            $path = Upload::storeImage($heroFile, 'branding', $error);
            if ($path) {
                Upload::delete(Setting::get()['hero_banner_image'] ?? null);
                $data['hero_banner_image'] = $path;
            } else {
                Flash::error($error);
            }
        } elseif ($request->input('remove_hero_banner_image')) {
            Upload::delete(Setting::get()['hero_banner_image'] ?? null);
            $data['hero_banner_image'] = null;
        }

        Setting::update($data);
        Flash::success(__('settings.save_success'));
        redirect('admin/settings');
    }

    public function testEmail(Request $request): void
    {
        $settings = Setting::get();
        $to = $settings['org_email'] ?? '';
        if (!$to) {
            Flash::error(__('settings.test_email_no_recipient'));
            redirect('admin/settings');
        }

        $sent = Mailer::send(
            $to,
            __('settings.test_email_subject'),
            '<p>' . __('settings.test_email_body') . '</p>'
        );

        if ($sent) {
            Flash::success(__('settings.test_email_success', ['email' => $to]));
        } else {
            Flash::error(__('settings.test_email_failed'));
        }
        redirect('admin/settings');
    }

    public function storeSocialLink(Request $request): void
    {
        $label = $request->trimmed('label');
        $url = $request->trimmed('url');

        if ($label === '' || $url === '') {
            Flash::error(__('validation.generic_error'));
            redirect('admin/settings');
        }

        SocialLink::create($label, $url);
        Flash::success(__('settings.social_link_added'));
        redirect('admin/settings');
    }

    public function updateSocialLink(Request $request, string $id): void
    {
        $link = SocialLink::find((int) $id);
        if (!$link) {
            redirect('admin/settings');
        }

        $label = $request->trimmed('label');
        $url = $request->trimmed('url');
        if ($label !== '' && $url !== '') {
            SocialLink::update((int) $id, $label, $url);
        }
        redirect('admin/settings');
    }

    public function destroySocialLink(Request $request, string $id): void
    {
        SocialLink::delete((int) $id);
        Flash::success(__('settings.social_link_removed'));
        redirect('admin/settings');
    }

    public function storeGalleryPhoto(Request $request): void
    {
        $photoFile = $request->file('photo');
        if (!$photoFile) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/settings');
        }

        $error = null;
        $path = Upload::storeImage($photoFile, 'gallery', $error);
        if (!$path) {
            Flash::error($error);
            redirect('admin/settings');
        }

        GalleryPhoto::create($path, $request->trimmed('caption'));
        Flash::success(__('settings.gallery_photo_added'));
        redirect('admin/settings');
    }

    public function destroyGalleryPhoto(Request $request, string $id): void
    {
        $photo = GalleryPhoto::find((int) $id);
        if ($photo) {
            Upload::delete($photo['image_path']);
            GalleryPhoto::delete((int) $id);
            Flash::success(__('settings.gallery_photo_removed'));
        }
        redirect('admin/settings');
    }
}
