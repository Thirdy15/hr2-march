<?php
session_start();
include '../../db/db_conn.php'; // Include database connection

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

// Fetch all employees
$query = "SELECT * FROM employee_register";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Employee Schedule</h1>
    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Shift Type</th>
                <th>Schedule Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($employee = $result->fetch_assoc()): ?>
                    <?php
                    // Fetch the employee's schedule
                    $scheduleQuery = "SELECT * FROM employee_schedule WHERE employee_id = ? ORDER BY schedule_date DESC LIMIT 1";
                    $stmt = $conn->prepare($scheduleQuery);
                    $stmt->bind_param('i', $employee['employee_id']);
                    $stmt->execute();
                    $scheduleResult = $stmt->get_result();
                    $schedule = $scheduleResult->fetch_assoc();
                    $stmt->close();
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['shift_type'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($schedule['schedule_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($schedule['start_time'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($schedule['end_time'] ?? 'N/A'); ?></td>
                        <td>
                            <button class="edit-btn" onclick="openEditModal(<?php echo $employee['employee_id']; ?>)">Edit Schedule</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No employees found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
        <h2>Edit Schedule</h2>
        <form id="editForm" method="POST" action="../../employee/supervisor/updateSchedule.php">
            <input type="hidden" id="editEmployeeId" name="employee_id">
            <label for="editShiftType">Shift Type:</label>
            <select id="editShiftType" name="shift_type" required>
                <option value="day">Day Shift</option>
                <option value="night">Night Shift</option>
            </select><br><br>
            <label for="editScheduleDate">Schedule Date:</label>
            <input type="date" id="editScheduleDate" name="schedule_date" required><br><br>
            <label for="editStartTime">Start Time:</label>
            <input type="time" id="editStartTime" name="start_time" required><br><br>
            <label for="editEndTime">End Time:</label>
            <input type="time" id="editEndTime" name="end_time" required><br><br>
            <button type="submit">Save Changes</button>
            <button type="button" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>

    <script>
        // Function to open the edit modal and populate form fields
        function openEditModal(employeeId) {
            fetch(`/HR2/employee_db/supervisor/getSchedule.php?employee_id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editEmployeeId').value = employeeId;
                    document.getElementById('editShiftType').value = data.shift_type || 'day';
                    document.getElementById('editScheduleDate').value = data.schedule_date || '';
                    document.getElementById('editStartTime').value = data.start_time || '';
                    document.getElementById('editEndTime').value = data.end_time || '';
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => console.error('Error fetching schedule:', error));
        }

        // Function to close the edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>