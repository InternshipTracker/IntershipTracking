<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Approval Letter</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            line-height: 1.6; 
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .logo {
            width: 70px;
            height: 70px;
        }
        .college-name {
            text-align: center;
            flex: 1;
            padding: 0 20px;
        }
        .college-title { 
            font-size: 18px; 
            font-weight: bold; 
            color: #1e40af;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .college-subtitle {
            font-size: 11px;
            color: #475569;
            margin-bottom: 3px;
        }
        .doc-title { 
            text-align: center; 
            font-size: 16px; 
            font-weight: bold; 
            margin: 20px 0;
            color: #1e40af;
            text-decoration: underline;
        }
        .date-section {
            text-align: right;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .content { 
            margin-top: 20px; 
            white-space: pre-line; 
            text-align: justify;
        }
        .footer { 
            margin-top: 50px; 
        }
        .signature-block {
            margin-top: 60px;
            text-align: right;
        }
        strong {
            font-weight: bold;
        }
        p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <!-- Left Logo -->
                <img src="{{ public_path('images/clg logo.jpg') }}" alt="College Logo" style="width: 70px; height: 70px; object-fit: contain;" />
            </div>
            
            <div class="college-name">
                <div class="college-title">{{ $college }}</div>
                <div class="college-subtitle">Affiliated to Savitribai Phule Pune University</div>
                <div class="college-subtitle">NAAC Re-accredited 'A+' Grade</div>
            </div>
            
            <div class="logo">
                <!-- Right Logo -->
                <img src="{{ public_path('images/clg logo.jpg') }}" alt="College Logo" style="width: 70px; height: 70px; object-fit: contain;" />
            </div>
        </div>
    </div>

    <div class="doc-title">INTERNSHIP APPROVAL LETTER</div>

    <div class="date-section">
        <strong>Date:</strong> {{ $date }}
    </div>

    <p><strong>To Whom It May Concern,</strong></p>

    <p>This is to certify that <strong>{{ $studentName }}</strong> is a bonafide student of our institution.</p>

    <div class="content">{{ $content }}</div>

    <p>We wish the student all the best for their internship.</p>

    <div class="signature-block">
        <p><strong>{{ $teacherName }}</strong></p>
        <p>Internship Coordinator</p>
        <p>{{ $college }}</p>
    </div>
</body>
</html>
