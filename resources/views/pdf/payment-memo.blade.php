<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Memorando de Pagamento — TFD/MT</title>
    <style>
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
            font-size: 9.5pt;
            line-height: 1.5;
            padding: 20mm;
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
            font-size: 8.5pt;
            color: #555555;
        }

        /* Título do Documento */
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 12px;
            text-indent: 15px;
        }

        /* Tabela Principal */
        .memo-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 8px 0;
        }

        .memo-table th, 
        .memo-table td {
            border: 1px solid #333333;
            padding: 6px;
            text-align: center;
            font-size: 8.5pt;
        }

        .memo-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
        }

        .memo-table tfoot td {
            background-color: #f8fafc;
            font-weight: bold;
        }

        .legend {
            font-size: 8pt;
            margin-top: 4px;
            color: #444444;
        }

        .obs {
            font-size: 8.5pt;
            color: #c62828;
            margin-bottom: 14px;
        }

        /* Caixas Informativas */
        .budget-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            margin-top: 8px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .budget-box-title {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .budget-box p {
            font-size: 8.5pt;
            margin-bottom: 2px;
        }

        /* Assinaturas */
        .footer-container {
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .date-location {
            text-align: right;
            margin-bottom: 35px;
            font-size: 9pt;
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
            margin-bottom: 4px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 8.5pt;
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
        $cityName = $patient_request['hospital_unity']['city'] ?? 'Cuiabá';
        $dailies = $cost_assistance['cost_assistance_dailies'] ?? [];
        $totalDailiesCount = count($dailies);
    @endphp

    <!-- CABEÇALHO INSTITUCIONAL -->
    <div class="header">
        <h1>ESTADO DE MATO GROSSO</h1>
        <h2>SECRETARIA DE ESTADO DE SAÚDE</h2>
        <p>Sistema de Tratamento Fora do Domicílio — TFD/MT</p>
    </div>

    <!-- TÍTULO -->
    <div class="doc-title">
        MEMORANDO DE PAGAMENTO
    </div>

    <!-- TEXTO INICIAL -->
    <p class="paragraph">
        Prezado(a) Senhor(a):<br />
        Vimos solicitar, por meio deste, pagamento de ajuda de custo para o SIGADOC
        <strong>{{ $sigadoc ?? 'N/A' }}</strong> e código do credor 
        <strong>{{ $creditor ?? 'N/A' }}</strong> referente ao (a) paciente 
        <strong>{{ $patient_request['report']['patient_care']['patient']['name'] ?? 'N/A' }}</strong> 
        com diagnóstico de <strong>{{ $patient_request['report']['cid']['name'] ?? 'N/A' }}</strong> 
        de CID <strong>{{ $patient_request['report']['cid']['code'] ?? 'N/A' }}</strong> e tem como acompanhante 
        <strong>{{ $travel['passenger']['escort']['name'] ?? 'Não informado' }}</strong> 
        e realiza tratamento no <strong>{{ $patient_request['hospital_unity']['name'] ?? 'N/A' }}</strong> 
        na cidade de <strong>{{ $patient_request['hospital_unity']['city'] ?? 'Não informada' }} - {{ $patient_request['hospital_unity']['state'] ?? 'Não informado' }}</strong>. 
        Autorização dada pelo (a) médico (a) regulador (a) Dr. 
        <strong>{{ $patient_request['medical_professional']['name'] ?? 'N/A' }}</strong>, conforme especificação abaixo:
    </p>

    <!-- TABELA DE DIÁRIAS -->
    <table class="memo-table">
        <thead>
            <tr>
                <th>DATA DA CONSULTA</th>
                <th>QUANTIDADE DE DIÁRIAS</th>
                <th>VALOR DE DIÁRIAS</th>
                <th>VALOR TOTAL DE DIÁRIAS</th>
                <th>VALOR TOTAL A PAGAR</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cost_assistance['cost_assistance_dailies'] as $index => $daily)
                @php
                    $amount = $daily['amount'] ?? 0;
                    $unitValue = $daily['daily_cost']['value'] ?? 0;
                    $rowTotal = $amount * $unitValue;
                @endphp
                <tr>
                    @if ($loop->first)
                        <td rowspan="{{ $totalDailiesCount }}">
                            {{ !empty($payment['travel']['departure_date']) ? \Carbon\Carbon::parse($payment['travel']['departure_date'])->format('d/m/Y') : 'N/A' }} 
                            A 
                            {{ !empty($payment['travel']['return_date']) ? \Carbon\Carbon::parse($payment['travel']['return_date'])->format('d/m/Y') : 'N/A' }}
                        </td>
                    @endif
                    <td>{{ $amount }} {{ $daily['daily_cost']['name'] ?? '' }}</td>
                    <td>{{ $amount }} x R$ {{ number_format($unitValue, 2, ',', '.') }}</td>
                    <td>{{ $amount }} x R$ {{ number_format($unitValue, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($rowTotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhuma diária registrada.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total:</strong> {{ $cost_assistance['total_amount'] ?? 0 }}</td>
                <td colspan="2"><strong>Valor Total:</strong> R$ {{ number_format($cost_assistance['total_dailies'] ?? 0, 2, ',', '.') }}</td>
                <td><strong>R$ {{ number_format($cost_assistance['total_dailies'] ?? 0, 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <p class="legend">PCT = Paciente / AC = Acompanhante / DOA = Doador</p>
    <p class="obs"><strong>OBS:</strong> VALOR REQUERIDO CONSIDERANDO PERÍODO DE INTERNAÇÃO DE PACIENTE</p>

    <!-- FUNDAMENTAÇÃO LEGAL -->
    <p class="paragraph">
        Solicitação se embasa na Portaria SAS/MS n° 055 de 24/02/1999, Resolução CIB/MT n° 294 de 06/07/2023 e n° 776, de 02/10/2007; para as viagens realizadas a partir de 10/03/2022, da Portaria 241/2022/GBSES de 11/04/2022 e para as viagens a partir de 14/08/2023 a Portaria 589/2023/GBSES, que dispõe sobre o Financiamento Estadual no valor específico da tabela SIGTAP para o procedimento de ajuda de custo ao paciente em Tratamento Fora de Domicílio do Sistema Único de Saúde - SUS dos pacientes do Estado de MT.
    </p>

    <p class="paragraph">
        A quantidade de diárias solicitadas obedece ao Manual de Normatização do Tratamento Fora de Domicílio/TFD do Estado de Mato Grosso, aprovado pela Resolução CIB/MT n° 776, de 14/12/2023, conforme item 9.0-DA AJUDA DE CUSTO, subitem 9.1 e 9.2.
    </p>

    <p class="paragraph">
        Informamos que a classificação no Plano de Trabalho Anual/PTA {{ date('Y') }} é a seguinte:
    </p>

    <!-- DOTAÇÃO ORÇAMENTÁRIA -->
    <div class="budget-box">
        <div class="budget-box-title">DOTAÇÃO ORÇAMENTÁRIA</div>
        <p><strong>Programa:</strong> 0526e</p>
        <p><strong>Projeto Atividade:</strong> 2545</p>
        <p><strong>Natureza da Despesa:</strong> 3.3.90.00.00</p>
        <p><strong>Fonte:</strong> 2.600.0000</p>
        <p><strong>Valor Total:</strong> R$ {{ number_format($cost_assistance['total_dailies'] ?? 0, 2, ',', '.') }}</p>
    </div>

    <!-- RODAPÉ DE ASSINATURA -->
    <div class="footer-container">
        <div class="date-location">
            {{ $cityName }}-MT, {{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y') }}
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Médico(a) Regulador(a)</div>
                    <div class="signature-role">Dr.(a) {{ $patient_request['medical_professional']['name'] ?? 'Autorizador' }}</div>
                </td>
                <td class="signature-space"></td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-name">Setor de Regulação / TFD</div>
                    <div class="signature-role">GBSES - MT</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>