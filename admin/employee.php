<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Fetch employee data
$sql = "SELECT employee_id, first_name, last_name, face_image, gender, email, department, role, phone_number, address FROM employee_register WHERE position='Employee'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Employees' Account Management</h1>
                    <div class="container-fluid" id="calendarContainer" 
                        style="position: fixed; top: 7%; right: 40; z-index: 1050; 
                        max-width: 100%; display: none;">
                        <div class="row">
                            <div class="col-md-9 mx-auto">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>             
                    <div class=""></div>
                    <div class="card mb-4 bg-dark text-light">
                        <div class="card-header border-bottom border-1 border-secondary d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-table me-1"></i>
                                Employee Accounts
                            </span>
                            <a class="btn btn-primary text-light" href="../admin/create_employee.php">Create Employee</a>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table text-light text-center">
                                <thead class="thead-light">
                                    <tr class="text-center text-light">
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Phone Number</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr class="text-center text-light align-items-center">
                                                <td><?php echo htmlspecialchars(trim($row['employee_id'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['gender'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['email'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['department'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['role'] ?? 'N/A')); ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['phone_number'] ?? 'N/A')) ?: 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars(trim($row['address'] ?? 'N/A')) ?: 'N/A'; ?></td>
                                                <td class='d-flex justify-content-around align-items-center'>
                                                    <button class="btn btn-danger btn-sm me-2 gap-2 mb-2" onclick="deleteEmployee(<?php echo $row['employee_id']; ?>)">Delete</button>
                                                    <button class="btn btn-success btn-sm mb-2" 
                                                        onclick="fillUpdateForm(<?php echo $row['employee_id']; ?>, '<?php echo htmlspecialchars($row['first_name']); ?>', '<?php echo htmlspecialchars($row['last_name']); ?>', '<?php echo htmlspecialchars($row['email']); ?>',
                                                        '<?php echo htmlspecialchars($row['department']); ?>', '<?php echo htmlspecialchars($row['role']); ?>', '<?php echo htmlspecialchars($row['phone_number']); ?>', '<?php echo htmlspecialchars($row['address']); ?>')">Update
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="10" class="text-center">No records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
                <div class="modal fade" id="updateEmployeeModal" tabindex="-1" aria-labelledby="updateEmployeeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title text-center" id="updateEmployeeModalLabel">Update Employee Account</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="updateForm">
                                    <input type="hidden" name="employee_id" id="updateId">      
                                    <div class="">                     
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <input type="text" class="form-control fw-bold" name="first_name" required>
                                                <label class="text-dark fw-bold" for="firstname">First Name</label>
                                            </div>
                                            <div class="col-sm-6 bg-dark form-floating mb-3">                                 
                                                <input type="text" class="form-control fw-bold" name="last_name" required>
                                                <label class="text-dark fw-bold" for="lastname">Last Name</label>
                                            </div>
                                        </div>
                                    </div>  
                                    <div class="">
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <input type="email" class="form-control fw-bold" name="email" placeholder="Email" required>
                                                <label class="text-dark fw-bold" for="email">Email</label>
                                            </div> 
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <input type="text" class="form-control fw-bold" name="phone_number" pattern="^\d{11}$" maxlength="11" required>
                                                <label class="text-dark fw-bold" for="phone_number">Phone Number</label>
                                            </div>
                                        </div>
                                    </div>  
                                    <div class="">                          
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <select class="form-control fw-bold form-select" name="department" required>
                                                    <option value="" disabled selected>Select a Department</option>
                                                    <option value="Finance Department">Finance Department</option>
                                                    <option value="Administration Department">Administration Department</option>
                                                    <option value="Sales Department">Sales Department</option>
                                                    <option value="Credit Department">Credit Department</option>
                                                    <option value="Human Resource Department">Human Resource Department</option>
                                                    <option value="IT Department">IT Department</option>
                                                </select>
                                                <label class="text-dark fw-bold" for="department">Department</label>
                                            </div>
                                            <div class="col-sm-6 bg-dark form-floating mb-3">
                                                <select class="form-control fw-bold form-select" name="position" required>
                                                    <option value="" disabled selected>Select Role</option>
                                                    <option value="Contractual">Contractual</option>
                                                    <option value="Field Worker">Field Worker</option>
                                                    <option value="Staff">Staff</option>
                                                    <option value="Supervisor">Supervisor</option>
                                                </select>
                                                <label class="text-dark fw-bold" for="position">Role</label>
                                            </div>
                                        </div>   
                                    </div>  
                                    <div class="">  
                                        <div class="form-group mb-3 row">
                                            <div class="col-sm-12 bg-dark form-floating mb-3">
                                                <input type="text" class="form-control fw-bold" name="address" placeholder="Address" required>
                                                <label class="text-dark fw-bold" for="address">Address</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2" onclick="closeModal()">Close</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer border-top border-secondary">
                                <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                <form action="../admin/logout.php" method="POST">
                                    <button type="submit" class="btn btn-danger">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>  
            <?php include 'footer.php'; ?>
        </div>
    </div>
<script>
//UPDATE MODAL
let modalInstance;

function fillUpdateForm(id, firstname, lastname, email, department, position, phone_number, address) {
    document.getElementById('updateId').value = id;
    document.querySelector('input[name="first_name"]').value = firstname.trim() === '' ? 'N/A' : firstname;
    document.querySelector('input[name="last_name"]').value = lastname.trim() === '' ? 'N/A' : lastname;
    document.querySelector('input[name="email"]').value = email.trim() === '' ? 'N/A' : email;
    document.querySelector('select[name="department"]').value = department.trim() === '' ? 'N/A' : department;
    document.querySelector('select[name="position"]').value = position.trim() === '' ? 'N/A' : position;
    document.querySelector('input[name="phone_number"]').value = phone_number.trim() === '' ? 'N/A' : phone_number;
    document.querySelector('input[name="address"]').value = address.trim() === '' ? 'N/A' : address;

    modalInstance = new bootstrap.Modal(document.getElementById('updateEmployeeModal'));
    modalInstance.show();
}

function closeModal() {
    if (modalInstance) {
        modalInstance.hide();
    }
}

function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        const formData = new FormData();
        formData.append('employee_id', id);

        fetch('../db/delete_employee.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success || data.error);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the employee.');
        });
    }
}

document.getElementById('updateForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('../db/update_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.success || data.error);
        if (data.success) {
            closeModal();
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the employee.');
    });
};
//UPDATE MODAL END
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="../js/datatables-simple-demo.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
</body>
</html>