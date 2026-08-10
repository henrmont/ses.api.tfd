<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de Ajuda de Custo - TFD</title>
    <style>
        /* Configuração de Página A4 sem margens externas do domPDF */
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif; /* Fonte padrão nativa do domPDF com suporte a acentuação */
        }

        body {
            background-color: #ffffff;
            color: #111111;
            font-size: 9pt;
            line-height: 1.2;
            padding: 15mm 15mm 15mm 15mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Cabeçalho */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #2b3e50;
            padding-bottom: 6px;
        }

        .header-title {
            text-align: center;
        }

        .header-title h1 {
            font-size: 14pt;
            text-transform: uppercase;
            color: #1a252f;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header-title p {
            font-size: 8.5pt;
            color: #4a5568;
        }

        /* Estrutura das Seções */
        .section-box {
            border: 1px solid #cbd5e0;
            border-radius: 3px;
            margin-bottom: 10px;
            background-color: #ffffff;
        }

        .section-header {
            background-color: #edf2f7;
            padding: 4px 8px;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #2d3748;
            border-bottom: 1px solid #cbd5e0;
        }

        .section-body {
            padding: 6px 8px;
        }

        /* Tabelas Internas de Campos */
        .field-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 6px;
            table-layout: fixed;
        }

        .field-cell {
            vertical-align: top;
            width: 100%;
        }

        .field-label {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #4a5568;
            display: block;
            margin-bottom: 2px;
        }

        .field-value-line {
            border-bottom: 1px solid #718096;
            height: auto;
            font-size: 8.5pt;
            color: #1a202c;
            padding-left: 2px;
            padding-bottom: 3px;
            /* Alterações para permitir a quebra de texto em parágrafos longos: */
            white-space: normal;       /* Permite quebra de linha normal */
            word-wrap: break-word;     /* Força a quebra de palavras muito longas */
            overflow: visible;         /* Exibe todo o conteúdo sem cortar */
        }

        .field-value-box {
            border: 1px solid #a0aec0;
            border-radius: 2px;
            min-height: 45px;
            padding: 4px;
            font-size: 8.5pt;
            color: #1a202c;
            background-color: #fafafa;
        }

        /* Rodapé de Assinaturas */
        .footer-container {
            margin-top: 20px;
        }

        .date-row {
            text-align: right;
            font-size: 8.5pt;
            color: #2d3748;
            margin-bottom: 35px;
            font-weight: bold;
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
            border-top: 1px solid #2d3748;
            margin-bottom: 4px;
        }

        .signature-label {
            font-size: 7.5pt;
            color: #4a5568;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- CABEÇALHO -->
    <table class="header-table">
        <tr>
            <td class="header-title">
                <h1>Solicitação de Ajuda de Custo</h1>
                <p>Tratamento Fora do Domicílio — TFD</p>
            </td>
        </tr>
    </table>

    <!-- CATEGORIA 1: SOLICITAÇÃO DE AJUDA DE CUSTO -->
    <div class="section-box">
        <div class="section-header">SOLICITAÇÃO DE AJUDA DE CUSTO</div>
        <div class="section-body">
            <table class="field-table">
                <tr>
                    <td class="field-cell">
                        <span class="field-label">Paciente</span>
                        <div class="field-value-line">{{ $patient_request['report']['patient_care']['patient']['name'] ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="field-cell">
                        <div class="field-value-line">
                            <p>Eu, <strong>{{ $patient_request['report']['patient_care']['patient']['name'] ?? '' }}</strong></p>
                            <p>Portador do CPF n° <strong>{{ $patient_request['report']['patient_care']['patient']['document'] ?? '' }}</strong></p>
                            <p>Residente e domiciliado na 
                                <strong>{{ $patient_request['report']['patient_care']['patient']['address'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['number'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['complement'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['neighborhood'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['city'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['state'] ?? '' }}</strong>
                                <strong>{{ $patient_request['report']['patient_care']['patient']['cep'] ?? '' }}</strong>
                            </p>
                            <p>Telefone: <strong>{{ $patient_request['report']['patient_care']['patient']['phone'] ? 'Fixo '.$patient_request['report']['patient_care']['patient']['phone'] : 'Celular '.$patient_request['report']['patient_care']['patient']['cell_phone'] }}</strong></p>
                        </div> 
                    </td>
                </tr>
                <tr>
                    <td class="field-cell">
                        <span class="field-label">Acompanhantes</span>
                        <div class="field-value-line">
                            @foreach($patient_request['travels'] as $travel)
                                @foreach($travel['passengers'] as $passenger)
                                <div>
                                    @if(!$passenger['is_patient'])
                                        {{ $passenger['escort']['name'] ?? '' }}
                                    @endif
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="field-cell">
                        <div class="field-value-line">
                            <p>Usuário do SUS/TFD/MT, especialidade <strong>{{ $patient_request['report']['specialty'] ?? '' }}</strong></p>
                            <p>Diagnóstico: <strong>{{ $patient_request['report']['cid']['code'].' - '.$patient_request['report']['cid']['name'] ?? '' }}</strong></p>
                        </div> 
                    </td>
                </tr>
                <tr>
                    <td class="field-cell">
                        <div class="field-value-line">
                            <p>Consulta agendada no: <strong>{{ $patient_request['hospital_unity']['name'] ?? '' }}</strong></p>
                            <p>(Desde) Na data de: <strong>{{ $patient_request['consultation_date'] ?? '' }}</strong></p>
                        </div> 
                    </td>
                </tr>
                <tr>
                    <td class="field-cell">
                        <div class="field-value-line">
                            <p>Conforme o item 9.1 e 9.2 do manual do TFD aprovado pela RESOLUÇÃO CIB/MT N° 776 DE 14 DE DEZEMBRO DE 2023, baseado na portaria SAS n° 055/99, o paciente Pré e Pós-transplante e o acompanhante tem direito a 15(quinze) diárias iniciais, e paciente de ouytas patologias tem direito a 5(cinco) diárias iniciais, e a partir da segunda consulta, tem direito a 15(quinze) diárias. Caso o paciente e acompanhante permanecerem no seu destino por período inferior, as diárias não utilizadas serão abatidas no próximo agendamento do paciente.</p>
                            <p>Dr(a): <strong>{{ $patient_request['medical_professional']['name'] ?? '' }}</strong></p>
                        </div> 
                    </td>
                </tr>
                <!-- <tr>
                    <td class="field-cell" colspan="2">
                        <span class="field-label">5. Acompanhante</span>
                        <div class="field-value-line">{{ $solicitacao->acompanhante_nome ?? '' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="field-cell" style="width: 40%;">
                        <span class="field-label">6. Especialidade</span>
                        <div class="field-value-line">{{ $solicitacao->especialidade ?? '' }}</div>
                    </td>
                    <td class="field-cell" style="width: 30%;">
                        <span class="field-label">7. CID</span>
                        <div class="field-value-line">{{ $solicitacao->cid ?? '' }}</div>
                    </td>
                    <td class="field-cell" style="width: 30%;">
                        <span class="field-label">9. Data da Consulta</span>
                        <div class="field-value-line">
                            {{ isset($solicitacao->data_consulta) ? \Carbon\Carbon::parse($solicitacao->data_consulta)->format('d/m/Y') : '' }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="field-cell" style="width: 50%;">
                        <span class="field-label">8. Hospital</span>
                        <div class="field-value-line">{{ $solicitacao->hospital ?? '' }}</div>
                    </td>
                    <td class="field-cell" style="width: 50%;">
                        <span class="field-label">10. Médico Responsável</span>
                        <div class="field-value-line">{{ $solicitacao->medico_responsavel ?? '' }}</div>
                    </td>
                </tr> -->
            </table>
        </div>
    </div>

    <!-- CATEGORIA 2: DADOS PARA EFETUAÇÃO DO PAGAMENTO -->
    <div class="section-box">
        <div class="section-header">Dados para Efetuação do Pagamento</div>
        <div class="section-body">
            <table class="field-table">
                <tr>
                    <td class="field-cell" style="width: 100%;">
                        <p>Em nome de <strong>{{ $cost_assistance['passenger']['patient']['name'] ?? $cost_assistance['passenger']['escort']['name'] }}</strong></p>
                        <p>Portador do CPF n°  <strong>{{ $cost_assistance['passenger']['patient']['document'] ?? $cost_assistance['passenger']['escort']['document'] }}</strong></p>
                        <p>Banco:  <strong>{{ $cost_assistance['bank'] ?? '' }}</strong>, Agência: <strong>{{ $cost_assistance['agency'] ?? '' }}</strong>, Conta: <strong>{{ $cost_assistance['account'] ?? '' }}</strong></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- CATEGORIA 3: PARA USO EXCLUSIVO DO TFD -->
    <div class="section-box">
        <div class="section-header">Para Uso Exclusivo do TFD</div>
        <div class="section-body">
            <table class="field-table">
                <tr>
                    <td class="field-cell" style="width: 100%;">
                        <p>Valor:  <strong>{{ $cost_assistance['total_dailies'] ?? '' }}</strong></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- RODAPÉ DE DATA E ASSINATURAS -->
    <div class="footer-container">
        <!-- <div class="date-row">
            Data: {{ isset($data_emissao) ? \Carbon\Carbon::parse($data_emissao)->format('d/m/Y') : '____ / ____ / ________' }}
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-label">Assinatura do Paciente ou Responsável</div>
                </td>
                <td class="signature-space"></td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                    <div class="signature-label">Assinatura / Carimbo TFD</div>
                </td>
            </tr>
        </table> -->
    </div>

</body>
</html>