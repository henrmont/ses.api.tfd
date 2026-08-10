<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;
// ATENÇÃO PARA O NAMESPACE DO FPDF/FPDI:
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class PdfMergerService
{
    public function generateMergedPdf(array $data, array $attachments): string
    {
        // Instancia a classe unificada do FPDI/FPDF
        $fpdi = new Fpdi();

        // 1. Renderiza a Capa a partir da view Blade
        $costAssistancePdfBinary = Pdf::loadView('pdf.cost-assistance', $data)->output();
        $this->appendPdfFromStream($fpdi, $costAssistancePdfBinary);

        // 2. Requerimento de ajuda de custo
        $this->appendAttachment($attachments[0], $fpdi);

        // 3. Declaração médica
        $this->appendAttachment($attachments[1], $fpdi);

        // 4. Parecer médico
        $medicalOpinionPdfBinary = Pdf::loadView('pdf.medical-opinion', $data)->output();
        $this->appendPdfFromStream($fpdi, $medicalOpinionPdfBinary);

        // 5. Passagem
        $travelPdfBinary = Pdf::loadView('pdf.travel', $data)->output();
        $this->appendPdfFromStream($fpdi, $travelPdfBinary);

        // 6. Itera sobre cada anexo
        foreach ($data['travel']['passengers'] as $passenger) {
            try {
                if (!empty($passenger['patient']['file_document'])) {
                    $this->appendAttachment($passenger['patient']['file_document'], $fpdi);
                }
                if (!empty($passenger['escort']['file_document'])) {
                    $this->appendAttachment($passenger['escort']['file_document'], $fpdi);
                }
            } catch (Exception $e) {
                Log::error("Falha ao mesclar documentos do passageiro: " . $e->getMessage());
            }
        }

        // 7. Comprovante bancário
        $this->appendAttachment($attachments[2], $fpdi);

        // 8. Situação cadastral
        $this->appendAttachment($attachments[3], $fpdi);

        // Tente chamar o método com 'Output' maiúsculo ou 'output' minúsculo via FPDF
        return $fpdi->Output('S');
    }

    public function generateMemoPdf(array $data): string
    {
        // Instancia a classe unificada do FPDI/FPDF
        $fpdi = new Fpdi();

        // 1. Renderiza a Capa a partir da view Blade
        $paymentPdfBinary = Pdf::loadView('pdf.payment-memo', $data)->output();
        $this->appendPdfFromStream($fpdi, $paymentPdfBinary);
        
        return $fpdi->Output('S');
    }

    private function appendAttachment(array $attachment, Fpdi $fpdi)
    {
        try {
            $base64 = $attachment['content'] ?? $attachment['archive'] ?? '';
            if (empty($base64)) {
                return;
            }

            if ($this->isPdfBase64($base64)) {
                $pdfBinary = $this->decodeBase64($base64);
                $this->appendPdfFromStream($fpdi, $pdfBinary);
            } else {
                // Define o tamanho da margem em milímetros (ex: 10mm)
                $marginMm = 15;
                $imagePdfBinary = $this->convertImageBase64ToPdf($base64, $marginMm);
                $this->appendPdfFromStream($fpdi, $imagePdfBinary);
            }
        } catch (Exception $e) {
            Log::error("Falha ao mesclar anexo ID {$attachment['id']}: " . $e->getMessage());
        }
    }

    private function appendPdfFromStream(Fpdi $fpdi, string $pdfContent): void
    {
        // StreamReader decodifica a string binária para o FPDI
        $stream = StreamReader::createByString($pdfContent);
        $pageCount = $fpdi->setSourceFile($stream);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $fpdi->importPage($pageNo);
            $size = $fpdi->getTemplateSize($templateId);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }
    }

    private function convertImageBase64ToPdf(string $imageBase64, string $margin = '15mm'): string
    {
        if (!str_contains($imageBase64, 'data:image')) {
            $imageBase64 = 'data:image/jpeg;base64,' . $imageBase64;
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                @page { 
                    margin: ' . $margin . '; 
                }
                body { 
                    margin: 0px; 
                    padding: 0px;
                    background: #ffffff; 
                    text-align: center; 
                }
                .wrapper { 
                    width: 100%; 
                    height: 100vh; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                }
                img { 
                    max-width: 100%; 
                    max-height: 100%; 
                    object-fit: contain; 
                }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <img src="' . $imageBase64 . '" />
            </div>
        </body>
        </html>';

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    private function isPdfBase64(string $base64): bool
    {
        return str_contains($base64, 'data:application/pdf') || str_starts_with(ltrim($base64), 'JVBERi0');
    }

    private function decodeBase64(string $base64): string
    {
        if (preg_match('/^data:.*;base64,/', $base64, $matches)) {
            $base64 = substr($base64, strlen($matches[0]));
        }
        return base64_decode($base64);
    }
}