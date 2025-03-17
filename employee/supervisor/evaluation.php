<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../employee/employeelogin.php");
    exit();
}

// Include the database connection  
include '../../db/db_conn.php'; 

$role = $_SESSION['role']; // Ensure this is set during login (e.g., supervisor, staff, admin, fieldworker, contractual)
$department = $_SESSION['department']; // Ensure this is set during login

// Fetch user info from the employee_register table
$employeeId = $_SESSION['employee_id'];
$sql = "SELECT employee_id, first_name, middle_name, last_name, birthdate, gender, email, role, position, department, phone_number, address, pfp 
        FROM employee_register 
        WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

if (!$employeeInfo) {
    die("Error: Employee information not found.");
}

// Define the position
$position = 'employee'; // Position is now 'employee' only

// Fetch employee records where position is 'employee' and department matches the logged-in employee's department
$sql = "SELECT employee_id, first_name, last_name, role, position 
        FROM employee_register 
        WHERE position = ? AND department = ? AND role IN ('supervisor', 'staff', 'admin', 'fieldworker', 'contractual')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $position, $department);  // Bind the parameters for position and department
$stmt->execute();
$result = $stmt->get_result();

// Fetch evaluations for this employee from the evaluations table
$evaluatedEmployees = [];
$evalSql = "SELECT employee_id FROM ptp_evaluations WHERE evaluator_id = ?"; // Evaluator ID is the logged-in employee
$evalStmt = $conn->prepare($evalSql);
$evalStmt->bind_param('s', $employeeId);
$evalStmt->execute();
$evalResult = $evalStmt->get_result();
if ($evalResult->num_rows > 0) {
    while ($row = $evalResult->fetch_assoc()) {
        $evaluatedEmployees[] = $row['employee_id']; // Store employee IDs who were evaluated
    }
}


// Fetch evaluation questions from the database for each category and role
$categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
$questions = [];

foreach ($categories as $category) {
    // Fetch questions for the specific category and role
    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ? AND role = ?";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->bind_param('ss', $category, $role); // $role is the role being evaluated
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    $questions[$category] = [];

    if ($categoryResult->num_rows > 0) {
        while ($row = $categoryResult->fetch_assoc()) {
            $questions[$category][] = $row['question'];
        }
    }
}

// Check if any records are found
$employees = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Exclude the logged-in employee from the list
        if ($row['employee_id'] != $employeeId) {
            $employees[] = $row;
        }
    }
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="../../css/star.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Evaluation</h1>
                </div>
                <div class="container" id="calendarContainer" style="position: fixed; top: 9%; right: 0; z-index: 1050; width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                <div class="container-fluid px-4">
                    <div class="col-md-12">
                        <h2 class="text-center text-light mb-4"><?php echo $department; ?></h2>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-hover text-light">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Position</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($employees)): ?>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['role']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                                <td>
                                                    <button class="btn btn-success" 
                                                        onclick="evaluateEmployee(<?php echo $employee['employee_id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', '<?php echo htmlspecialchars($employee['role']); ?>')"
                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                        <?php echo in_array($employee['employee_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No employees found for evaluation in <?php echo $department; ?>.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main> 
                <div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="employeeDetails"></h5>
                                <button type="button" class="close btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="employee_id" value="<?php echo $_SESSION['employee_id']; ?>">
                                <div id="questions"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="submitEvaluation()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../../employee/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Code -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
// Declare evaluatedEmployees and initialize it with PHP data
let evaluatedEmployees = <?php echo json_encode($evaluatedEmployees); ?>;

// Function to fetch questions based on the evaluated employee's role
async function fetchQuestions(role) {
    const response = await fetch(`../../employee_db/supervisor/fetchQuestions.php?role=${role}`);
    return await response.json();
}

async function evaluateEmployee(employee_id, employeeName, employeeRole) {
    currentEmployeeId = employee_id;
    currentEmployeeName = employeeName;
    currentEmployeeRole = employeeRole;

    // Fetch questions based on the evaluated employee's role
    const questions = await fetchQuestions(employeeRole);

    // Display employee details in the modal
    const employeeDetails = `<strong>Name: ${employeeName} <br> Role: ${employeeRole}</strong>`;
    document.getElementById('employeeDetails').innerHTML = employeeDetails;

    // Clear previous questions
    const questionsDiv = document.getElementById('questions');
    questionsDiv.innerHTML = '';

    // Start the table structure
    let tableHtml = `
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category</th>
                <th>Question</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>`;

    // Loop through categories and questions to add them into the table
    for (const [category, categoryQuestions] of Object.entries(questions)) {
        categoryQuestions.forEach((question, index) => {
            const questionName = `${category.replace(/\s/g, '')}q${index}`; // Unique name per question
            tableHtml += `
            <tr>
                <td>${index === 0 ? category : ''}</td>
                <td>${question}</td>
                <td>
                    <div class="star-rating">
                        ${[6, 5, 4, 3, 2, 1].map(value => `
                            <input type="radio" name="${questionName}" value="${value}" id="${questionName}star${value}" required>
                            <label for="${questionName}star${value}">&#9733;</label>
                        `).join('')}
                    </div>
                </td>
            </tr>`;
        });
    }

    // Close the table structure
    tableHtml += `
        </tbody>
    </table>`;

    questionsDiv.innerHTML = tableHtml;

    // Show the modal
    $('#evaluationModal').modal('show');
}

// Define calculateAverage function
function calculateAverage(category, evaluations) {
    // Filter evaluations for the current category
    const categoryEvaluations = evaluations.filter(evaluation => {
        // Ensure the question name matches the category
        return evaluation.question.toLowerCase().includes(category.toLowerCase());
    });

    if (categoryEvaluations.length === 0) {
        console.warn(`No evaluations found for category: ${category}`);
        return 0; // No evaluations for this category
    }

    // Calculate the average rating
    const total = categoryEvaluations.reduce((sum, evaluation) => sum + parseFloat(evaluation.rating), 0);
    const average = total / categoryEvaluations.length;
    console.log(`Category: ${category}, Average: ${average}`); // Debugging output
    return average;
}

// Your existing submitEvaluation function
function submitEvaluation() {
    const evaluations = [];
    const questionsDiv = document.getElementById('questions');

    // Check if all questions are answered
    const unansweredQuestions = questionsDiv.querySelectorAll('.star-rating').length - questionsDiv.querySelectorAll('input[type="radio"]:checked').length;
    if (unansweredQuestions > 0) {
        alert('Please complete the evaluation before submitting.');
        return;
    }

    // Collect all ratings
    questionsDiv.querySelectorAll('input[type="radio"]:checked').forEach(input => {
        evaluations.push({
            question: input.name, // Question identifier
            rating: input.value  // Rating value
        });
    });

    console.log('Evaluations:', evaluations); // Debugging: Log the evaluations array

    // Calculate category averages
    const categoryAverages = {
        QualityOfWork: calculateAverage('QualityOfWork', evaluations),
        CommunicationSkills: calculateAverage('CommunicationSkills', evaluations),
        Teamwork: calculateAverage('Teamwork', evaluations),
        Punctuality: calculateAverage('Punctuality', evaluations),
        Initiative: calculateAverage('Initiative', evaluations)
    };

    console.log('Category Averages:', categoryAverages);

    // Get the logged-in employee ID and department
    const employeeId = document.getElementById('employee_id').value;
    const department = '<?php echo $department; ?>'; // Use the department from PHP

    // Submit the evaluation via AJAX
    $.ajax({
        type: 'POST',
        url: '../../employee_db/supervisor/submit_evaluation.php',
        data: {
            employee_id: currentEmployeeId,
            employeeName: currentEmployeeName,
            employeeRole: currentEmployeeRole,
            categoryAverages: categoryAverages,
            employeeId: employeeId,
            department: department
        },
        success: function (response) {
            console.log(response);
            if (response === 'You have already evaluated this employee.') {
                alert(response);
            } else {
                // Add the evaluated employee's ID to the evaluatedEmployees array
                evaluatedEmployees.push(currentEmployeeId);

                // Disable the button for this employee on the page
                const evaluateButton = document.querySelector(`button[onclick="evaluateEmployee(${currentEmployeeId}, '${currentEmployeeName}', '${currentEmployeeRole}')"]`);
                if (evaluateButton) {
                    evaluateButton.disabled = true;
                    evaluateButton.innerHTML = 'Evaluated'; // Change the button text to 'Evaluated'
                }

                // Hide the modal after submission
                $('#evaluationModal').modal('hide');
            }
        },
        error: function (err) {
            console.error(err);
            alert('An error occurred while submitting the evaluation.');
        }
    });
}



</script>

</body>
</html>
