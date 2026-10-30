<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /**
     * Serve uploaded media directly from storage/app/public safely.
     * Acts as an automatic fallback when the symbolic link is disabled on Hostinger.
     */
    public function servePublicStorage(Request $request, string $path): Response
    {
        // 1. Prevent Directory Traversal & Null Byte
        if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '%00')) {
            abort(400, 'Invalid media path.');
        }

        $basePath = realpath(storage_path('app/public'));
        if (!$basePath) {
            abort(404, 'Storage directory not found.');
        }

        $fullPath = storage_path('app/public/' . $path);
        $realFullPath = realpath($fullPath);

        // Security check: Must reside strictly inside storage/app/public
        if (!$realFullPath || !str_starts_with($realFullPath, $basePath) || !is_file($realFullPath)) {
            abort(404, 'Media file not found.');
        }

        // Security check: Only allow safe image and media extensions
        $ext = strtolower(pathinfo($realFullPath, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico', 'pdf'];
        if (!in_array($ext, $allowedExtensions, true)) {
            abort(403, 'Access to this file type is forbidden.');
        }

        // Map canonical MIME types
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        // Cache headers for high performance on shared hosting
        $response = new BinaryFileResponse($realFullPath);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', 'inline; filename="' . basename($realFullPath) . '"');
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
