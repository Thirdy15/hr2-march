<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Tracker Calendar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="../css/styles.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #283593, #1e1e2f);
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        #calendar {
            max-width: 1100px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .fc-event {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fc-event:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
        }
        .fc-button {
            background-color: #6200ea !important;
            border-color: #6200ea !important;
            color: #fff !important;
            transition: background-color 0.3s ease;
        }
        .fc-button:hover {
            background-color: #3700b3 !important;
            border-color: #3700b3 !important;
        }
        .fc-daygrid-day-number {
            color: #fff;
        }
        .fc-col-header-cell {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .fc-daygrid-day {
            background: rgba(255, 255, 255, 0.05);
        }
        .fc-daygrid-day:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .fc-day-today {
            background: rgba(255, 193, 7, 0.1) !important;
        }
        .loading-spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center my-4">
                <h1 class="display-4">Leave Tracker Calendar</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div id="calendar"></div>
                <div class="loading-spinner" id="loading-spinner"></div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="customModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="customModalLabel">Leave Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Employee:</strong> <span id="modalEmployeeName"></span></p>
                    <p><strong>Leave Type:</strong> <span id="modalLeaveType"></span></p>
                    <p><strong>Employee ID:</strong> <span id="modalEmployeeId"></span></p>
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
                themeSystem: 'bootstrap',
                eventBackgroundColor: '#6200ea',
                eventBorderColor: '#6200ea',
                eventTextColor: '#fff',
                eventDisplay: 'block',
                dayMaxEvents: true, // Allow "more" link when too many events
            });

            calendar.render();
        });
    </script>
</body>
</html>
