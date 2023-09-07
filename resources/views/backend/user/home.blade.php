@extends('backend.layouts.user_master')
@section('title', 'Dashboard')
@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <img src="{{ asset('assets/images/bar-graph.png') }}" style="height: 40px" />
                </div>

                <div style="display: flex">
                    <div class="col-md-12 col-sm-1" style="color:white;">
                        Trend Sarthi</div>
                </div>

            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="main-card mb-3 row">
                <div class="col-md-7">
                    <div class="card text-white bg-dark mb-3">
                        <div class="card-header"><img src="{{ asset('assets/images/bar-graph.png') }}"
                                style="height: 20px;margin-right:5px;" /> IO Decode</div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="barChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card text-white bg-dark mb-3">
                        <div class="card-body">
                            <div class="p-1 row">
                                <a href="#" class="button-62 col-4 ml-1">NIFTY</a>
                                <a href="#" class="button-62 col-4 ml-1">BANKNIFTY</a>
                            </div>
                        </div>
                        <div style="flex: row;padding:10px;background-color:rgb(22 26 32);">
                            <span style="color: white;font-size: 12px;margin:11px;">Expiry:</span>
                            <select style="width:8em">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="card text-white bg-dark mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="chart-container">
                                        <canvas id="barChartCallPut"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6 center">
                                    <h6 style="text-align: center;color: white;font-weight: bold">Change PE/CE</h6>
                                    <div style="text-align: center;">
                                        <strong style="display: block;color: aquamarine"> <span class="dot"></span>
                                            Change PE OI</strong>
                                        <span class="badge text-bg-light" id="putlabel">.....</span>
                                        <strong style="display: block;color: red"> <span class="dot"></span> Change CE
                                            OI</strong>
                                        <span class="badge text-bg-light" id="calllabel">.....</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <hr color="white">
                                </div>
                                <div class="col-md-6"> <canvas id="doughnutChart"></canvas></div>
                                <div class="col-md-6">
                                    <h6 style="text-align: center;color: white;font-weight: bold">P/C Ratio Net</h6>
                                    <div style="text-align: center;">
                                        <strong style="display: block;color: aquamarine"> <span class="dot"></span> Total
                                            PE OI</strong>
                                        <span class="badge text-bg-light" id="putlabelOI">.....</span>
                                        <strong style="display: block;color: red"> <span class="dot"></span> Total CE
                                            OI</strong>
                                        <span class="badge text-bg-light" id="calllabelOI">.....</span>
                                        <h6 style="text-align: center;color: white;font-weight: bold">PCR: <span
                                                id="putIOlabel">02</span></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>
    <script>
        $(document).ready(function() {
            $.get('/chart-data/Nifty', function(data) {
                var labels = [];
                var callData = [];
                var putData = [];
                data.forEach(function(item) {
                    labels.push(item.label);
                    callData.push(item.call);
                    putData.push(item.put);
                });
                console.log(data);
                var ctx = document.getElementById('barChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Call',
                                data: callData,
                                backgroundColor: 'rgba(255, 0, 0, 0.7)', // Red color for Call
                                borderColor: 'rgba(255, 0, 0, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Put',
                                data: putData,
                                backgroundColor: 'rgba(17, 250, 242)', // Blue color for Put
                                borderColor: 'rgba(17, 250, 242)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        scales: {
                            x: {
                                beginAtZero: true,
                                reverse: true,
                            },
                            y: {
                                beginAtZero: true,
                                // Reverse the y-axis to make it horizontal
                            },
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'Strike Price vs. Call/Put Data',
                                fontSize: 18
                            }
                        }
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $.get('/chart-data/Nifty', function(data) {
                var labels = [];
                var callData = [];
                var putData = [];

                var totalCall = 0; // Variable to calculate the total of Call
                var totalPut = 0; // Variable to calculate the total of Put

                data.forEach(function(item) {
                    labels.push(item.label);
                    callData.push(item.call);
                    putData.push(item.put);

                    // Update the totalCall and totalPut
                    totalCall += item.call;
                    totalPut += item.put;
                });
                var putLabelElement = document.getElementById("putlabel");
                putLabelElement.textContent = totalPut;
                var CallLabelElement = document.getElementById("calllabel");
                CallLabelElement.textContent = totalCall;

                var CallLabelElement = document.getElementById("calllabel");
                var ctx = document.getElementById('barChartCallPut').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar', // Change to vertical bar chart
                    data: {
                        labels: ['Call Put'],
                        datasets: [{
                                label: 'Total Call',
                                data: [totalCall],
                                backgroundColor: 'rgba(255, 0, 0, 0.7)', // Red color for Call
                                borderColor: 'rgba(255, 0, 0, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Total Put',
                                data: [totalPut],
                                backgroundColor: 'rgba(17, 250, 242)', // Blue color for Put
                                borderColor: 'rgba(17, 250, 242)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: { // Change the scale to y-axis
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'Strike Price vs. Call/Put Data',
                                fontSize: 18
                            }
                        }
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $.get('/chart-data/Nifty', function(data) {
                var labels = [];
                var callData = [];
                var putData = [];

                var totalCall = 0; // Variable to calculate the total of Call
                var totalPut = 0; // Variable to calculate the total of Put

                data.forEach(function(item) {
                    labels.push(item.label);
                    callData.push(item.calloi);
                    putData.push(item.putoi);

                    // Update the totalCall and totalPut
                    totalCall += item.calloi;
                    totalPut += item.putoi;
                });

                // Calculate percentages
                var total = totalCall + totalPut;
                var callPercentage = (totalCall / total) * 100;
                var putPercentage = (totalPut / total) * 100;
                var putLabelElement = document.getElementById("putlabelOI");
                putLabelElement.textContent = totalPut;
                var CallLabelElement = document.getElementById("calllabelOI");
                CallLabelElement.textContent = totalCall;
                var pcrvalue = (totalPut / totalCall).toFixed(2);
                var pcrLabelElement = document.getElementById("putIOlabel");
                pcrLabelElement.textContent = pcrvalue;
                var ctx = document.getElementById('doughnutChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'doughnut', // Change to doughnut chart
                    data: {
                        labels: ['Total Call (' + callPercentage.toFixed(2) + '%)', 'Total Put (' +
                            putPercentage.toFixed(2) + '%)'
                        ],
                        datasets: [{
                            data: [callPercentage, putPercentage],
                            backgroundColor: ['rgba(255, 0, 0, 0.7)',
                            'rgba(17, 250, 242)'], // Colors for Call and Put
                            borderColor: ['rgba(255, 0, 0, 1)', 'rgba(17, 250, 242)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'Total Call vs. Total Put',
                                fontSize: 18
                            }
                        }
                    }
                });
            });
        });
    </script>

@endsection
