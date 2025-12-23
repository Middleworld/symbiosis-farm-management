<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CIC34 Report - Middle World Farms CIC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .report-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .period { font-size: 14px; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .subsection-title { font-size: 14px; font-weight: bold; margin-bottom: 8px; margin-top: 15px; }
        .question { margin-bottom: 15px; }
        .answer { margin-left: 20px; margin-bottom: 10px; }
        .signature { margin-top: 50px; }
        .signature-line { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin-left: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
        <p>Company Number: 13617115</p>
        <p>CIC34 Community Interest Company Report</p>
    </div>

    <div class="report-title">COMMUNITY INTEREST COMPANY REPORT</div>
    <div class="period">
        For the period ended {{ date('d F Y', strtotime($periodEnd)) }}
    </div>

    <div class="section">
        <div class="section-title">1. Description of activities</div>
        <p>Middle World Farms C.I.C. operates as a community interest company focused on sustainable agriculture and community food production. Our activities include:</p>
        <ul>
            <li>Growing and supplying organic vegetables to the local community</li>
            <li>Educational programs about sustainable farming practices</li>
            <li>Community supported agriculture initiatives</li>
            <li>Environmental conservation through regenerative farming methods</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">2. Community benefit</div>
        <p>Our activities are carried out for the benefit of the community in the following ways:</p>
        <ul>
            <li>Providing access to fresh, locally grown organic produce</li>
            <li>Supporting local food security and reducing food miles</li>
            <li>Educating the community about sustainable agriculture</li>
            <li>Creating employment opportunities in the local area</li>
            <li>Contributing to environmental sustainability through regenerative practices</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">3. Consultations</div>
        <p>During the reporting period, we consulted with:</p>
        <ul>
            <li>Local residents and community members</li>
            <li>Other local food producers and farmers</li>
            <li>Environmental groups and sustainability organizations</li>
            <li>Local authority representatives</li>
        </ul>
        <p>Consultation methods included community meetings, surveys, and direct engagement through our community supported agriculture program.</p>
    </div>

    <div class="section">
        <div class="section-title">4. Directors' remuneration</div>
        <div class="question">
            <strong>Did any director receive remuneration for their services to the company?</strong>
        </div>
        <div class="answer">No</div>

        <div class="question">
            <strong>If yes, please provide details:</strong>
        </div>
        <div class="answer">Not applicable</div>

        <div class="subsection-title">Remuneration received by directors</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <th style="border: 1px solid #000; padding: 8px; text-align: left;">Director</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: left;">Remuneration (£)</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: left;">Nature of service</th>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">Martin Taylor</td>
                <td style="border: 1px solid #000; padding: 8px;">0</td>
                <td style="border: 1px solid #000; padding: 8px;">Director</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. Asset locks</div>
        <div class="question">
            <strong>Are any of the company's assets subject to an asset lock?</strong>
        </div>
        <div class="answer">No</div>

        <div class="question">
            <strong>If yes, please provide details:</strong>
        </div>
        <div class="answer">Not applicable</div>
    </div>

    <div class="section">
        <div class="section-title">6. Transfers of assets</div>
        <div class="question">
            <strong>Have there been any transfers of assets to or from the company?</strong>
        </div>
        <div class="answer">No</div>

        <div class="question">
            <strong>If yes, please provide details:</strong>
        </div>
        <div class="answer">Not applicable</div>
    </div>

    <div class="section">
        <div class="section-title">7. Additional information</div>
        <p>Middle World Farms C.I.C. continues to operate in accordance with its community interest company objectives, focusing on sustainable agriculture and community benefit. We maintain our commitment to environmental sustainability and community engagement throughout all our activities.</p>
    </div>

    <div class="signature">
        <p>Director's approval</p>
        <p>This report was approved by the director on {{ date('d F Y') }} and was signed on its behalf by:</p>
        <br><br>
        <span class="signature-line"></span>
        <p>Martin Taylor<br>Director</p>
    </div>
</body>
</html>