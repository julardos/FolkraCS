<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractor
{
    public function extract(UploadedFile $file): string
    {
        return match ($file->extension()) {
            'pdf'       => $this->extractPdf($file),
            'docx'      => $this->extractDocx($file),
            'txt', 'md' => $file->get(),
            default     => throw new \InvalidArgumentException("Unsupported file type: {$file->extension()}"),
        };
    }

    private function extractPdf(UploadedFile $file): string
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($file->getRealPath());

        return $pdf->getText();
    }

    private function extractDocx(UploadedFile $file): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new \RuntimeException('Could not open DOCX file.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('Invalid DOCX: missing word/document.xml.');
        }

        // Strip XML tags and decode entities
        $text = strip_tags(str_replace(['</w:p>', '</w:r>'], "\n", $xml));

        return trim(html_entity_decode($text));
    }
}
