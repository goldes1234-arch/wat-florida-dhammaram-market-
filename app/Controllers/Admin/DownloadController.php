<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\DownloadCategory;
use App\Models\DownloadFile;

class DownloadController
{
    public function index(Request $request): void
    {
        $categories = DownloadCategory::allWithFileCounts();
        $filesByCategory = [];
        foreach ($categories as $category) {
            $filesByCategory[$category['id']] = DownloadFile::forCategory((int) $category['id']);
        }

        View::render('admin/downloads/index', [
            'title' => __('nav.downloads'),
            'active' => 'downloads',
            'categories' => $categories,
            'filesByCategory' => $filesByCategory,
        ], 'admin');
    }

    public function storeCategory(Request $request): void
    {
        $name = $request->trimmed('name');
        if ($name === '') {
            Flash::error(__('validation.generic_error'));
            redirect('admin/downloads');
        }

        DownloadCategory::create($name);
        Flash::success(__('downloads.category_added'));
        redirect('admin/downloads');
    }

    public function destroyCategory(Request $request, string $id): void
    {
        $category = DownloadCategory::find((int) $id);
        if ($category) {
            foreach (DownloadFile::forCategory((int) $id) as $file) {
                Upload::delete($file['file_path']);
            }
            DownloadCategory::delete((int) $id);
            Flash::success(__('downloads.category_deleted'));
        }
        redirect('admin/downloads');
    }

    public function storeFile(Request $request): void
    {
        $categoryId = (int) $request->input('category_id');
        $title = $request->trimmed('title');
        $category = DownloadCategory::find($categoryId);
        $file = $request->file('file');

        if (!$category || $title === '' || !$file) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/downloads');
        }

        $error = null;
        $path = Upload::storeFile($file, 'downloads', $error);
        if (!$path) {
            Flash::error($error);
            redirect('admin/downloads');
        }

        DownloadFile::create($categoryId, $title, $path, (int) $file['size']);
        Flash::success(__('downloads.file_added'));
        redirect('admin/downloads');
    }

    public function destroyFile(Request $request, string $id): void
    {
        $file = DownloadFile::find((int) $id);
        if ($file) {
            Upload::delete($file['file_path']);
            DownloadFile::delete((int) $id);
            Flash::success(__('downloads.file_deleted'));
        }
        redirect('admin/downloads');
    }
}
