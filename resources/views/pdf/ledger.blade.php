<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 130px 36px 64px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2933;
            margin: 0;
        }

        /* Fixed page header repeated on every page */
        #page-header {
            position: fixed;
            top: -104px;
            left: 0;
            right: 0;
            height: 96px;
        }

        .brand-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
        }

        .brand-meta {
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
            margin-top: 2px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .rule {
            border-bottom: 2px solid #0f172a;
            margin-top: 8px;
        }

        /* Fixed page footer repeated on every page */
        #page-footer {
            position: fixed;
            bottom: -44px;
            left: 0;
            right: 0;
            height: 36px;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        #page-footer .pagenum:before {
            content: counter(page);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            vertical-align: top;
            padding: 0;
        }

        .party {
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
        }

        .party .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: bold;
        }

        .party .value {
            color: #1f2933;
            font-weight: bold;
            font-size: 12px;
        }

        .ledger {
            margin-top: 14px;
        }

        .ledger th {
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 7px 8px;
            text-align: left;
        }

        .ledger td {
            padding: 6px 8px;
            border-bottom: 1px solid #e8edf2;
            font-size: 10.5px;
            vertical-align: top;
        }

        .ledger tr.alt td {
            background: #f7f9fc;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .muted {
            color: #94a3b8;
        }

        .summary {
            margin-top: 16px;
            width: 100%;
        }

        .summary td {
            padding: 0;
        }

        .summary-box {
            float: right;
            width: 240px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .summary-box .row {
            padding: 7px 12px;
            font-size: 11px;
        }

        .summary-box .row .k {
            color: #64748b;
        }

        .summary-box .row .v {
            float: right;
            font-weight: bold;
            color: #0f172a;
        }

        .summary-box .closing {
            background: #0f172a;
            color: #ffffff;
        }

        .summary-box .closing .k,
        .summary-box .closing .v {
            color: #ffffff;
        }

        .note {
            clear: both;
            margin-top: 18px;
            font-size: 9.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .empty {
            padding: 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div id="page-header">
        <table class="meta-table">
            <tr>
                <td style="width: 60%;">
                    <div class="brand-name">{{ $storeName }}</div>
                    <div class="brand-meta">
                        @if ($storeAddress){{ $storeAddress }}<br>@endif
                        @if ($storePhone)Phone: {{ $storePhone }}@endif
                        @if ($storePhone && $storeEmail) &nbsp;&middot;&nbsp; @endif
                        @if ($storeEmail)Email: {{ $storeEmail }}@endif
                    </div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="doc-title">{{ $title }}</div>
                    <div class="doc-sub">Generated {{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
        <div class="rule"></div>
    </div>

    <div id="page-footer">
        <table>
            <tr>
                <td style="text-align: left;">{{ $storeName }} — {{ $title }}</td>
                <td style="text-align: right;">Page <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <table class="meta-table" style="margin-bottom: 4px;">
        <tr>
            <td style="width: 55%;">
                <div class="party">
                    <div class="label">Statement For</div>
                    <div class="value">{{ $partyName }}</div>
                    @if ($partyPhone)<div>Phone: {{ $partyPhone }}</div>@endif
                    @if ($partyEmail)<div>Email: {{ $partyEmail }}</div>@endif
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="party">
                    <div class="label">Currency</div>
                    <div class="value">{{ $currencyCode }}</div>
                    <div>{{ $rowCount }} {{ \Illuminate\Support\Str::plural('entry', $rowCount) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 15%;">Reference</th>
                <th style="width: 22%;">Note</th>
                <th style="width: 14%;" class="num">Debit</th>
                <th style="width: 14%;" class="num">Credit</th>
                <th style="width: 14%;" class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                @php
                    // ledgerRows() yields [date, reference, note, type, amount, balance].
                    // Split the single signed amount into Debit / Credit columns by the
                    // transaction type word ("... Debit" / "... Credit"); show the
                    // magnitude in the matching column and leave the other blank.
                    $rowType = strtolower((string) ($row[3] ?? ''));
                    $absAmount = ltrim((string) ($row[4] ?? ''), '-');
                    $isDebit = str_contains($rowType, 'debit');
                    $isCredit = str_contains($rowType, 'credit');
                @endphp
                <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                    <td class="muted">{{ $i + 1 }}</td>
                    <td>{{ $row[0] ?: '—' }}</td>
                    <td>{{ $row[1] ?: '—' }}</td>
                    <td>{{ $row[2] ?: '—' }}</td>
                    <td class="num {{ $isDebit ? '' : 'muted' }}">{{ $isDebit ? $absAmount : '—' }}</td>
                    <td class="num {{ $isCredit ? '' : 'muted' }}">{{ $isCredit ? $absAmount : '—' }}</td>
                    <td class="num {{ $row[5] === '—' ? 'muted' : '' }}">{{ $row[5] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No ledger entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($rows))
        @php
            // Grand totals: sum the debit and credit magnitudes across all rows.
            $decimals = $decimalPlaces ?? 2;
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($rows as $totalsRow) {
                $rowType = strtolower((string) ($totalsRow[3] ?? ''));
                $rowAmount = (float) str_replace(',', '', ltrim((string) ($totalsRow[4] ?? ''), '-'));
                if (str_contains($rowType, 'debit')) {
                    $totalDebit += $rowAmount;
                } elseif (str_contains($rowType, 'credit')) {
                    $totalCredit += $rowAmount;
                }
            }
        @endphp
        <table class="summary">
            <tr>
                <td>
                    <div class="summary-box">
                        <div class="row">
                            <span class="k">Total Debit</span>
                            <span class="v">{{ $currencyCode }} {{ number_format($totalDebit, $decimals) }}</span>
                        </div>
                        <div class="row">
                            <span class="k">Total Credit</span>
                            <span class="v">{{ $currencyCode }} {{ number_format($totalCredit, $decimals) }}</span>
                        </div>
                        <div class="row closing">
                            <span class="k">Closing Balance</span>
                            <span class="v">{{ $currencyCode }} {{ $closingBalance }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <div class="note">
        This is a system-generated statement and does not require a signature.
        @if ($includePaidSales)
            Rows marked “Paid Sale” are informational references and do not affect the running ledger balance.
        @endif
    </div>
</body>
</html>
