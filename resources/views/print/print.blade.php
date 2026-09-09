@php
    $documents = collect($documents)->values();
    $firstDocument = $documents->first();
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $documents->count() > 1 ? 'In '.$documents->count().' đơn hàng' : ($firstDocument['browser_title'] ?? 'Chứng từ') }} - Malibu Print</title>

    <!-- Tailwind CSS 4 -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Baloo 2 hỗ trợ tiếng Việt và đầy đủ weight dùng trong thiết kế trang in. -->
    <link
        href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style type="text/tailwindcss">
        @theme {
            --font-sans: "Baloo 2", Arial, sans-serif;

            --color-primary: #0879d1;
            --color-primary-dark: #0564b4;
            --color-orange: #f45116;
            --color-yellow: #ffd600;
            --color-magenta: #ec008c;
            --color-cyan: #00a8e8;
        }
    </style>

    <style>
        /* =========================================
           PRINT SETUP
        ========================================= */

        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #e5e7eb;
            font-family: "Baloo 2", Arial, sans-serif;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .a4-page {
            width: 210mm;
            height: 297mm;
            position: relative;
            overflow: hidden;
            background: #fff;
            margin: 20px auto;
            box-shadow: 0 5px 30px rgba(0, 0, 0, .15);
        }

        @media print {
            html,
            body {
                width: 210mm;
                height: 297mm;
                background: #fff;
            }

            .a4-page {
                width: 210mm;
                height: 297mm;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .a4-page:last-of-type {
                page-break-after: auto;
            }

            .no-print {
                display: none !important;
            }
        }

        /* =========================================
           DECORATIVE CMYK LINES
        ========================================= */

        .cmyk-top {
            position: absolute;
            top: 0;
            right: 0;
            width: 52mm;
            height: 29mm;
            overflow: hidden;
            pointer-events: none;
        }

        .cmyk-top span {
            position: absolute;
            width: 75mm;
            height: 16mm;
            border-radius: 100px;
            transform: rotate(-45deg);
        }

        .cmyk-top .cyan {
            background: #009fe3;
            top: -11mm;
            right: -20mm;
        }

        .cmyk-top .magenta {
            background: #ed008c;
            top: -7mm;
            right: -18mm;
        }

        .cmyk-top .yellow {
            background: #ffdc00;
            top: -3mm;
            right: -16mm;
        }

        .cmyk-top .black {
            background: #151515;
            top: 1mm;
            right: -14mm;
        }

        .cmyk-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5mm;
            display: flex;
            overflow: hidden;
        }

        .cmyk-bottom .cyan {
            width: 34%;
            background: #009fe3;
        }

        .cmyk-bottom .magenta {
            width: 30%;
            background: #ed008c;
        }

        .cmyk-bottom .yellow {
            width: 15%;
            background: #ffdc00;
        }

        .cmyk-bottom .black {
            flex: 1;
            background: #151515;
        }

        /* =========================================
           CONTENT
        ========================================= */

        .page-content {
            position: relative;
            z-index: 2;
            height: 100%;
            padding:
                9mm
                9.5mm
                9mm
                9.5mm;
        }

        /* =========================================
           LOGO
        ========================================= */

        .logo-mark {
            position: relative;
            width: 42mm;
            height: 20mm;
        }

        .logo-mark span {
            position: absolute;
            height: 2.4mm;
            border-radius: 20px;
            transform: rotate(17deg);
        }

        .logo-mark .l1 {
            width: 34mm;
            left: 3mm;
            top: 5mm;
            background: #009fe3;
        }

        .logo-mark .l2 {
            width: 31mm;
            left: 6mm;
            top: 8mm;
            background: #ed008c;
        }

        .logo-mark .l3 {
            width: 28mm;
            left: 9mm;
            top: 11mm;
            background: #ffdc00;
        }

        .logo-mark .l4 {
            width: 25mm;
            left: 12mm;
            top: 14mm;
            background: #151515;
        }

        /* =========================================
           HEADER
        ========================================= */

        .header {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 4mm;
            height: 68mm;
        }

        .company-info {
            position: relative;
            padding-right: 4mm;
        }

        .company-divider {
            position: absolute;
            right: 0;
            top: 3mm;
            width: 0.3mm;
            height: 72mm;
            background: #000000;
        }

        .brand-name {
            margin-top: -1mm;
            font-size: 7mm;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -0.4mm;
        }

        .brand-name .malibu {
            color: #f45116;
        }

        .brand-name .print {
            color: #1387d6;
        }

        .brand-subtitle {
            margin-top: 2mm;
            color: #f45116;
            font-size: 2.1mm;
            font-weight: 600;
            letter-spacing: 1.3mm;
        }

        .company-contact {
            display: flex;
            flex-direction: column;
            gap: 3.5mm;
            color: #222;
            font-size: 2.8mm;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 3mm;
        }

        .contact-icon {
            width: 4mm;
            height: 4mm;
            flex-shrink: 0;
            color: #0879d1;
        }

        /* =========================================
           ORDER HEADER
        ========================================= */

        .order-header {
            position: relative;
            padding-left: 1mm;
        }

        .order-title {
            font-size: 7mm;
            line-height: 1;
            font-weight: 800;
            color: #f45116;
            margin-bottom: 2mm;
        }

        .order-code {
            display: inline-block;
            padding: 1.3mm 4mm;
            border-radius: 1.5mm;
            background: #0879d1;
            color: #fff;
            font-size: 3.1mm;
            font-weight: 700;
            letter-spacing: .1mm;
            margin-bottom: 4mm;
        }

        .order-meta {
            display: flex;
            flex-direction: column;
            gap: 3.5mm;
        }

        .order-meta-row {
            display: grid;
            grid-template-columns: 7mm 31mm 4mm 1fr;
            align-items: center;
            font-size: 2.8mm;
        }

        .order-meta-icon {
            width: 4.2mm;
            height: 4.2mm;
            color: #303b4a;
        }

        .order-meta-label {
            font-weight: 600;
        }

        /* =========================================
           INFORMATION BOXES
        ========================================= */

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6mm;
            height: 43mm;
        }

        .info-box {
            position: relative;
            border: .35mm solid;
            border-radius: 2mm;
            padding: 8mm 4mm 3.5mm;
        }

        .customer-box {
            border-color: #5ca9ef;
            background: linear-gradient(
                135deg,
                rgba(0, 136, 255, .025),
                rgba(0, 136, 255, .08)
            );
        }

        .order-box {
            border-color: #ff9a62;
            background: linear-gradient(
                135deg,
                rgba(255, 100, 0, .02),
                rgba(255, 100, 0, .06)
            );
        }

        .info-title {
            position: absolute;
            left: 3mm;
            top: -4mm;
            display: flex;
            align-items: center;
            gap: 2mm;
            padding: 1.6mm 3mm;
            border-radius: 1.5mm;
            color: #fff;
            font-size: 3.1mm;
            font-weight: 700;
        }

        .customer-box .info-title {
            background: #0879d1;
        }

        .order-box .info-title {
            background: #f45116;
        }

        .info-title svg {
            width: 4mm;
            height: 4mm;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 2.4mm;
            font-size: 2.7mm;
        }

        .info-row {
            display: grid;
            grid-template-columns: 31mm 4mm 1fr;
        }

        .info-label {
            font-weight: 500;
        }

        .status {
            color: #f45116;
            font-weight: 700;
        }

        /* =========================================
           PRODUCT TABLE
        ========================================= */

        .product-table-wrap {
            margin-top: 5mm;
            border: .3mm solid #6aa9e6;
            border-radius: 2mm;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            background: #0879d1;
            color: white;
        }

        th {
            height: 9mm;
            padding: 1mm;
            font-size: 2.8mm;
            font-weight: 700;
            border-right: .2mm solid rgba(255,255,255,.45);
        }

        th:last-child {
            border-right: none;
        }

        td {
            height: 18mm;
            padding: 2mm 2.5mm;
            border-right: .2mm solid #d9dee5;
            border-bottom: .2mm solid #d9dee5;
            vertical-align: middle;
            font-size: 2.55mm;
        }

        tr:last-child td {
            border-bottom: none;
        }

        td:last-child {
            border-right: none;
        }

        tbody tr:nth-child(even) {
            background: #f7f9fb;
        }

        .col-stt {
            width: 8%;
            text-align: center;
        }

        .col-product {
            width: 23%;
        }

        .col-description {
            width: 27%;
        }

        .col-quantity {
            width: 14%;
            text-align: center;
        }

        .col-price {
            width: 14%;
            text-align: center;
        }

        .col-total {
            width: 14%;
            text-align: center;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 2mm;
        }

        .product-image {
            width: 16mm;
            height: 14mm;
            object-fit: contain;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: 700;
            font-size: 2.7mm;
        }

        .service-row {
            background: #eff6ff !important;
        }

        .service-row td {
            height: 10mm;
            color: #475569;
        }

        .service-name {
            color: #0879d1;
            font-size: 2.5mm;
            font-weight: 700;
        }

        .description {
            line-height: 1.45;
        }

        .money {
            font-weight: 500;
            white-space: nowrap;
        }

        .total-money {
            color: #f45116;
            font-weight: 800;
            white-space: nowrap;
        }

        /* =========================================
           BOTTOM INFORMATION
        ========================================= */

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1.08fr;
            gap: 3mm;
            margin-top: 4mm;
            height: 36mm;
        }

        .bottom-box {
            border: .3mm solid;
            border-radius: 2mm;
            padding: 3mm;
        }

        .note-box {
            border-color: #77b9ef;
        }

        .payment-box {
            border-color: #f4d45e;
        }

        .summary-box {
            border-color: #d6d6d6;
            padding: 0;
            overflow: hidden;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 2mm;
            font-size: 3mm;
            font-weight: 700;
            margin-bottom: 2.5mm;
        }

        .note-box .section-heading {
            color: #0879d1;
        }

        .payment-box .section-heading {
            color: #f45116;
            justify-content: center;
        }

        .section-heading svg {
            width: 4mm;
            height: 4mm;
        }

        .notes {
            display: flex;
            flex-direction: column;
            gap: 1.8mm;
            font-size: 2.15mm;
            line-height: 1.35;
        }

        .note-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5mm;
        }

        .check {
            width: 3mm;
            height: 3mm;
            flex-shrink: 0;
            margin-top: .2mm;
            color: #0879d1;
        }

        .payment-content {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 3mm;
            align-items: center;
        }

        .qr {
            width: 30mm;
            height: 30mm;
            object-fit: contain;
        }

        .payment-details {
            font-size: 2.15mm;
            line-height: 1.8;
        }

        .payment-details .label {
            display: inline-block;
            width: 17mm;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 2.2mm 3mm 0;
            font-size: 2.3mm;
        }

        .summary-total {
            margin-top: 2mm;
            padding: 3mm;
            background: #f45116;
            color: #fff;
            text-align: center;
        }

        .summary-total-label {
            font-size: 2.5mm;
            font-weight: 700;
        }

        .summary-total-price {
            font-size: 5.5mm;
            font-weight: 800;
            margin-top: .5mm;
        }

        .summary-words {
            text-align: center;
            font-size: 2.15mm;
            padding: 1.5mm;
        }

        /* =========================================
           SIGNATURE
        ========================================= */

        .signature {
            height: 27mm;
            margin-top: 3mm;
            border: .3mm solid #dedede;
            border-radius: 2mm;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            overflow: hidden;
        }

        .signature-item {
            position: relative;
            text-align: center;
            padding-top: 3mm;
            border-right: .3mm solid #ddd;
        }

        .signature-item:last-child {
            border-right: none;
        }

        .signature-title {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2mm;
            font-size: 2.9mm;
            font-weight: 700;
        }

        .signature-item:nth-child(1) .signature-title {
            color: #0879d1;
        }

        .signature-item:nth-child(2) .signature-title {
            color: #f45116;
        }

        .signature-item:nth-child(3) .signature-title {
            color: #ed008c;
        }

        .signature-title svg {
            width: 4.5mm;
            height: 4.5mm;
        }

        .signature-note {
            font-size: 2.2mm;
            margin-top: .5mm;
        }

        .signature-line {
            width: 70%;
            margin: 9mm auto 1.5mm;
            border-bottom: .25mm dotted #555;
        }

        .signature-date {
            font-size: 2.15mm;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: #0879d1;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
            transition: background .2s ease, transform .2s ease;
        }

        .print-button:hover {
            background: #0564b4;
            transform: translateY(-1px);
        }

        .print-button:active {
            transform: translateY(0);
        }

        .print-button svg {
            width: 18px;
            height: 18px;
        }

        @media print {
            .print-button {
                display: none !important;
            }
        }
    </style>
</head>

<body>
<button
    type="button"
    class="print-button no-print"
    onclick="window.print()"
    aria-label="{{ $documents->count() > 1 ? 'In các đơn hàng' : ($firstDocument['print_button_label'] ?? 'In chứng từ') }}"
>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M6 9V2h12v7"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <path d="M6 14h12v8H6z"/>
    </svg>
    <span>{{ $documents->count() > 1 ? 'In '.$documents->count().' đơn hàng' : ($firstDocument['print_button_label'] ?? 'In chứng từ') }}</span>
</button>

@foreach ($documents as $document)
    @php
        $customer = $document['customer'];
        $subtotal = max(0, (float) $document['subtotal']);
        $discount = max(0, (float) $document['discount']);
        $shippingFee = max(0, (float) $document['shipping_fee']);
        $adjustment = (float) $document['adjustment'];
        $total = max(0, (float) $document['total']);
        $pageNumber = $loop->iteration;
        $pageCount = $documents->count();
    @endphp

<div class="a4-page">

    <!-- ================================
         DECORATIVE ELEMENTS
    ================================= -->

    <div class="cmyk-top">
        <img src="{{ asset('storage/malibu-rainbow.png')  }}">
    </div>

    <div class="cmyk-bottom">
        <div class="cyan"></div>
        <div class="magenta"></div>
        <div class="yellow"></div>
        <div class="black"></div>
    </div>


    <main class="page-content">

        <!-- ================================
             HEADER
        ================================= -->

        <section class="header mb-6">

            <!-- COMPANY -->
            <div class="company-info">

               <div class="logo mb-5">
                   <img src="{{ asset('storage/malibu-print-logo.png')  }}">
               </div>

                <div class="company-contact font-bold">

                    <div class="contact-row">
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a8 8 0 0 0-8 8c0 5.8 8 12 8 12s8-6.2 8-12a8 8 0 0 0-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        <span>20 Đoàn Nguyễn Tuấn, P. Quy Nhơn, Tỉnh Gia Lai</span>
                    </div>

                    <div class="contact-row">
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.6 10.8c1.5 2.9 3.7 5.1 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.2 1.3.4 2.6.7 3.9.7.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C11.4 21 3 12.6 3 2.5c0-.6.4-1 1-1h3.3c.6 0 1 .4 1 1 0 1.3.2 2.6.7 3.9.1.4.1.8-.2 1.2l-2.2 2.2z"/>
                        </svg>
                        <span>0931 94 83 43</span>
                    </div>

                    <div class="contact-row">
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 5h20v14H2V5zm10 7L4 7v10h16V7l-8 5zm0-2.2L20 6H4l8 3.8z"/>
                        </svg>
                        <span>malibuprint@gmail.com</span>
                    </div>

                    <div class="contact-row">
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.9 6h-3.1a15.5 15.5 0 0 0-1.2-3.1A8.1 8.1 0 0 1 18.9 8zM12 4c.8 1.1 1.4 2.4 1.8 4h-3.6c.4-1.6 1-2.9 1.8-4zM4.3 14a8 8 0 0 1 0-4h3.3a16.6 16.6 0 0 0 0 4H4.3zm.8 2h3.1c.3 1.1.7 2.1 1.2 3.1A8.1 8.1 0 0 1 5.1 16zm3.1-8H5.1a8.1 8.1 0 0 1 4.3-3.1A15.5 15.5 0 0 0 8.2 8zM12 20c-.8-1.1-1.4-2.4-1.8-4h3.6c-.4 1.6-1 2.9-1.8 4zm2.2-6H9.8a14.7 14.7 0 0 1 0-4h4.4a14.7 14.7 0 0 1 0 4zm.4 5.1c.5-1 .9-2 1.2-3.1h3.1a8.1 8.1 0 0 1-4.3 3.1zM16.4 14a16.6 16.6 0 0 0 0-4h3.3a8 8 0 0 1 0 4h-3.3z"/>
                        </svg>
                        <span>www.malibuprint.vn</span>
                    </div>

                </div>

                <div class="company-divider"></div>
            </div>


            <!-- ORDER -->
            <div class="order-header pt-17">

                <div class="order-title">
                    {{ $document['document_title'] }}
                </div>

                <div class="order-code">
                    #{{ $document['code'] }}
                </div>

                <div class="order-meta">

                    <div class="order-meta-row">
                        <svg class="order-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span class="order-meta-label">Ngày Đặt Hàng</span>
                        <span>:</span>
                        <span class="order-meta-label">{{ $document['date']?->format('d/m/Y') ?? '—' }}</span>
                    </div>

                    <div class="order-meta-row">
                        <svg class="order-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span class="order-meta-label">Ngày giao hàng dự kiến</span>
                        <span>:</span>
                        <span class="order-meta-label">{{ $document['secondary_date']?->format('d/m/Y') ?? '—' }}</span>
                    </div>

                </div>
            </div>

        </section>


        <!-- ================================
             CUSTOMER + ORDER INFO
        ================================= -->

        <section class="info-grid mt-15">

            <!-- CUSTOMER -->
            <div class="info-box customer-box">

                <div class="info-title">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="9" cy="8" r="4"/>
                        <path d="M2 21a7 7 0 0 1 14 0H2z"/>
                        <circle cx="17" cy="7" r="3"/>
                        <path d="M15 13a6 6 0 0 1 7 6h-5"/>
                    </svg>

                    THÔNG TIN GIAO HÀNG
                </div>

                <div class="info-list">

                    <div class="info-row">
                        <span class="info-label">Tên khách hàng</span>
                        <span>:</span>
                        <span>{{ $customer['name'] ?? '—' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Địa chỉ</span>
                        <span>:</span>
                        <span>{{ $customer['address'] ?? '—' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Điện thoại</span>
                        <span>:</span>
                        <span>{{ $customer['phone'] ?? '—' }}</span>
                    </div>

                </div>

            </div>


            <!-- ORDER INFO -->
            <div class="info-box order-box">

                <div class="info-title">

                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 2h12v20H6z"/>
                        <path d="M9 6h6M9 10h6M9 14h4"
                              stroke="#fff"
                              stroke-width="1.5"
                              fill="none"/>
                    </svg>

                    {{ $document['info_heading'] }}
                </div>

                <div class="info-list">

                    <div class="info-row">
                        <span class="info-label">Thanh toán</span>
                        <span>:</span>
                        <span>{{ $document['payment_status'] }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Ghi chú</span>
                        <span>:</span>
                        <span class="text-xs font-bold">{{ $document['note'] ?: '—' }}</span>
                    </div>

                </div>

            </div>

        </section>


        <!-- ================================
             PRODUCT TABLE
        ================================= -->

        <section class="product-table-wrap">

            <table>

                <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th class="col-product">TÊN SẢN PHẨM</th>
                    <th class="col-description">QUY CÁCH / MÔ TẢ</th>
                    <th class="col-quantity">SỐ LƯỢNG</th>
                    <th class="col-price">ĐƠN GIÁ</th>
                    <th class="col-total">THÀNH TIỀN</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($document['items'] as $item)
                    <tr>
                        <td class="col-stt">{{ $loop->iteration }}</td>

                        <td class="col-product">
                            <div class="product-cell">
                                <span class="product-name">
                                    {{ $item['name'] }}
                                </span>
                            </div>
                        </td>

                        <td class="description">
                            @forelse ($item['description'] as $description)
                                <div>{{ $description }}</div>
                            @empty
                                <div>—</div>
                            @endforelse
                        </td>

                        <td class="col-quantity">
                            {{ number_format($item['quantity'], 0, ',', '.') }}
                            {{ $item['unit'] }}
                        </td>

                        <td class="col-price money">
                            {{ number_format($item['unit_price'], 0, ',', '.') }}
                        </td>

                        <td class="col-total total-money">
                            {{ number_format($item['total'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @foreach ($item['services'] as $service)
                        <tr class="service-row">
                            <td class="col-stt"></td>
                            <td class="col-product">
                                <span class="service-name">Dịch vụ: {{ $service['name'] }}</span>
                            </td>
                            <td class="description">Dịch vụ đi kèm sản phẩm</td>
                            <td class="col-quantity">
                                {{ number_format($service['quantity'], 0, ',', '.') }}
                            </td>
                            <td class="col-price money">
                                {{ number_format($service['unit_price'], 0, ',', '.') }}
                            </td>
                            <td class="col-total total-money">
                                {{ number_format($service['total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center;">
                            Đơn hàng chưa có sản phẩm.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </section>


        <!-- ================================
             NOTES / PAYMENT / SUMMARY
        ================================= -->

        <section class="bottom-grid">

            <!-- NOTES -->
            <div class="bottom-box note-box">

                <div class="section-heading">

                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 2h12l4 4v16H4z"/>
                        <path d="M14 2v6h6"
                              fill="#fff"/>
                        <path
                            d="M8 12h8M8 16h8"
                            stroke="#fff"
                            stroke-width="1.5"/>
                    </svg>

                    GHI CHÚ
                </div>

                <div class="notes">

                    <div class="note-item">
                        <svg class="check" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m7 12 3 3 7-7"
                                  fill="none"
                                  stroke="#fff"
                                  stroke-width="2"/>
                        </svg>

                        <span>
                            Vui lòng kiểm tra kỹ nội dung,
                            chính tả trước khi duyệt in.
                        </span>
                    </div>

                    <div class="note-item">
                        <svg class="check" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m7 12 3 3 7-7"
                                  fill="none"
                                  stroke="#fff"
                                  stroke-width="2"/>
                        </svg>

                        <span>
                            Thời gian giao hàng có thể thay đổi
                            tùy theo khối lượng thực tế.
                        </span>
                    </div>

                    <div class="note-item">
                        <svg class="check" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m7 12 3 3 7-7"
                                  fill="none"
                                  stroke="#fff"
                                  stroke-width="2"/>
                        </svg>

                        <span>
                            Cảm ơn quý khách đã tin tưởng và
                            sử dụng dịch vụ của chúng tôi!
                        </span>
                    </div>

                </div>

            </div>

            <!-- PAYMENT -->
            <div class="bottom-box payment-box">


                <div class="payment-content">

                    <img
                        class="qr"
                        src="{{ asset('storage/QR.png') }}"
                        alt="QR thanh toán"
                    >

                    <div class="payment-details">
                        <div class="section-heading">
                            THÔNG TIN THANH TOÁN
                        </div>
                        <div>
                            <span class="label">Ngân hàng</span>
                            :
                            BIDV
                        </div>

                        <div>
                            <span class="label">Số tài khoản</span>
                            :
                            8607377666
                        </div>

                        <div>
                            <span class="label">Chủ tài khoản</span>
                            :
                            HỘ KINH DOANH MALIBU PRINT
                        </div>

                    </div>

                </div>

            </div>


            <!-- SUMMARY -->
            <div class="bottom-box summary-box">

                <div class="summary-row">
                    <span>Tạm tính</span>
                    <strong>{{ number_format($subtotal, 0, ',', '.') }}</strong>
                </div>

                <div class="summary-row">
                    <span>Chiết khấu</span>
                    <strong>{{ number_format($discount, 0, ',', '.') }}</strong>
                </div>

                <div class="summary-row">
                    <span>Phí giao hàng</span>
                    <strong>{{ number_format($shippingFee, 0, ',', '.') }}</strong>
                </div>

                @if ($adjustment !== 0.0)
                    <div class="summary-row">
                        <span>Điều chỉnh đối soát</span>
                        <strong>{{ $adjustment > 0 ? '+' : '' }}{{ number_format($adjustment, 0, ',', '.') }}</strong>
                    </div>
                @endif

                <div class="summary-total">

                    <div class="summary-total-label">
                        {{ $document['total_label'] }}
                    </div>

                    <div class="summary-total-price">
                        {{ number_format($total, 0, ',', '.') }} VNĐ
                    </div>

                </div>

            </div>

        </section>

    </main>

</div>
@endforeach

</body>
</html>
