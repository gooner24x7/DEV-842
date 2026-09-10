@php use Carbon\Carbon; @endphp
<html lang="en">
<head>
    <title>{{ $title }}</title>
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
        <h1>{{ $title }}</h1>
        <div class="text-right">{{ $company_name }}</div>
    </div>

    <div class="wrapper">
        <div class="report-summary">
            <h2>Overall</h2>
            <table id="projectSummaryTable">
                <tr>
                    <td>Project Name</td>
                    <td>{{ $project_name }}</td>
                </tr>
                @if(!empty($works_package))
                    <tr>
                        <td>Works Package:</td>
                        <td>{{ $works_package }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Total Materials Enquiries</td>
                    <td>{{ $totals['materials_enquiries'] }}</td>
                </tr>
                <tr>
                    <td>Total Materials Quotes Received</td>
                    <td>{{ $totals['materials_quotes'] }}</td>
                </tr>
                <tr>
                    <td>Total Merchants Quoted</td>
                    <td>{{ $totals['merchants_quoted'] }}</td>
                </tr>
                <tr>
                    <td>Total Trades Procurement Works Packages</td>
                    <td>{{ $totals['trades_wps'] }}</td>
                </tr>
                <tr>
                    <td>Total Trades Procurement Enquiries</td>
                    <td>{{ $totals['trades_enquiries'] }}</td>
                </tr>
                <tr>
                    <td>Total Trades Procurement Quotes Received</td>
                    <td>{{ $totals['trades_quotes'] }}</td>
                </tr>
                <tr>
                    <td>Total Materials Procurement Spent</td>
                    <td>£ {{ number_format($totals['materials_spent'], 2) }}</td>
                </tr>
                <tr>
                    <td>Total Trades Procurement Spent</td>
                    <td>£ {{ number_format($totals['trades_spent'], 2) }}</td>
                </tr>
                <tr>
                    <td>Fastest Trades Procurement Quote Received</td>
                    <td>{{ $min_quote_time }}</td>
                </tr>
                <tr>
                    <td>ESG Saving for Trades Procurement</td>
                    <td>{{ $trades_average_esg }}%</td>
                </tr>
                <tr>
                    <td>% of Sub contractors used live chat</td>
                    <td>{{ $percent_used_live_chat }}%</td>
                </tr>
            </table>
        </div>

        <div class="page-break"></div>

        <h2>Materials Procurement</h2>

        @if(!empty($quotes_accepted['materials']['data']))

        <div class="report-list-container">
            <h2>Framework</h2>
            <h3>Accepted Quotes</h3>
            <div style="display: inline-block;">
                <table class="quotes-table">
                    <tr>
                        <th>Merchant Name</th>
                        <th>Distance Within</th>
                        <th>Price (ex vat)</th>
                    </tr>

                    @foreach($quotes_accepted['materials']['data'] as $quote)
                        <tr>
                            <td>{{ $quote['first_name'] }}</td>
                            <td>{{ $quote['distance_within'] }}</td>
                            <td>£ {{ number_format($quote['price'], 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="report-summary" style="float: right;">
                <table id="frameworkSummaryTable">
                    <tr>
                        <td>Total Price:</td>
                        <td>£ {{ number_format($quotes_accepted['materials']['totals'][2], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Within 10 Miles:</td>
                        <td>£ {{ number_format($quotes_accepted['materials']['totals'][0], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Within 20 Miles:</td>
                        <td>£ {{ number_format($quotes_accepted['materials']['totals'][1], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Within 40 Miles:</td>
                        <td>£ {{ number_format($quotes_accepted['materials']['totals'][2], 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="report-list-container">
            <h2>Client (Doncaster)</h2>
            <h3>Accepted Quotes</h3>
            <div style="display: inline-block;">
                <table class="quotes-table">
                    <tr>
                        <th>Merchant Name</th>
                        <th>Distance Within</th>
                        <th>Price (ex vat)</th>
                    </tr>

                    @php
                        $client_total_materials = 0;
                    @endphp

                    @foreach($quotes_accepted['materials']['data'] as $quote)

                        @php
                            if ($quote['distance'] <= 20) {
                                $distance_within = '20 miles';
                                $client_total_materials += $quote['price'];
                            } else {
                                continue;
                            }
                        @endphp

                        <tr>
                            <td>{{ $quote['first_name'] }}</td>
                            <td>{{ $distance_within }}</td>
                            <td>£ {{ number_format($quote['price'], 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="report-summary" style="float: right;">
                <table id="clientSummaryTable">
                    <tr>
                        <td>Total Price:</td>
                        <td>£ {{ number_format($client_total_materials, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @else
            <div>No accepted materials quotes were found</div>
        @endif

        <div class="page-break"></div>

        <h2>Trades Procurement</h2>

        @if(!empty($quotes_accepted['trades']['data']))

            <div class="report-list-container">
                <h2>Framework</h2>
                <h3>Accepted Quotes</h3>
                <div style="display: inline-block;">
                    <table class="quotes-table">
                        <tr>
                            <th>Merchant Name</th>
                            <th>Distance Within</th>
                            <th>Price (ex vat)</th>
                        </tr>

                        @foreach($quotes_accepted['trades']['data'] as $quote)

                            <tr>
                                <td>{{ $quote['first_name'] }}</td>
                                <td>{{ $quote['distance_within'] }}</td>
                                <td>£ {{ number_format($quote['price'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="report-summary" style="float: right;">
                    <table id="frameworkSummaryTable">
                        <tr>
                            <td>Total Price:</td>
                            <td>£ {{ number_format($quotes_accepted['trades']['totals'][2], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Within 10 Miles:</td>
                            <td>£ {{ number_format($quotes_accepted['trades']['totals'][0], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Within 20 Miles:</td>
                            <td>£ {{ number_format($quotes_accepted['trades']['totals'][1], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Within 40 Miles:</td>
                            <td>£ {{ number_format($quotes_accepted['trades']['totals'][2], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="report-list-container">
                <h2>Client (Doncaster)</h2>
                <h3>Accepted Quotes</h3>
                <div style="display: inline-block;">
                    <table class="quotes-table">
                        <tr>
                            <th>Merchant Name</th>
                            <th>Distance Within</th>
                            <th>Price (ex vat)</th>
                        </tr>

                        @php
                            $client_total_trades = 0;
                        @endphp

                        @foreach($quotes_accepted['trades']['data'] as $quote)

                            @php
                                if ($quote['distance'] <= 20) {
                                    $distance_within = '20 miles';
                                    $client_total_trades += $quote['price'];
                                } else {
                                    continue;
                                }
                            @endphp

                            <tr>
                                <td>{{ $quote['first_name'] }}</td>
                                <td>{{ $distance_within }}</td>
                                <td>£ {{ number_format($quote['price'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="report-summary" style="float: right;">
                    <table id="clientSummaryTable">
                        <tr>
                            <td>Total Price:</td>
                            <td>£ {{ number_format($client_total_trades, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        @else
            <div>No accepted trades quotes were found</div>
        @endif

        <div class="page-break"></div>

        <div class="report-list-container">

            <h2>Trades Procurement</h2>
            <h2>Project Enquiries</h2>

            @foreach ($enquiries['trades'] as $id => $quotes)

                @php
                    $prices = array_column($quotes, 'price');
                    $max_price = max($prices);
                    $min_price = min($prices);
                    $accepted_price = 0.0;
                @endphp

                <div class="enquiry-item">
                    <h2>Enquiry ID {{ $id }}</h2>

                    @if (count($quotes) > 0)
                        <h2>Quotes</h2>
                        <table class="quotes-table">
                            <tr>
                                <th>Price (ex vat)</th>
                                <th>ESG Saving</th>
                                <th>Accepted</th>
                                <th>Time taken to quote</th>
                                <th>Total Savings</th>
                            </tr>

                            @foreach($quotes as $index => $quote)

                                @php
                                    // convert time diff from seconds to hours/minutes
                                    $start = new Carbon('@0');
                                    $end = new Carbon("@{$quote['time_difference']}");
                                    $time_diff = $start->diff($end)->format('%a days, %h hours, %i minutes');

                                    // calculate total savings
                                    $total_savings = '';

                                    if (!empty($quote['quote_accepted_at'])) {
                                        $total_savings = '£ ' . number_format($max_price - $quote['price'], 2);
                                        $accepted_price = $quote['price'];
                                    }
                                @endphp

                                <tr>
                                    <td>£ {{ number_format($quote['price'], 2) }}</td>
                                    <td>{{ number_format($quote['esgPerc'], 1) }}</td>
                                    <td>{{ $quote['quote_accepted_at'] }}</td>
                                    <td>{{ $time_diff }}</td>
                                    <td>{{ $total_savings }}</td>
                                </tr>
                            @endforeach

                            @php
                                // savings reminder
                                if ($accepted_price > $min_price) {
                                    $potential_savings = '£' . number_format($max_price - $min_price, 2);
                                    echo "<tr><td colspan='5'>If you accepted the lowest quote, you could have saved {$potential_savings}</td></tr>";
                                }
                            @endphp

                        </table>
                    @endif
                </div>
            @endforeach

        </div>

    </div>
</div>
</body>
</html>
