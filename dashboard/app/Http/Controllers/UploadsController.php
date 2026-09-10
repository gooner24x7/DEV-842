<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadsController extends Controller
{
    public function download(Request $request): Response
    {
        $file = $request->query->get('file');
        if (!$file) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        return response()->download(storage_path('app') . '/' . $file, basename($file), [
            'Content-Type' => 'application/octet-stream',
            'Content-Transfer-Encoding' => 'Binary',
            'Content-disposition' => 'attachment; filename="' . basename($file) . '"',
        ]);
    }
}
