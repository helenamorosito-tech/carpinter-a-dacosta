<?php

namespace App\Http\Controllers;

use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CutSheetPreviewController extends Controller
{
    public function show(string $token, PresetCutSheetPdfGenerator $generator): Response
    {
        $payload = $generator->retrievePreview($token);

        abort_unless($payload !== null, 404);

        return response($payload['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$payload['filename'].'"',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    public function download(string $token, PresetCutSheetPdfGenerator $generator): SymfonyResponse
    {
        $payload = $generator->retrievePreview($token);

        abort_unless($payload !== null, 404);

        return response($payload['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$payload['filename'].'"',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }
}
