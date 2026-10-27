<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

abstract class Controller
{
    /**
     * PDF をブラウザで開く (旧 $pdf->stream('日報') と同じ inline・同じファイル名)。
     * dompdf 3 の stream() は ASCII 以外のファイル名で例外になるので、ASCII の代わりの名前を添えて自分で返す。
     */
    protected function pdfInline(PDF $pdf, string $filename, string $asciiFallback): Response
    {
        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $asciiFallback),
        ]);
    }
}
