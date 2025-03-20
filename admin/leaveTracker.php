<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Tracker Calendar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
     
    <style>
        :root {
            --dark-bg: #1a1a1a;
            --darker-bg: #121212;
            --card-bg: #2a2a2a;
            --accent-color: #757575;
            --accent-hover: #616161;
            --text-primary: #f5f5f5;
            --text-secondary: #bdbdbd;
            --border-color: rgba(158, 158, 158, 0.2);
            --highlight-color: rgba(158, 158, 158, 0.1);
        }

        body {
            background: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }

        .page-header {
            padding: 30px 0;
            position: relative;
            overflow: hidden;
            background: var(--darker-bg);
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-weight: 700;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
            text-transform: uppercase;
            font-size: 2rem;
        }

        .page-header h1 span {
            color: #9e9e9e;
            font-weight: 300;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(66, 66, 66, 0.1), rgba(97, 97, 97, 0.1));
            z-index: 0;
        }

        #calendar {
            max-width: 1100px;
            margin: 20px auto 40px;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
        }

        .fc-theme-standard .fc-scrollgrid {
            border-color: var(--border-color);
        }

        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--border-color);
        }

        .fc-event {
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 3px 6px;
            border: none !important;
            background: linear-gradient(135deg, #757575, #616161) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .fc-event-title {
            color: white; /* Change the text color to white */
        }

        .fc-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .fc-button {
            background-color: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            color: #fff !important;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .fc-button:hover {
            background-color: var(--accent-hover) !important;
            border-color: var(--accent-hover) !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-1px);
        }

        .fc-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(158, 158, 158, 0.25) !important;
        }

        .fc-daygrid-day-number {
            color: var(--text-primary);
            font-weight: 500;
            padding: 8px !important;
        }

        .fc-col-header-cell {
            background: var(--highlight-color);
            color: var(--text-primary);
            padding: 12px 0 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .fc-col-header-cell-cushion {
            font-weight: 600;
            padding: 10px 4px !important;
            color: #9e9e9e;
        }

        .fc-daygrid-day {
            background: rgba(42, 42, 42, 0.5);
            transition: background 0.2s ease;
        }

        .fc-daygrid-day:hover {
            background: rgba(66, 66, 66, 0.8);
        }

        .fc-day-today {
            background: rgba(158, 158, 158, 0.15) !important;
        }

        .loading-spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            z-index: 10;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid rgba(158, 158, 158, 0.2);
            border-radius: 50%;
            border-top-color: #9e9e9e;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal Styling */
        .modal-content {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 24px;
            background: var(--darker-bg);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 16px 24px;
            background: var(--darker-bg);
        }

        .modal-title {
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1.1rem;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body p {
            margin-bottom: 16px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }

        .modal-body p:last-child {
            margin-bottom: 0;
        }

        .modal-body p strong {
            color: #9e9e9e;
            margin-right: 10px;
            min-width: 100px;
            display: inline-block;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .modal-body p span {
            color: var(--text-primary);
            font-weight: 500;
            background: rgba(158, 158, 158, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
            flex: 1;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .btn-secondary {
            background-color: #757575;
            border-color: #757575;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            padding: 8px 20px;
        }

        .btn-secondary:hover {
            background-color: #616161;
            border-color: #616161;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }

            .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                margin-bottom: 10px;
            }

            .fc-daygrid-day-number {
                padding: 4px !important;
            }

            #calendar {
                padding: 10px;
                margin: 10px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0 page-header">
            <div class="col-12 text-center">
                <h1>Leave Tracker Calendar</h1>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-12">
                <div id="calendar"></div>
                <div class="loading-spinner" id="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="customModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customModalLabel">Leave Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Employee</strong> <span id="modalEmployeeName"></span></p>
                    <p><strong>Leave Type</strong> <span id="modalLeaveType"></span></p>
                    <p><strong>Employee ID</strong> <span id="modalEmployeeId"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const loadingSpinner = document.getElementById('loading-spinner');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: function (info, successCallback, failureCallback) {
                    loadingSpinner.style.display = 'block';
                    fetch('../db/ongoingLeave.php')
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    throw new Error(`Server returned: ${text}`);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            successCallback(data);  // Pass the data to FullCalendar
                        })
                        .catch(error => {
                            console.error('Error fetching events:', error);
                            failureCallback(error);  // Handle errors in fetching the events
                        })
                        .finally(() => {
                            loadingSpinner.style.display = 'none';
                        });
                },
                eventClick: function (info) {
                    const employeeName = info.event.title;
                    const leaveType = info.event.extendedProps.leave_type;
                    const eId = info.event.extendedProps.employee_id;

                    // Get the modal elements
                    const modalEmployeeName = document.getElementById("modalEmployeeName");
                    const modalLeaveType = document.getElementById("modalLeaveType");
                    const modalEmployeeId = document.getElementById("modalEmployeeId");

                    // Set the content of the modal
                    modalEmployeeName.textContent = employeeName;
                    modalLeaveType.textContent = leaveType;
                    modalEmployeeId.textContent = eId;

                    // Show the modal using Bootstrap's JavaScript API
                    const modal = new bootstrap.Modal(document.getElementById('customModal'));
                    modal.show();
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                themeSystem: 'standard',
                eventBackgroundColor: '#757575',
                eventBorderColor: '#616161',
                eventTextColor: '#fff',
                eventDisplay: 'block',
                dayMaxEvents: true, // Allow "more" link when too many events
            });

            calendar.render();
        });
    </script>
</body>
</html>
