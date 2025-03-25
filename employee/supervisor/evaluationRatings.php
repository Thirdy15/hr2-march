<?php
session_start();

// Include database connection
include '../../db/db_conn.php';

// Redirect if not logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

$employeeId = $_SESSION['employee_id'];

// Fetch employee information
$sql = "SELECT employee_id, first_name, middle_name, last_name, birthdate, email, role, position, department, phone_number, address, pfp 
        FROM employee_register 
        WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

// Fetch evaluation data
$sql = "SELECT 
            AVG(quality) AS avg_quality, 
            AVG(communication_skills) AS avg_communication_skills, 
            AVG(teamwork) AS avg_teamwork, 
            AVG(punctuality) AS avg_punctuality, 
            AVG(initiative) AS avg_initiative,
            COUNT(*) AS total_evaluations 
        FROM evaluations 
        WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $evaluation = $result->fetch_assoc();
} else {
    $evaluation = [
        'avg_quality' => 0,
        'avg_communication_skills' => 0,
        'avg_teamwork' => 0,
        'avg_punctuality' => 0,
        'avg_initiative' => 0,
        'total_evaluations' => 0
    ];
}

// Calculate overall average
$overallAverage = 0;
if ($evaluation['total_evaluations'] > 0) {
    $overallAverage = ($evaluation['avg_quality'] + $evaluation['avg_communication_skills'] + 
                      $evaluation['avg_teamwork'] + $evaluation['avg_punctuality'] + 
                      $evaluation['avg_initiative']) / 5;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Result | HR2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #0077b6;
            --accent-color: #48cae4;
            --grid-color: rgba(73, 80, 87, 0.5);
            --dark-bg: #0a0a0a;
            --card-bg: rgba(33, 37, 41) !important;
            --border-color: #2a2a2a;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --text-muted: #6c757d;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --danger-color: #ef476f;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        
        .container-fluid {
            padding: 1.5rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .page-header h1 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .evaluation-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .chart-container {
            background-color: #141414;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            border-radius: 6px;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--text-primary);
            text-align: center;
        }
        
        .chart-subtitle {
            font-size: 10px;
            color: white;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        
        .chart-controls {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        
        .chart-control-btn {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .chart-control-btn:hover {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--primary-color);
        }
        
        .chart-control-btn.active {
            background-color: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }
        
        .data-table th {
            background-color: rgba(0, 0, 0, 0.3);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
        }
        
        .data-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        
        .data-table tr:nth-child(odd) td {
            background-color: rgba(255, 255, 255, 0.02);
        }
        
        .data-table tr:hover td {
            background-color: rgba(0, 180, 216, 0.05);
        }
        
        .category-cell {
            display: flex;
            align-items: center;
        }
        
        .category-cell i {
            margin-right: 0.75rem;
            width: 16px;
            color: var(--primary-color);
        }
        
        .value-cell {
            text-align: center;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        
        .value-cell .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
            background-color: rgba(0, 180, 216, 0.15);
            color: var(--primary-color);
        }
        
        .summary-row {
            background-color: rgba(0, 0, 0, 0.2) !important;
        }
        
        .summary-row td {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 10px;
            color: white;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 0.5rem;
        }
        
        .chart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .chart-footer .evaluation-count {
            display: flex;
            align-items: center;
        }
        
        .chart-footer .evaluation-count i {
            margin-right: 0.5rem;
        }
        
        .chart-footer .timestamp {
            font-family: 'Courier New', monospace;
        }
        
        .data-point {
            position: relative;
            cursor: pointer;
        }
        
        .data-point:hover::after {
            content: attr(data-value);
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 10;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .chart-controls {
                flex-wrap: wrap;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }
        
        /* Calendar container styling */
        #calendarContainer {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            padding: 1.25rem;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .calendar-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }
        
        .btn-close-calendar {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .btn-close-calendar:hover {
            color: var(--text-primary);
        }
        
        /* Loading modal styling */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 180, 216, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Tooltip styling */
        .tooltip-inner {
            background-color: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            max-width: 250px;
        }
        
        .bs-tooltip-auto[x-placement^=top] .arrow::before, 
        .bs-tooltip-top .arrow::before {
            border-top-color: rgba(0, 0, 0, 0.9);
        }
        /* Dark Modal Styling */
        .modal-content.bg-dark {
            background-color: var(--bg-black); /* Use the dark background variable */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary); /* Ensure text is readable */
        }

        .modal-header.border-bottom {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .modal-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer.border-top {
            border-top: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .btn-close {
            color: var(--text-primary);
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .btn-close:hover {
            opacity: 1;
        }

    </style>
</head>
<body class="sb-nav-fixed bg-black">
   <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative">
                    <div class="page-header">
                        <h1><i class="fas fa-chart-line"></i>Evaluation Analytics</h1>
                        <div>
                            <button class="btn btn-outline-light btn-sm me-2" id="exportData">
                                <i class="fas fa-download me-1"></i>Export Data
                            </button>
                        </div>
                    </div>
                    
                    <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                        width: 80%; height: 80%; display: none;">
                        <div class="calendar-header">
                            <h3><i class="fas fa-calendar-alt me-2"></i>Calendar</h3>
                            <button type="button" class="btn-close-calendar" id="closeCalendar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="evaluation-card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-bar me-2"></i>Performance Metrics</h2>
                            <div class="chart-controls">
                                <button class="chart-control-btn text-white" data-chart-type="line">
                                    <i class="fas fa-chart-line me-1"></i>Line
                                </button>
                                <button class="chart-control-btn text-white" data-chart-type="bar">
                                    <i class="fas fa-chart-bar me-1"></i>Bar
                                </button>
                                <button class="chart-control-btn text-white" data-chart-type="radar">
                                    <i class="fas fa-spider me-1"></i>Radar
                                </button>
                                <button class="chart-control-btn text-white" data-chart-type="polarArea">
                                    <i class="fas fa-circle-notch me-1"></i>Polar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-title">Performance Evaluation Metrics</div>
                            <div class="chart-subtitle">
                                Analysis of <?php echo htmlspecialchars($employeeInfo['first_name'] . ' ' . $employeeInfo['last_name']); ?>'s performance across key competency areas
                            </div>
                            
                            <div class="chart-container">
                                <canvas id="evaluationChart" height="300"></canvas>
                            </div>
                            
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: rgba(0, 180, 216, 0.7)"></div>
                                    <span>Quality of Work</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: rgba(0, 119, 182, 0.7)"></div>
                                    <span>Communication Skills</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: rgba(72, 202, 228, 0.7)"></div>
                                    <span>Teamwork</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: rgba(144, 224, 239, 0.7)"></div>
                                    <span>Punctuality</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: rgba(202, 240, 248, 0.7)"></div>
                                    <span>Initiative</span>
                                </div>
                            </div>
                            
                            <div class="chart-footer">
                                <div class="evaluation-count text-white">
                                    <i class="fas fa-clipboard-check"></i>
                                    Based on <?php echo $evaluation['total_evaluations']; ?> evaluation<?php echo $evaluation['total_evaluations'] != 1 ? 's' : ''; ?>
                                </div>
                                <div class="timestamp text-white">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('Y-m-d H:i:s'); ?>
                                </div>
                            </div>
                            
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="50%">Performance Category</th>
                                        <th width="25%">Rating (0-6)</th>
                                        <th width="25%">Percentile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-medal"></i>
                                                Quality of Work
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT"><?php echo htmlspecialchars(number_format($evaluation['avg_quality'], 2)); ?></span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($evaluation['avg_quality'] / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-comments"></i>
                                                Communication Skills
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT"><?php echo htmlspecialchars(number_format($evaluation['avg_communication_skills'], 2)); ?></span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($evaluation['avg_communication_skills'] / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-users"></i>
                                                Teamwork
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT"><?php echo htmlspecialchars(number_format($evaluation['avg_teamwork'], 2)); ?></span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($evaluation['avg_teamwork'] / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-clock"></i>
                                                Punctuality
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT"><?php echo htmlspecialchars(number_format($evaluation['avg_punctuality'], 2)); ?></span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($evaluation['avg_punctuality'] / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-lightbulb"></i>
                                                Initiative
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT"><?php echo htmlspecialchars(number_format($evaluation['avg_initiative'], 2)); ?></span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($evaluation['avg_initiative'] / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                    <tr class="summary-row">
                                        <td>
                                            <div class="category-cell">
                                                <i class="fas fa-star"></i>
                                                Overall Average
                                            </div>
                                        </td>
                                        <td class="value-cell">
                                            <span class="badgeT" style="background-color: rgba(6, 214, 160, 0.15); color: var(--success-color);">
                                                <?php echo htmlspecialchars(number_format($overallAverage, 2)); ?>
                                            </span>
                                        </td>
                                        <td class="value-cell">
                                            <?php echo htmlspecialchars(number_format(($overallAverage / 6) * 100, 1)); ?>%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                        <!-- Logout Modal -->
                    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark">
                                <div class="modal-header">
                                    <h5 class="modal-titleT" id="logoutModalLabel">Confirm Logout</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-sign-out-alt text-warning" style="font-size: 3rem;"></i>
                                        <p class="mt-3">Are you sure you want to log out?</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form action="../../employee/logout.php" method="POST">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                    <div class="loading-spinner"></div>
                    <div class="mt-3 text-light fw-bold">Processing data...</div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="../../js/employee.js"></script>
    <script>
        // Initialize variables
        let myChart;
        let currentChartType = 'bar';
        
        // Chart data
        const chartData = {
            labels: [
                'Quality of Work', 
                'Communication Skills', 
                'Teamwork', 
                'Punctuality', 
                'Initiative'
            ],
            datasets: [{
                label: 'Performance Metrics',
                data: [
                    <?php echo htmlspecialchars(number_format($evaluation['avg_quality'], 2)); ?>,
                    <?php echo htmlspecialchars(number_format($evaluation['avg_communication_skills'], 2)); ?>,
                    <?php echo htmlspecialchars(number_format($evaluation['avg_teamwork'], 2)); ?>,
                    <?php echo htmlspecialchars(number_format($evaluation['avg_punctuality'], 2)); ?>,
                    <?php echo htmlspecialchars(number_format($evaluation['avg_initiative'], 2)); ?>
                ],
                backgroundColor: [
                    'rgba(0, 180, 216, 0.7)',
                    'rgba(0, 119, 182, 0.7)',
                    'rgba(72, 202, 228, 0.7)',
                    'rgba(144, 224, 239, 0.7)',
                    'rgba(202, 240, 248, 0.7)'
                ],
                borderColor: [
                    'rgba(0, 180, 216, 1)',
                    'rgba(0, 119, 182, 1)',
                    'rgba(72, 202, 228, 1)',
                    'rgba(144, 224, 239, 1)',
                    'rgba(202, 240, 248, 1)'
                ],
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(0, 180, 216, 1)',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(0, 180, 216, 1)',
                tension: 0.3 // Smoother line for line chart
            }]
        };
        
        // Scientific chart configurations
        const chartConfigs = {
            bar: {
                type: 'bar',
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 6,
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)',
                                lineWidth: 1,
                                drawBorder: true
                            },
                            ticks: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                },
                                stepSize: 1,
                                padding: 8
                            },
                            title: {
                                display: true,
                                text: 'Rating (0-6 scale)',
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif",
                                    weight: '500'
                                },
                                padding: {top: 10, bottom: 10}
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)',
                                lineWidth: 1,
                                drawBorder: true
                            },
                            ticks: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                },
                                padding: 8
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: {
                                size: 13,
                                family: "'Inter', sans-serif"
                            },
                            bodyFont: {
                                size: 12,
                                family: "'Courier New', monospace"
                            },
                            padding: 12,
                            cornerRadius: 4,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Rating: ${context.raw} / 6.00 (${((context.raw/6)*100).toFixed(1)}%)`;
                                }
                            }
                        },
                        annotation: {
                            annotations: {
                                line1: {
                                    type: 'line',
                                    yMin: 3,
                                    yMax: 3,
                                    borderColor: 'rgba(255, 209, 102, 0.5)',
                                    borderWidth: 1,
                                    borderDash: [5, 5],
                                    label: {
                                        content: 'Average',
                                        display: true,
                                        position: 'end',
                                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                        color: '#ffd166',
                                        font: {
                                            size: 10,
                                            family: "'Courier New', monospace"
                                        }
                                    }
                                }
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            },
            line: {
                type: 'line',
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 6,
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                },
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Rating (0-6 scale)',
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)',
                                lineWidth: 1
                            },
                            ticks: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.3,
                            fill: {
                                target: 'origin',
                                above: 'rgba(0, 180, 216, 0.1)'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            },
            radar: {
                type: 'radar',
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 6,
                            ticks: {
                                stepSize: 1,
                                backdropColor: 'transparent',
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                }
                            },
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)'
                            },
                            angleLines: {
                                color: 'rgba(73, 80, 87, 0.2)'
                            },
                            pointLabels: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            },
            polarArea: {
                type: 'polarArea',
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 6,
                            ticks: {
                                stepSize: 1,
                                backdropColor: 'transparent',
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Courier New', monospace"
                                }
                            },
                            grid: {
                                color: 'rgba(73, 80, 87, 0.2)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'rgba(173, 181, 189, 0.8)',
                                font: {
                                    size: 11,
                                    family: "'Inter', sans-serif"
                                },
                                boxWidth: 15,
                                padding: 15
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        };
        
        // Function to create or update chart
        function updateChart(type) {
            const ctx = document.getElementById('evaluationChart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (myChart) {
                myChart.destroy();
            }
            
            // Create new chart
            myChart = new Chart(ctx, {
                type: chartConfigs[type].type,
                data: chartData,
                options: chartConfigs[type].options
            });
            
            // Update active button
            document.querySelectorAll('.chart-control-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.chart-control-btn[data-chart-type="${type}"]`).classList.add('active');
            
            currentChartType = type;
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Create initial chart
            updateChart('line');
            
            // Add event listeners to chart control buttons
            document.querySelectorAll('.chart-control-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const chartType = this.getAttribute('data-chart-type');
                    updateChart(chartType);
                });
            });
            
            // Calendar toggle
            document.getElementById('calendarToggle').addEventListener('click', function() {
                document.getElementById('calendarContainer').style.display = 'block';
                
                // Initialize calendar if not already done
                if (!window.calendar) {
                    const calendarEl = document.getElementById('calendar');
                    window.calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        themeSystem: 'bootstrap',
                        events: [] // You can load events here if needed
                    });
                    window.calendar.render();
                }
            });
            
            // Close calendar
            document.getElementById('closeCalendar').addEventListener('click', function() {
                document.getElementById('calendarContainer').style.display = 'none';
            });
            
            // Export data functionality
            document.getElementById('exportData').addEventListener('click', function() {
                // Show loading modal
                $('#loadingModal').modal('show');
                
                // Simulate processing delay
                setTimeout(function() {
                    // Create CSV content
                    let csvContent = "data:text/csv;charset=utf-8,";
                    csvContent += "Category,Rating,Percentile\n";
                    csvContent += `Quality of Work,${<?php echo htmlspecialchars(number_format($evaluation['avg_quality'], 2)); ?>},${<?php echo htmlspecialchars(number_format(($evaluation['avg_quality'] / 6) * 100, 1)); ?>}\n`;
                    csvContent += `Communication Skills,${<?php echo htmlspecialchars(number_format($evaluation['avg_communication_skills'], 2)); ?>},${<?php echo htmlspecialchars(number_format(($evaluation['avg_communication_skills'] / 6) * 100, 1)); ?>}\n`;
                    csvContent += `Teamwork,${<?php echo htmlspecialchars(number_format($evaluation['avg_teamwork'], 2)); ?>},${<?php echo htmlspecialchars(number_format(($evaluation['avg_teamwork'] / 6) * 100, 1)); ?>}\n`;
                    csvContent += `Punctuality,${<?php echo htmlspecialchars(number_format($evaluation['avg_punctuality'], 2)); ?>},${<?php echo htmlspecialchars(number_format(($evaluation['avg_punctuality'] / 6) * 100, 1)); ?>}\n`;
                    csvContent += `Initiative,${<?php echo htmlspecialchars(number_format($evaluation['avg_initiative'], 2)); ?>},${<?php echo htmlspecialchars(number_format(($evaluation['avg_initiative'] / 6) * 100, 1)); ?>}\n`;
                    csvContent += `Overall Average,${<?php echo htmlspecialchars(number_format($overallAverage, 2)); ?>},${<?php echo htmlspecialchars(number_format(($overallAverage / 6) * 100, 1)); ?>}\n`;
                    
                    // Create download link
                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "evaluation_data_<?php echo date('Y-m-d'); ?>.csv");
                    document.body.appendChild(link);
                    
                    // Trigger download
                    link.click();
                    
                    // Hide loading modal
                    $('#loadingModal').modal('hide');
                }, 1000);
            });
        });
    </script>
</body>
</html>