<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Micro-entity Accounts - Middle World Farms CIC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .accounts-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .period { font-size: 14px; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .total { font-weight: bold; }
        .signature { margin-top: 50px; }
        .signature-line { border-bottom: 1px solid #000; width: 200px; display: inline-block; margin-left: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
        <p>Company Number: 13617115</p>
    </div>

    <div class="accounts-title">MICRO-ENTITY ACCOUNTS</div>
    <div class="period">
        For the period ended {{ date('d F Y', strtotime($periodEnd)) }}
    </div>

    <div class="section">
        <div class="section-title">BALANCE SHEET</div>
        <div class="section-title">As at {{ date('d F Y', strtotime($periodEnd)) }}</div>

        <table>
            <tr>
                <th width="60%">FIXED ASSETS</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Tangible assets</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
            <tr class="total">
                <td><strong>Total fixed assets</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">CURRENT ASSETS</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Cash at bank and in hand</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
            <tr class="total">
                <td><strong>Total current assets</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">CURRENT LIABILITIES</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Trade creditors</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
            <tr class="total">
                <td><strong>Total current liabilities</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">NET CURRENT ASSETS</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr class="total">
                <td><strong>Total assets less current liabilities</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">CAPITAL AND RESERVES</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Profit and loss account</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
            <tr class="total">
                <td><strong>Total equity</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">PROFIT AND LOSS ACCOUNT</div>
        <div class="section-title">For the period ended {{ date('d F Y', strtotime($periodEnd)) }}</div>

        <table>
            <tr>
                <th width="60%">TURNOVER</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Turnover</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">OPERATING PROFIT/(LOSS)</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr>
                <td>Operating profit/(loss)</td>
                <td>£0</td>
                <td>£0</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="60%">PROFIT/(LOSS) FOR THE PERIOD</th>
                <th width="20%">2024</th>
                <th width="20%">2023</th>
            </tr>
            <tr class="total">
                <td><strong>Profit/(loss) for the period</strong></td>
                <td><strong>£0</strong></td>
                <td><strong>£0</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">NOTES TO THE ACCOUNTS</div>

        <p><strong>1. Accounting policies</strong></p>
        <p>The financial statements have been prepared under the historical cost convention and in accordance with FRS 102, the Financial Reporting Standard applicable in the UK and Republic of Ireland.</p>

        <p><strong>2. Company information</strong></p>
        <p>Middle World Farms C.I.C. is a private company limited by guarantee incorporated in England and Wales. The registered office is Middle World Farms Bardney Rd, Branston Booths, Washingborough, Lincolnshire, LN4 1AQ.</p>

        <p><strong>3. Directors' benefits</strong></p>
        <p>The directors received no remuneration during the period (2023: £0).</p>
    </div>

    <div class="signature">
        <p>Director's approval</p>
        <p>The financial statements were approved by the director on {{ date('d F Y') }} and were signed on its behalf by:</p>
        <br><br>
        <span class="signature-line"></span>
        <p>Martin Taylor<br>Director</p>
    </div>
</body>
</html>