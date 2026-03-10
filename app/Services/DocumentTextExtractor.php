<?php

namespace App\Services;

use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Extracts plain text from project documents for RAG indexing.
 *
 * Supported MIME types and their extraction method:
 *   application/pdf                                        → pdftotext CLI (poppler-utils)
 *   application/msword                                     → phpoffice/phpword
 *   application/vnd.openxmlformats-officedocument.word…   → phpoffice/phpword
 *   text/plain                                             → direct read
 *   text/html                                              → strip_tags()
 *   message/rfc822                                         → strip_tags() of HtmlBody
 *
 * System dependencies:
 *   PDF:  apt-get install poppler-utils  (provides pdftotext binary)
 *         OR composer require smalot/pdfparser (pure PHP, less accurate)
 *   DOCX: composer require phpoffice/phpword
 *
 * The Sail Docker image includes poppler-utils. Add to your Dockerfile:
 *   RUN apt-get install -y poppler-utils
 *
 * Chunking strategy:
 *   Target chunk size: CHUNK_CHARS characters (~500 tokens for nomic-embed-text)
 *   Overlap: OVERLAP_CHARS characters (preserves context at chunk boundaries)
 *   Splits prefer paragraph boundaries (\n\n) before hard character cuts.
 */
class DocumentTextExtractor
{
    private const CHUNK_CHARS   = 2000;
    private const OVERLAP_CHARS = 200;

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Extract text from a stored document and return as an array of text chunks.
     *
     * @param  ProjectDocument  $document
     * @return string[]          Ordered array of text chunks ready for embedding
     * @throws \RuntimeException if the file cannot be read or text cannot be extracted
     */
    public function extractChunks(ProjectDocument $document): array
    {
        $text = $this->extractText($document);

        if (empty(trim($text))) {
            return [];
        }

        return $this->chunk($text);
    }

    /**
     * Extract full text from a stored document (no chunking).
     */
    public function extractText(ProjectDocument $document): string
    {
        $disk = $document->storage_disk;
        $path = $document->storage_path;

        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException("Document file not found on disk '{$disk}': {$path}");
        }

        $mime = $document->mime_type ?? 'application/octet-stream';

        return match (true) {
            $mime === 'application/pdf'                                                            => $this->extractPdf($disk, $path),
            in_array($mime, ['application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])       => $this->extractDocx($disk, $path),
            str_starts_with($mime, 'text/')                                                        => $this->extractText_($disk, $path, $mime),
            default                                                                                 => throw new \RuntimeException("Unsupported MIME type for text extraction: {$mime}"),
        };
    }

    // ── Extraction Methods ─────────────────────────────────────────────────────

    private function extractPdf(string $disk, string $path): string
    {
        // Strategy 1: pdftotext CLI (poppler-utils — accurate, preserves layout)
        if ($this->commandExists('pdftotext')) {
            return $this->extractPdfViaCli($disk, $path);
        }

        // Strategy 2: smalot/pdfparser (pure PHP — install via composer)
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            return $this->extractPdfViaParser($disk, $path);
        }

        throw new \RuntimeException(
            'PDF text extraction requires either poppler-utils (pdftotext) or composer require smalot/pdfparser. ' .
            'Add poppler-utils to your Dockerfile or run: composer require smalot/pdfparser'
        );
    }

    private function extractPdfViaCli(string $disk, string $path): string
    {
        // Write to a temp file (pdftotext needs a real filesystem path)
        $tmpPath = tempnam(sys_get_temp_dir(), 'kore_pdf_');
        file_put_contents($tmpPath, Storage::disk($disk)->get($path));

        try {
            $output   = [];
            $exitCode = 0;
            exec(escapeshellcmd("pdftotext -layout " . escapeshellarg($tmpPath) . " -"), $output, $exitCode);

            if ($exitCode !== 0) {
                throw new \RuntimeException("pdftotext exited with code {$exitCode}");
            }

            return implode("\n", $output);
        } finally {
            @unlink($tmpPath);
        }
    }

    private function extractPdfViaParser(string $disk, string $path): string
    {
        $parser   = new \Smalot\PdfParser\Parser();
        $content  = Storage::disk($disk)->get($path);
        $pdf      = $parser->parseContent($content);
        return $pdf->getText();
    }

    private function extractDocx(string $disk, string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new \RuntimeException(
                'DOCX extraction requires: composer require phpoffice/phpword'
            );
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'kore_docx_') . '.docx';
        file_put_contents($tmpPath, Storage::disk($disk)->get($path));

        try {
            $phpWord  = \PhpOffice\PhpWord\IOFactory::load($tmpPath);
            $sections = $phpWord->getSections();
            $text     = '';

            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }

            return $text;
        } finally {
            @unlink($tmpPath);
        }
    }

    private function extractText_(string $disk, string $path, string $mime): string
    {
        $raw = Storage::disk($disk)->get($path);

        if ($mime === 'text/html') {
            return strip_tags($raw);
        }

        return $raw;
    }

    // ── Chunking ───────────────────────────────────────────────────────────────

    /**
     * Split text into overlapping chunks.
     *
     * Algorithm:
     *   1. Split by paragraph boundaries (\n\n) first
     *   2. Accumulate paragraphs into chunks up to CHUNK_CHARS
     *   3. When a chunk is full, emit it and start the next chunk with
     *      OVERLAP_CHARS of trailing text from the previous chunk
     *
     * @return string[]
     */
    public function chunk(string $text): array
    {
        $text = $this->normaliseWhitespace($text);

        if (strlen($text) <= self::CHUNK_CHARS) {
            return [$text];
        }

        $paragraphs = preg_split('/\n{2,}/', $text);
        $chunks     = [];
        $current    = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            // If adding this paragraph exceeds the target size, emit the current chunk
            if (strlen($current) + strlen($paragraph) + 2 > self::CHUNK_CHARS && $current !== '') {
                $chunks[] = trim($current);

                // Carry forward the last OVERLAP_CHARS as context for the next chunk
                $overlap = substr($current, -self::OVERLAP_CHARS);
                $current = $overlap . "\n\n" . $paragraph;
            } else {
                $current .= ($current ? "\n\n" : '') . $paragraph;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        // Handle edge case: a single very long paragraph with no \n\n breaks
        $finalChunks = [];
        foreach ($chunks as $chunk) {
            if (strlen($chunk) > self::CHUNK_CHARS * 1.5) {
                // Hard-split by character count with overlap
                $finalChunks = array_merge($finalChunks, $this->hardChunk($chunk));
            } else {
                $finalChunks[] = $chunk;
            }
        }

        return array_values(array_filter($finalChunks, fn ($c) => trim($c) !== ''));
    }

    private function hardChunk(string $text): array
    {
        $chunks = [];
        $len    = strlen($text);
        $offset = 0;

        while ($offset < $len) {
            $chunks[] = substr($text, $offset, self::CHUNK_CHARS);
            $offset  += self::CHUNK_CHARS - self::OVERLAP_CHARS;
        }

        return $chunks;
    }

    private function normaliseWhitespace(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function commandExists(string $command): bool
    {
        $result = shell_exec("which {$command} 2>/dev/null");
        return ! empty(trim((string) $result));
    }
}
