<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Capa do Processo</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 30px;
            color: #2d3748;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #1a365d;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #718096;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f7fafc;
        }
        .field {
            margin-bottom: 12px;
            font-size: 14px;
        }
        .field .label {
            font-weight: bold;
            color: #4a5568;
            width: 150px;
            display: inline-block;
        }
        .field .value {
            color: #1a202c;
        }
        .footer {
            margin-top: 50px;
            font-size: 11px;
            text-align: center;
            color: #a0aec0;
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Sistema TFD - Capa do Processo</h1>
        <p>Documentação Unificada de Pagamento nº {{ $request_id }}</p>
    </div>

    <div class="card">
        <div class="field">
            <span class="label">Nº da Solicitação:</span>
            <span class="value">{{ $request_id }}</span>
        </div>
        <div class="field">
            <span class="label">Paciente:</span>
            <span class="value">{{ $patient_name }}</span>
        </div>
        <div class="field">
            <span class="label">Cartão CNS:</span>
            <span class="value">{{ $cns }}</span>
        </div>
        <div class="field">
            <span class="label">Data de Emissão:</span>
            <span class="value">{{ $issued_at }}</span>
        </div>
    </div>

    <div class="footer">
        Documento gerado automaticamente pelo sistema de Tratamento Fora do Domicílio (TFD).
    </div>

</body>
</html>