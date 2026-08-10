<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Despacho - Solicitação de Ajuda de Custo TFD</title>
    <style>
        /* Configuração de Folha Sem Margens Físicas no DomPDF */
        @page {
            size: A4 portrait;
            margin: 0mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }

        /* Área útil de impressão definida no Body com Padding */
        body {
            background-color: #ffffff;
            color: #000000;
            font-size: 10.5pt;
            line-height: 1.5;
            padding: 25mm 20mm 20mm 20mm; /* Margens: Superior, Direita, Inferior, Esquerda */
        }

        /* Cabeçalho Institucional */
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #000000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 11pt;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header h2 {
            font-size: 10pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #333333;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 9pt;
            color: #555555;
        }

        /* Identificador do Despacho */
        .dispatch-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        /* Conteúdo do Despacho */
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10pt;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .paragraph {
            text-align: justify;
            text-indent: 1.25cm;
            margin-bottom: 15px;
        }

        /* Bloco com o parecer em innerHTML */
        .opinion {
            text-align: justify;
            margin-top: 15px;
            margin-bottom: 20px;
            padding: 12px 15px;
            background-color: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            word-wrap: break-word;
        }

        /* Formatação dos elementos inseridos dinamicamente */
        .opinion p {
            margin-bottom: 8px;
            text-align: justify;
        }

        .opinion ul, .opinion ol {
            margin-left: 20px;
            margin-bottom: 8px;
        }

        /* Rodapé / Assinatura */
        .footer-container {
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .date-location {
            text-align: right;
            margin-bottom: 40px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-cell {
            width: 45%;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            width: 10%;
        }

        .signature-line {
            border-top: 1px solid #000000;
            margin-bottom: 5px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 9.5pt;
            text-transform: uppercase;
        }

        .signature-role {
            font-size: 8.5pt;
            color: #444444;
        }
    </style>
</head>
<body>

    <!-- CABEÇALHO OFICIAL -->
    <div class="header">
        <h1>ESTADO DE MATO GROSSO</h1>
        <h2>SECRETARIA MUNICIPAL DE SAÚDE</h2>
        <p>Sistema de Tratamento Fora do Domicílio — TFD/MT</p>
    </div>

    <!-- TÍTULO DO DESPACHO -->
    <div class="dispatch-title">
        DESPACHO DE SOLICITAÇÃO DE AJUDA DE CUSTO
    </div>

    <!-- CORPO DO DESPACHO -->
    <div class="section-title">Assunto: Parecer Médico</div>
    
    @php
        // Tratamento seguro para CID-10
        $cidData = $patient_request['report']['cid'] ?? null;
        $cidCode = 'N/A';
        
        if (is_array($cidData)) {
            if (isset($cidData['code'])) {
                $cidCode = $cidData['code'];
            } else {
                $cidCode = implode(', ', array_filter(array_column($cidData, 'code')));
            }
        } elseif (is_string($cidData)) {
            $cidCode = $cidData;
        }

        // Tratamento seguro para Cidade
        $cityData = $patient_request['report']['patient_care']['patient']['city'] ?? 'Cuiabá';
        $cityName = is_array($cityData) ? ($cityData['name'] ?? 'Cuiabá') : $cityData;
    @endphp

    <p class="paragraph">
        Paciente <strong>{{ $patient_request['report']['patient_care']['patient']['name'] ?? 'N/A' }}</strong>, 
        (data da consulta: {{ !empty($patient_request['consultation_date']) ? \Carbon\Carbon::parse($patient_request['consultation_date'])->format('d/m/Y') : 'N/A' }}), 
        (CID-10: <strong>{{ $cidCode }}</strong>), em acompanhamento na 
        <strong>{{ $patient_request['hospital_unity']['name'] ?? 'N/A' }}</strong>.
    </p>

    <!-- PARECER MÉDICO (INNER HTML TRATADO) -->
    <div class="opinion">
        {!! $patient_request['medical_approved_opinion']['content'] ?? '<p>Nenhum parecer informado.</p>' !!}
    </div>

    <!-- DATA E ASSINATURA -->
    <div class="footer-container">
        <div class="date-location">
            {{ $cityName }}-MT, {{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y') }}
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Médico Regulador / Perito</div>
                    <div class="signature-role">Assinatura / Carimbo</div>
                </td>
                <td class="signature-space"></td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Coordenação do TFD</div>
                    <div class="signature-role">Assinatura / Carimbo</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>