<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractor
{
    public function extract(UploadedFile $file): string
    {
        return match ($file->extension()) {
            'pdf'        => $this->extractPdf($file),
            'docx'       => $this->extractDocx($file),
            'xlsx', 'xls', 'csv' => $this->extractSpreadsheet($file),
            'txt', 'md'  => $file->get(),
            default      => throw new \InvalidArgumentException("Unsupported file type: {$file->extension()}"),
        };
    }

    private function extractPdf(UploadedFile $file): string
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($file->getRealPath());

        return $pdf->getText();
    }

    private function extractSpreadsheet(UploadedFile $file): string
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $lines       = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = $sheet->getTitle();
            if (count($spreadsheet->getAllSheets()) > 1) {
                $lines[] = "## {$title}";
            }

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $val = $cell->getFormattedValue();
                    if ($val !== '' && $val !== null) {
                        $cells[] = $val;
                    }
                }
                if ($cells) {
                    $lines[] = implode("\t", $cells);
                }
            }
        }

        return trim(implode("\n", $lines));
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
