<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ordem de Passagem - TFD</title>
    <style>
        /* Configuração A4 e área útil via Body Padding */
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

        body {
            background-color: #ffffff;
            color: #000000;
            font-size: 10pt;
            line-height: 1.4;
            padding: 25mm 20mm 20mm 20mm;
        }

        /* Cabeçalho Institucional */
        .header {
            text-align: center;
            margin-bottom: 20px;
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

        /* Título do Documento */
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            text-decoration: underline;
        }

        /* Seções */
        .section-header {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9.5pt;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        /* Tabelas de Dados */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-table td.label {
            font-weight: bold;
            width: 30%;
            color: #334155;
            font-size: 9pt;
            text-transform: uppercase;
        }

        .info-table td.value {
            width: 70%;
            font-size: 9.5pt;
        }

        /* Tabela de Passageiros */
        .passengers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        .passengers-table th {
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
            padding: 5px;
            font-size: 8.5pt;
            text-transform: uppercase;
            text-align: left;
        }

        .passengers-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
            font-size: 9pt;
        }

        /* Destaque do Bilhete/Localizador */
        .ticket-highlight {
            background-color: #f8fafc;
            border: 1px dashed #64748b;
            padding: 8px 12px;
            margin: 12px 0;
            border-radius: 4px;
        }

        /* Rodapé de Assinatura */
        .footer-container {
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .date-location {
            text-align: right;
            margin-bottom: 40px;
            font-size: 9.5pt;
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
            font-size: 9pt;
            text-transform: uppercase;
        }

        .signature-role {
            font-size: 8pt;
            color: #444444;
        }
    </style>
</head>
<body>

    @php
        // Tratamento de Cidade
        $cityData = $travel['origin_city'] ?? $patient_request['report']['patient_care']['patient']['city'] ?? 'Cuiabá';
        $cityName = is_array($cityData) ? ($cityData['name'] ?? 'Cuiabá') : $cityData;
    @endphp

    <!-- CABEÇALHO OFICIAL -->
    <div class="header">
        <h1>ESTADO DE MATO GROSSO</h1>
        <h2>SECRETARIA MUNICIPAL DE SAÚDE</h2>
        <p>Sistema de Tratamento Fora do Domicílio — TFD/MT</p>
        <p>Solicitante: {{ $patient_request['travel_professional']['name'] ?? 'N/A' }}</p>
    </div>

    <!-- TÍTULO -->
    <div class="doc-title">
        ORDEM DE EMISSÃO DE PASSAGEM / VIAGEM
    </div>

    <!-- DADOS DA VIAGEM E TRANSPORTE -->
    <div class="section-header">Detalhes da requisição</div>
    
    <table class="info-table">
        <tr>
            <td class="label">Hospital:</td>
            <td class="value">{{ $patient_request['hospital_unity']['name'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Data da consulta:</td>
            <td class="value">{{ $patient_request['consultation_date'] ? \Carbon\Carbon::parse($patient_request['consultation_date'])->format('d/m/Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Especialidade:</td>
            <td class="value">{{ $patient_request['report']['specialty'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Médico autorizador:</td>
            <td class="value">{{ $patient_request['medical_professional']['name'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Observação:</td>
            <td class="value">
                <strong>{{ $travel['description'] ?? 'N/A' }}</strong>
            </td>
        </tr>
    </table>

    <!-- BLOCO DE CODIGO/LOCALIZADOR -->
    <!-- @if(!empty($travel['locator']) || !empty($travel['ticket_number']))
    <div class="ticket-highlight">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;"><strong>Localizador / Reserva:</strong> {{ $travel['locator'] ?? 'N/A' }}</td>
                <td style="width: 50%;"><strong>Nº do Bilhete:</strong> {{ $travel['ticket_number'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    @endif -->

    <!-- LISTA DE PASSAGEIROS -->
    <div class="section-header">Passageiros</div>
    <table class="passengers-table">
        <thead>
            <tr>
                <th style="width: 45%;">Nome</th>
                <th style="width: 25%;">Tipo</th>
                <th style="width: 20%;">Sexo</th>
                <th style="width: 10%;">Assento</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($travel['passengers']) && is_array($travel['passengers']))
                @foreach($travel['passengers'] as $passenger)
                    @php
                        $isPatient = $passenger['is_patient'] ?? false;
                        $person = $isPatient ? ($passenger['patient'] ?? []) : ($passenger['escort'] ?? []);
                        $documentData = $person['document'] ?? 'N/A';
                        $doc = is_array($documentData) ? ($documentData['number'] ?? 'N/A') : $documentData;
                    @endphp
                    <tr>
                        <td><strong>{{ $person['name'] ?? 'N/A' }}</strong></td>
                        <td>{{ $passenger['type'] ?? 'N/A' }}</td>
                        <td>{{ $passenger['gender'] ?? 'N/A' }}</td>
                        <td>{{ $passenger['seat'] ?? '-' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum passageiro vinculado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- MOTIVO DA VIAGEM -->
    <div class="section-header">Reservas da viagem</div>
    <table class="info-table">
        <tr>
            <td class="label">Localizador:</td>
            <td class="value">{{ $travel['locator'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Trecho:</td>
            <td class="value">{{ $travel['origin'] ?? 'N/A' }} - {{ $travel['destination'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Companhia Aérea:</td>
            <td class="value">{{ $travel['company'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Data da reserva:</td>
            <td class="value">
                @if(!empty($travel['created_at']))
                    {{ \Carbon\Carbon::parse($travel['created_at'])->format('d/m/Y H:i') }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Prazo:</td>
            <td class="value">{{ \Carbon\Carbon::parse($travel['created_at'])->format('d/m/Y') }}</td>
        </tr>      
    </table>

    <table class="passengers-table">
        <thead>
            <tr>
                <th style="width: 45%;">Tipo</th>
                <th style="width: 25%;">Tarifa</th>
                <th style="width: 20%;">Taxa</th>
                <th style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($travel['passengers']) && is_array($travel['passengers']))
                @foreach($travel['passengers'] as $passenger)
                    <tr>
                        <td>{{ $passenger['type'] ?? 'N/A' }}</td>
                        <td>{{ $passenger['tariff'] ?? 'N/A' }}</td>
                        <td>{{ $passenger['tax'] ?? 'N/A' }}</td>
                        <td>{{ number_format($passenger['tariff'] + $passenger['tax'], 2, ',', '.') ?? 'N/A' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td></td>
                    <td>{{ $travel['total_tariffs'] ?? 'N/A' }}</td>
                    <td>{{ $travel['total_taxes'] ?? 'N/A' }}</td>
                    <td>{{ number_format($travel['total'], 2, ',', '.') ?? 'N/A' }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum passageiro vinculado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-header">Discontos aplicados</div>
    <table class="passengers-table">
        <thead>
            <tr>
                <th style="width: 100%;">Desconto Tarifa %</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($travel['passengers']) && is_array($travel['passengers']))
                @foreach($travel['passengers'] as $passenger)
                    @if(!empty($passenger['discount']) && $passenger['discount'] > 0)
                        <tr>
                            <td>{{ $passenger['discount'] ?? 'N/A' }} %</td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td style="text-align: center;">Nenhum passageiro vinculado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- LISTA DE ROTAS -->
    <div class="section-header">Trechos</div>
    <table class="passengers-table">
        <thead>
            <tr>
                <th>Companhia</th>
                <th>Voo</th>
                <th>Avião</th>
                <th>Saída</th>
                <th>Chegada</th>
                <th>Origem</th>
                <th>Destino</th>
                <th>Classe</th>
                <th>Escalas</th>
                <th>Familia</th>
                <th>Localizador</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($travel['travel_routes']) && is_array($travel['travel_routes']))
                @foreach($travel['travel_routes'] as $route)
                    <tr>
                        <td>{{ $travel['company'] ?? 'N/A' }}</td>
                        <td>{{ $route['flight'] ?? 'N/A' }}</td>
                        <td>{{ $route['airplane'] ?? 'N/A' }}</td>
                        <td>{{ !empty($route['departure']) ? \Carbon\Carbon::parse($route['departure'])->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ !empty($route['arrival']) ? \Carbon\Carbon::parse($route['arrival'])->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $route['origin'] ?? 'N/A' }}</td>
                        <td>{{ $route['destination'] ?? 'N/A' }}</td>
                        <td>{{ $route['class'] ?? 'N/A' }}</td>
                        <td>{{ $route['scales'] ?? 'N/A' }}</td>
                        <td>{{ $route['family'] ?? 'N/A' }}</td>
                        <td>{{ $travel['locator'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="11" style="text-align: center;">Nenhuma rota vinculada.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- ASSINATURAS -->
    <div class="footer-container">
        <div class="date-location">
            {{ $cityName }}-MT, {{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y') }}
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Assinatura do Passageiro</div>
                    <div class="signature-role">Ciente da Emissão</div>
                </td>
                <td class="signature-space"></td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Emissor / Setor de Transporte</div>
                    <div class="signature-role">Coordenação TFD/MT</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>