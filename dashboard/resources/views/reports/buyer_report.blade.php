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
            <table id="projectSummaryTable">
                <tr>
                    <td>Project Name:</td>
                    <td>{{ $project_name }}</td>
                </tr>
                @if(!empty($works_package))
                    <tr>
                        <td>Works Package:</td>
                        <td>{{ $works_package }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Total Enquiries:</td>
                    <td>{{ $total_enquiries }}</td>
                </tr>
                <tr>
                    <td>Total Quotes Received:</td>
                    <td>{{ $total_quotes }}</td>
                </tr>
                <tr>
                    <td>Merchants Quoted:</td>
                    <td>{{ $merchants_quoted }}</td>
                </tr>
                <tr>
                    <td>New Relationships:</td>
                    <td>{{ $new_relationships }}</td>
                </tr>
            </table>
        </div>

        <div class="report-chart">
            <h2>Overall Report</h2>
            <div class="pie-chart">
                <div class="row">
                    <div class="section-1">
                        <div class="circle"></div>
                        <div class="text top-left">Fastest quote time <br> {{ $min_quote_time }}</div>
                    </div>
                    <div class="section-2">
                        <div class="circle"></div>
                        <div class="text top-right">Price Total <br> £ {{ $total_price }}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="section-3">
                        <div class="circle"></div>
                        <div class="text bottom-left">ESG Total <br> {{ $total_esg }}%</div>
                    </div>
                    <div class="section-4">
                        <div class="circle"></div>
                        <div class="text bottom-right">% of merchants <br> used live chat <br> {{ $total_messages }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-list-container">
            @if(!empty($works_package))
                <h2>Works Package Enquiries</h2>
            @else
                <h2>Project Enquiries</h2>
            @endif

            @foreach ($questions as $id => $answers)

                @php
                    $prices = array_column($answers, 'price');
                    $max_price = max($prices);
                    $min_price = min($prices);
                    $accepted_price = 0.0;
                @endphp

                <div class="enquiry-item">
                    <h2>Enquiry ID {{ $id }}</h2>

                    @if (count($answers) > 0)
                        <h2>Quotes</h2>
                        <table class="quotes-table">
                            <tr>
                                <th>Price (ex vat)</th>
                                <th>ESG Saving</th>
                                <th>Accepted</th>
                                <th>Time taken to quote</th>
                                <th>Total Savings</th>
                            </tr>

                            @foreach($answers as $index => $answer)

                                @php
                                    // convert time diff from seconds to hours/minutes
                                    $start = new Carbon('@0');
                                    $end = new Carbon("@{$answer['time_difference']}");
                                    $time_diff = $start->diff($end)->format('%a days, %h hours, %i minutes');

                                    // calculate total savings
                                    $total_savings = '';

                                    if (!empty($answer['quote_accepted_at'])) {
                                        $total_savings = '£ ' . number_format($max_price - $answer['price'], 2);
                                        $accepted_price = $answer['price'];
                                    }
                                @endphp

                                <tr>
                                    <td>£ {{ number_format($answer['price'], 2) }}</td>
                                    <td>{{ number_format($answer['esgPerc'], 1) }}</td>
                                    <td>{{ $answer['quote_accepted_at'] }}</td>
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

        @if(!empty($quotes_accepted) && empty($works_package))
        <div class="page-break"></div>

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

                    @foreach($quotes_accepted as $quote)

                        @php

                        if ($quote['distance'] <= 10) {
                            $distance_within = '10 miles';
                            $framework_totals[0] += $quote['price'];
                        } else if ($quote['distance'] <= 20) {
                            $distance_within = '20 miles';
                            $framework_totals[1] += $quote['price'];
                        } else if ($quote['distance'] <= 40) {
                            $distance_within = '40 miles';
                            $framework_totals[2] += $quote['price'];
                        } else {
                            continue;
                        }

                        @endphp

                        <tr>
                            <td>{{ $quote['first_name'] }}</td>
                            <td>{{ $distance_within }}</td>
                            <td>£ {{ $quote['price'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="report-summary" style="float: right;">
                <table id="frameworkSummaryTable">
                    <tr>
                        <td>Total Price:</td>
                        <td>£ {{ array_sum($framework_totals) }}</td>
                    </tr>
                    <tr>
                        <td>Within 10 Miles:</td>
                        <td>£ {{ $framework_totals[0] }}</td>
                    </tr>
                    <tr>
                        <td>Within 20 Miles:</td>
                        <td>£ {{ $framework_totals[1] }} <br>(£ {{ $framework_totals[1] + $framework_totals[0] }})</td>
                    </tr>
                    <tr>
                        <td>Within 40 Miles:</td>
                        <td>£ {{ $framework_totals[2] }} <br>(£ {{ array_sum($framework_totals) }})</td>
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

                    @foreach($quotes_accepted as $quote)

                        @php

                            if ($quote['distance'] <= 20) {
                                $distance_within = '20 miles';
                                $client_total += $quote['price'];
                            } else {
                                continue;
                            }

                        @endphp

                        <tr>
                            <td>{{ $quote['first_name'] }}</td>
                            <td>{{ $distance_within }}</td>
                            <td>£ {{ $quote['price'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div class="report-summary" style="float: right;">
                <table id="clientSummaryTable">
                    <tr>
                        <td>Total Price:</td>
                        <td>£ {{ $client_total }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @endif

        <div class="page-break"></div>

        <div class="chat-gpt-summary">
            <h2>AI Summary</h2>
            <p>{!! nl2br($summary) !!}</p>
        </div>
    </div>
</div>
</body>
</html>
