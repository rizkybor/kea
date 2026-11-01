<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Sertifikat {{ $certificate->code }}</title>
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #14110F; }
    .frame { position: absolute; top: 28px; left: 28px; right: 28px; bottom: 28px; border: 3px solid #0E3B34; padding: 48px 56px; }
    .brand { font-size: 22px; font-weight: bold; color: #0E3B34; }
    .brand img { width: 44px; height: 44px; vertical-align: middle; margin-right: 10px; }
    .by { float: right; font-size: 12px; color: #4F5550; margin-top: 14px; }
    .label { margin-top: 70px; font-size: 13px; letter-spacing: 4px; color: #5E6B26; font-weight: bold; }
    .name { margin-top: 18px; font-size: 40px; font-weight: bold; }
    .done { margin-top: 18px; font-size: 15px; color: #4F5550; }
    .program { margin-top: 8px; font-size: 24px; font-weight: bold; color: #0E3B34; }
    .footer { position: absolute; left: 90px; right: 240px; bottom: 84px; }
    .meta { font-size: 12px; color: #4F5550; line-height: 1.7; }
    .qr { position: absolute; right: 84px; bottom: 76px; width: 120px; height: 120px; }
    .accent { position: absolute; left: 0; right: 0; top: 0; height: 8px; background: #F26B2A; }
</style>
</head>
<body>
<div class="frame">
    <div class="accent"></div>
    <span class="by">by Jendela Cakra Digital</span>
    <div class="brand"><img src="{{ $logo }}" alt="">KEA</div>

    <div class="label">SERTIFIKAT KELULUSAN</div>
    <div class="name">{{ $certificate->recipient_name }}</div>
    <div class="done">telah menyelesaikan</div>
    <div class="program">{{ $certificate->program_title }}</div>
    @if ($certificate->cohort_name)
        <div class="done">{{ $certificate->cohort_name }}</div>
    @endif

    <div class="footer">
        <div class="meta">
            Diterbitkan {{ $certificate->issued_at->locale('id')->translatedFormat('d F Y') }}<br>
            Kode {{ $certificate->code }}@if ($certificate->final_score !== null) · Nilai akhir {{ $certificate->final_score }}@endif<br>
            Verifikasi: {{ $verifyUrl }}
        </div>
    </div>
    <img class="qr" src="{{ $qr }}" alt="QR verifikasi">
</div>
</body>
</html>
