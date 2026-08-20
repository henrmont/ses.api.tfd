<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Professional;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class PaymentService
{
    /**
     * Conexão com o banco padrão (TFD).
     */
    private function tfd(): Connection
    {
        return DB::connection();
    }

    /*
    |--------------------------------------------------------------------------
    | Tramitação e Mudança de Estados do Pagamento
    |--------------------------------------------------------------------------
    */

    /**
     * Alternar marcação de sobrestado/paralisação do pagamento.
     */
    public function haltedPayment(Payment $payment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $payment->update([
                'is_payment_bookmark' => !$payment->is_payment_bookmark,
            ]);

            $this->tfd()->commit();

            $message = $payment->is_payment_bookmark
                ? 'Solicitação marcada em sobrestado.'
                : 'Solicitação desmarcada em sobrestado.';

            return response()->json(['message' => $message], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao alternar sobrestado do pagamento: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Arquivar etapa de pagamento da solicitação.
     */
    public function archivePayment(Payment $payment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $payment->update([
                'is_payment_archived' => true,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação arquivada com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao arquivar pagamento: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Atualizar dados cadastrais do pagamento.
     */
    public function updatePayment(Payment $payment, Request $request): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $payment->update($request->all());

            $this->tfd()->commit();

            return response()->json(['message' => 'Pagamento atualizado com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao atualizar pagamento: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Restaurar pagamento arquivado.
     */
    public function movePaymentFromArchive(Payment $payment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $payment->update([
                'is_payment_archived' => false,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação retirada do arquivo.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover pagamento do arquivo: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Transferir pagamento a partir do setor "Outros" para o profissional atual.
     */
    public function movePaymentFromOthers(Payment $payment): JsonResponse
    {
        try {
            $this->tfd()->beginTransaction();

            $professionalId = Professional::where('user_id', auth()->id())->value('id');

            $payment->update([
                'payment_professional_id' => $professionalId,
            ]);

            $this->tfd()->commit();

            return response()->json(['message' => 'Solicitação transferida com sucesso.'], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->tfd()->rollBack();

            Log::error('Erro ao mover pagamento de outros: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Geração e Exportação de PDFs
    |--------------------------------------------------------------------------
    */

    /**
     * Gerar e realizar o download do PDF mesclado completo da solicitação.
     */
    public function downloadMergedPdf(Payment $payment): Response|JsonResponse
    {
        try {
            $payment->load([
                'paymentAttachments',
                'patientRequest.report.patientCare.patient',
                'patientRequest.report.cid',
                'patientRequest.travels.passengers.patient',
                'patientRequest.travels.passengers.escort',
                'patientRequest.hospitalUnity',
                'patientRequest.medicalProfessional',
                'patientRequest.travelProfessional',
                'costAssistance.passenger.patient',
                'costAssistance.passenger.escort',
                'travel.passengers.patient.fileDocument',
                'travel.passengers.escort.fileDocument',
                'travel.travelRoutes',
            ]);

            $attachments = $payment->paymentAttachments->map(fn($item) => [
                'id'      => $item->patientRequestAttachment->id ?? null,
                'content' => $item->patientRequestAttachment->archive->archive ?? '',
            ])->toArray();

            $pdfBinary = $this->generateMergedPdf($payment->toArray(), $attachments);
            $filename  = "processo_{$payment->id}_completo.pdf";

            return response($pdfBinary, JsonResponse::HTTP_OK, [
                'Content-Type'                  => 'application/pdf',
                'Content-Disposition'           => "attachment; filename=\"{$filename}\"",
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao gerar PDF mesclado do pagamento: ' . $e->getMessage());

            return response()->json(['message' => 'Erro ao gerar o PDF mesclado.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Gerar e realizar o download do PDF do Memorando de pagamento.
     */
    public function downloadMemoPdf(Payment $payment): Response|JsonResponse
    {
        try {
            $payment->load([
                'paymentAttachments',
                'patientRequest.report.patientCare.patient',
                'patientRequest.report.cid',
                'patientRequest.travels.passengers.patient',
                'patientRequest.travels.passengers.escort',
                'patientRequest.hospitalUnity',
                'patientRequest.medicalProfessional',
                'patientRequest.travelProfessional',
                'costAssistance.passenger.patient',
                'costAssistance.passenger.escort',
                'costAssistance.costAssistanceDailies.dailyCost',
                'travel.passengers.patient.fileDocument',
                'travel.passengers.escort.fileDocument',
                'travel.travelRoutes',
            ]);

            $pdfBinary = $this->generateMemoPdf($payment->toArray());
            $filename  = "memorando_{$payment->document_number}.pdf";

            return response($pdfBinary, JsonResponse::HTTP_OK, [
                'Content-Type'                  => 'application/pdf',
                'Content-Disposition'           => "attachment; filename=\"{$filename}\"",
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao gerar PDF do memorando de pagamento: ' . $e->getMessage());

            return response()->json(['message' => 'Erro ao gerar o memorando.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos Privados Auxiliares para Mesclagem de PDFs
    |--------------------------------------------------------------------------
    */

    private function generateMergedPdf(array $data, array $attachments): string
    {
        $fpdi = new Fpdi();

        // 1. Capa da ajuda de custo
        $costAssistancePdfBinary = Pdf::loadView('pdf.cost-assistance', $data)->output();
        $this->appendPdfFromStream($fpdi, $costAssistancePdfBinary);

        // 2. Requerimento de ajuda de custo
        if (isset($attachments[0])) {
            $this->appendAttachment($attachments[0], $fpdi);
        }

        // 3. Declaração médica
        if (count($attachments) === 4 && isset($attachments[1])) {
            $this->appendAttachment($attachments[1], $fpdi);
        }

        // 4. Parecer médico
        $medicalOpinionPdfBinary = Pdf::loadView('pdf.medical-opinion', $data)->output();
        $this->appendPdfFromStream($fpdi, $medicalOpinionPdfBinary);

        // 5. Passagem
        $travelPdfBinary = Pdf::loadView('pdf.travel', $data)->output();
        $this->appendPdfFromStream($fpdi, $travelPdfBinary);

        // 6. Anexos de documentos dos passageiros
        if (!empty($data['travel']['passengers'])) {
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
        }

        // 7. Comprovante bancário
        if (count($attachments) === 4 && isset($attachments[2])) {
            $this->appendAttachment($attachments[2], $fpdi);
        } elseif (isset($attachments[1])) {
            $this->appendAttachment($attachments[1], $fpdi);
        }

        // 8. Situação cadastral
        if (count($attachments) === 4 && isset($attachments[3])) {
            $this->appendAttachment($attachments[3], $fpdi);
        } elseif (isset($attachments[2])) {
            $this->appendAttachment($attachments[2], $fpdi);
        }

        return $fpdi->Output('S');
    }

    private function generateMemoPdf(array $data): string
    {
        $fpdi = new Fpdi();
        $paymentPdfBinary = Pdf::loadView('pdf.payment-memo', $data)->output();
        $this->appendPdfFromStream($fpdi, $paymentPdfBinary);

        return $fpdi->Output('S');
    }

    private function appendAttachment(array $attachment, Fpdi $fpdi): void
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
                $imagePdfBinary = $this->convertImageBase64ToPdf($base64, '15mm');
                $this->appendPdfFromStream($fpdi, $imagePdfBinary);
            }
        } catch (Exception $e) {
            $attachmentId = $attachment['id'] ?? 'desconhecido';
            Log::error("Falha ao mesclar anexo ID {$attachmentId}: " . $e->getMessage());
        }
    }

    private function appendPdfFromStream(Fpdi $fpdi, string $pdfContent): void
    {
        $stream    = StreamReader::createByString($pdfContent);
        $pageCount = $fpdi->setSourceFile($stream);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId  = $fpdi->importPage($pageNo);
            $size        = $fpdi->getTemplateSize($templateId);
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

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                @page { margin: {$margin}; }
                body { margin: 0; padding: 0; background: #ffffff; text-align: center; }
                .wrapper { width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; }
                img { max-width: 100%; max-height: 100%; object-fit: contain; }
            </style>
        </head>
        <body>
            <div class=\"wrapper\">
                <img src=\"{$imageBase64}\" />
            </div>
        </body>
        </html>";

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