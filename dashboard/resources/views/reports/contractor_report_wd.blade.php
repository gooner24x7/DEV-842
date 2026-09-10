@php use Carbon\Carbon; @endphp
<html lang="en">
<head>
    <title>Wilmott Dixon Procurement Report</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="report.css" rel="stylesheet">
</head>

<body>
<div class="sidebar">
    <div class="logo">TBC</div>
</div>

<div class="content">
    <div class="header">
        <h1>Wilmott Dixon Procurement Report</h1>
    </div>

    <div class="wrapper">

        <div class="report-map-container">
            <img src="{{ $quotesMapImg }}" alt="report-map"/>
        </div>

        <div class="report-list-container" style="margin-top: 20px">
            <table class="quotes-table">
                <tr>
                    <td>Within 10 Miles:</td>
                    <td>£ {{ number_format($quotes_accepted['materials']['totals_local'][0], 2) }}</td>
                </tr>
                <tr>
                    <td>Within 20 Miles:</td>
                    <td>£ {{ number_format($quotes_accepted['materials']['totals_local'][1], 2) }}</td>
                </tr>
                <tr>
                    <td>Within 40 Miles:</td>
                    <td>£ {{ number_format($quotes_accepted['materials']['totals_local'][2], 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="page-break"></div>

        @if(!empty($quotes_accepted['materials']['data']))
            <div class="report-list-container">
                <h2>Accepted Quotes (Materials)</h2>
                <h3>Framework</h3>
                <div>
                    <table class="quotes-table">
                        <tr>
                            <th>Merchant Name</th>
                            <th>Sub-contractor Name</th>
                            <th>Postcode</th>
                            <th>Distance Within</th>
                            <th>Exact Distance</th>
                            <th>Local Material Spend</th>
                            <th>Description of Goods</th>
                        </tr>

                        @foreach($quotes_accepted['materials']['data'] as $quote)
                            <tr>
                                <td>{{ $quote['first_name'] }}</td>
                                <td>{{ $quote['contractor_name'] }}</td>
                                <td>{{ $quote['postcode'] }}</td>
                                <td>{{ $quote['distance_within'] }}</td>
                                <td>{{ number_format($quote['distance'], 2) }}</td>
                                <td class="text-nowrap">£ {{ number_format($quote['local_material_spend'], 2) }}</td>
                                <td>{{ $quote['description'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        <div class="page-break"></div>

        @if(!empty($quotes_accepted['trades']['data']))
            <div class="report-list-container">
                <h2>Accepted Quotes (Trades)</h2>
                <h3>Framework</h3>
                <div>
                    <table class="quotes-table">
                        <tr>
                            <th>Sub-contractor Name</th>
                            <th>Postcode</th>
                            <th>Distance Within</th>
                            <th>Exact Distance</th>
                            <th>Price</th>
                            <th>Description of Goods</th>
                        </tr>

                        @foreach($quotes_accepted['trades']['data'] as $quote)
                            <tr>
                                <td>{{ $quote['first_name'] }}</td>
                                <td>{{ $quote['postcode'] }}</td>
                                <td>{{ $quote['distance_within'] }}</td>
                                <td>{{ number_format($quote['distance'], 2) }}</td>
                                <td class="text-nowrap">£ {{ number_format($quote['price'], 2) }}</td>
                                <td>{{ $quote['description'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        <div class="page-break"></div>

        <div class="report-map-container" style="margin-top: 20px;">
            <img src="{{ $postcodeMapImg }}" alt="postcode-map"/>
        </div>

        <div class="page-break"></div>

        @if(!empty($project_districts))
            <div class="report-list-container">
                <h2>Quotes by Postcode Area</h2>
                <div>
                    <table class="quotes-table">
                        <tr>
                            <th>Postcode Area</th>
                            <th>Total Materials</th>
                            <th>Total Trades</th>
                        </tr>

                        @foreach($project_districts as $key => $value)
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="text-nowrap">£ {{ number_format($value['materials'], 2) }}</td>
                                <td class="text-nowrap">£ {{ number_format($value['trades'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

    </div>
</div>
</body>
</html>
