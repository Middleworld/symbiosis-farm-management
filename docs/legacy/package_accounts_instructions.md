# Companies House Package Accounts (CIC) — Technical Requirements for Laravel Export

This document explains **exactly** how to generate the required ZIP file for submitting **package accounts** to Companies House for a Community Interest Company (CIC). This is the format your Laravel admin system will need to produce.

---

## 1. Overview
Companies House requires CIC accounts to be filed as a **ZIP package** containing:

```
accounts.zip
   ├── accounts.pdf (or accounts.xml if using iXBRL)
   ├── cicreport.pdf
   └── manifest.xml
```

Your Laravel system must generate **all three** files and bundle them correctly.

---

## 2. Required Files

### 2.1 accounts.pdf
A PDF of your micro-entity accounts, including:
- Balance sheet
- Optional: Profit & loss
- Notes
- Director approval statement
- Signature name & date

### 2.2 cicreport.pdf
The CIC34 report for the same accounting period.
Content includes:
- Activities & community benefit delivered
- Consultations
- Directors’ remuneration
- Asset locks
- Any transfers of assets

### 2.3 manifest.xml
A small XML file that describes the files inside the ZIP. This follows Companies House taxonomy rules.

#### **Template manifest.xml**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Package xmlns="http://www.companieshouse.gov.uk/ef/ixbrl/package/0.1/">
    <Contents>
        <Document>
            <File>accounts.pdf</File>
            <Type>application/pdf</Type>
        </Document>
        <Document>
            <File>cicreport.pdf</File>
            <Type>application/pdf</Type>
        </Document>
    </Contents>
</Package>
```

Save this file as **manifest.xml** inside the ZIP.

---

## 3. ZIP Folder Structure
The ZIP must have **no root folder**. Its top-level contents must be:

```
/accounts.pdf
/cicreport.pdf
/manifest.xml
```

**NOT**:
```
/accounts/accounts.pdf
/myfolder/cicreport.pdf
```

---

## 4. Laravel Implementation Outline

### 4.1 Generate the PDFs
Use your existing PDF generator (DOMPDF, Snappy, Browsershot, TCPDF, etc.)

Example:
```php
$pdfAccounts = PDF::loadView('accounts.micro', $data);
$pdfAccounts->save(storage_path('app/tmp/accounts.pdf'));

$pdfCIC = PDF::loadView('accounts.cic34', $data);
pdfCIC->save(storage_path('app/tmp/cicreport.pdf'));
```

### 4.2 Create manifest.xml
```php
$manifest = view('accounts.manifest')->render();
file_put_contents(storage_path('app/tmp/manifest.xml'), $manifest);
```

### 4.3 Create the ZIP
```php
$zipPath = storage_path('app/accounts_package.zip');
$zip = new ZipArchive;

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    $zip->addFile(storage_path('app/tmp/accounts.pdf'), 'accounts.pdf');
    $zip->addFile(storage_path('app/tmp/cicreport.pdf'), 'cicreport.pdf');
    $zip->addFile(storage_path('app/tmp/manifest.xml'), 'manifest.xml');
    $zip->close();
}
```

### 4.4 Output ZIP for download
```php
return response()->download($zipPath, 'accounts.zip', [
    'Content-Type' => 'application/zip',
]);
```

---

## 5. Validation (Optional but recommended)
Companies House provides an **iXBRL accounts test service**. Even though you're using PDFs, you can still test that your ZIP structure is valid.

---

## 6. After Generating the ZIP
You upload this ZIP directly into the Companies House “File package accounts” screen.

---

## 7. Next Steps
Once this ZIP export works, the Laravel admin will be able to export Companies House‑ready CIC accounts automatically.

If you need help implementing these files or want me to write the Blade templates, controllers, or manifest generator, ask and I will continue.

