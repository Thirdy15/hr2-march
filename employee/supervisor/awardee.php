<?php
    // Start session and check admin login
session_start();
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

    // Include database connection
    include '../../db/db_conn.php';

    // Fetch user info
    $employeeId = $_SESSION['employee_id'];
    $sql = "SELECT employee_id, first_name, middle_name, last_name, birthdate, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $employeeInfo = $result->fetch_assoc();

    function calculateProgressCircle($averageScore) {
        return ($averageScore / 10) * 100;
    }

    function getTopEmployeesByCriterion($conn, $criterion, $criterionLabel, $index) {
        // SQL query to fetch the highest average score for each employee
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.department, e.pfp, e.email, 
                    AVG(ae.$criterion) AS avg_score,
                    AVG(ae.quality) AS avg_quality,
                    AVG(ae.communication_skills) AS avg_communication,
                    AVG(ae.teamwork) AS avg_teamwork,
                    AVG(ae.punctuality) AS avg_punctuality,
                    AVG(ae.initiative) AS avg_initiative
                FROM employee_register e
                JOIN evaluations ae ON e.employee_id = ae.employee_id
                GROUP BY e.employee_id
                ORDER BY avg_score DESC
                LIMIT 1";  // Select the top employee with the highest average score

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        // Path to the default profile picture
        $defaultPfpPath = '../../img/defaultpfp.jpg'; // Update this path to your actual default profile picture location
        $defaultPfp = base64_encode(file_get_contents($defaultPfpPath));

        // Output the awardee's information for each criterion
        echo "<div class='category' id='category-$index' style='display: none;'>";
        echo "<h3 class='text-center mt-4'>$criterionLabel</h3>";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if profile picture exists, else use the default picture
                if (file_exists($row['pfp']) && !empty($row['pfp'])) {
                    $pfp = base64_encode(file_get_contents($row['pfp']));
                } else {
                    $pfp = $defaultPfp;
                }

                // Calculate percentage for the progress circle
                $scorePercentage = calculateProgressCircle($row['avg_score']);
                
                echo "<div class='employee-card'>";
                echo "<div class='metrics-container'>";
                
                // Left metrics
                echo "<div class='metrics-column'>";
                echo "<div class='metric-box fade-in'>";
                echo "<span class='metric-label'>Quality of Work</span>";
                echo "<span class='metric-value'>" . round($row['avg_quality'], 2) . "</span>";
                echo "</div>";
                
                echo "<div class='metric-box fade-in' style='animation-delay: 0.2s;'>";
                echo "<span class='metric-label'>Communication Skills</span>";
                echo "<span class='metric-value'>" . round($row['avg_communication'], 2) . "</span>";
                echo "</div>";
                echo "</div>";

                // Center profile section
                echo "<div class='profile-section'>";
                echo "<div class='progress-circle-container'>";
                echo "<div class='progress-circle' data-progress='" . $scorePercentage . "'>";
                echo "<div class='profile-image-container'>";
                if (!empty($pfp)) {
                    echo "<img src='data:image/jpeg;base64,$pfp' alt='Profile Picture' class='profile-image'>";
                }
                echo "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='profile-info'>";
                echo "<h2 class='employee-name'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</h2>";
                echo "<p class='department-name'>" . htmlspecialchars($row['department']) . "</p>";
                echo "</div>";
                echo "<div class='employee-id fade-in' style='animation-delay: 0.8s;'>";
                echo "Employee ID: " . htmlspecialchars($row['employee_id']);
                echo "</div>";

                // New metric box below employee ID
                echo "<div class='metric-box fade-in' style='animation-delay: 0.8s;'>";
                echo "<span class='metric-label'>Initiative</span>";
                echo "<span class='metric-value'>" . round($row['avg_initiative'], 2) . "</span>";
                echo "</div>";

                // Add buttons for comments and reactions
                echo "<div class='comment-reaction-buttons'>";
                echo "  <div class='reactions text-start mt-4'>";
                echo "      <div class='reaction-button'>";
                echo "          <button class='btn btn-outline-primary' title='Like' onmouseover=\"showPopup('👍', 'like-popup')\" onmouseout=\"hidePopup('like-popup')\" onclick=\"react('like')\">";
                echo "              👍 <span id='like-count'>2</span>";
                echo "          </button>";
                echo "          <span class='popup-emoji' id='like-popup'>👍</span>";
                echo "      </div>";
                echo "      <div class='reaction-button'>";
                echo "          <button class='btn btn-outline-primary' title='Love' onmouseover=\"showPopup('❤️', 'love-popup')\" onmouseout=\"hidePopup('love-popup')\" onclick=\"react('love')\">";
                echo "              ❤️ <span id='love-count'>3</span>";
                echo "          </button>";
                echo "          <span class='popup-emoji' id='love-popup'>❤️</span>";
                echo "      </div>";
                echo "      <div class='reaction-button'>";
                echo "          <button class='btn btn-outline-primary' title='Wow' onmouseover=\"showPopup('😮', 'wow-popup')\" onmouseout=\"hidePopup('wow-popup')\" onclick=\"react('wow')\">";
                echo "              😮 <span id='wow-count'>1</span>";
                echo "          </button>";
                echo "          <span class='popup-emoji' id='wow-popup'>😮</span>";
                echo "      </div>";
                echo "      <div class='reaction-button'>";
                echo "          <button class='btn btn-outline-primary' title='Awesome' onmouseover=\"showPopup('😎', 'awesome-popup')\" onmouseout=\"hidePopup('awesome-popup')\" onclick=\"react('awesome')\">";
                echo "              😎 <span id='awesome-count'>2</span>";
                echo "          </button>";
                echo "          <span class='popup-emoji' id='awesome-popup'>😎</span>";
                echo "      </div>";
                echo "  </div>";
                echo "</div>";
                echo "<div class='text-start mt-2'>";
                echo "  <button class='btn btn-primary' onclick='openCommentModal()'>Write a Comment</button>";
                echo "</div>";

                echo "</div>"; // End profile-section

                // Right metrics
                echo "<div class='metrics-column'>";
                echo "<div class='metric-box fade-in' style='animation-delay: 0.4s;'>";
                echo "<span class='metric-label'>Teamwork</span>";
                echo "<span class='metric-value'>" . round($row['avg_teamwork'], 2) . "</span>";
                echo "</div>";
                
                echo "<div class='metric-box fade-in' style='animation-delay: 0.6s;'>";
                echo "<span class='metric-label'>Punctuality</span>";
                echo "<span class='metric-value'>" . round($row['avg_punctuality'], 2) . "</span>";
                echo "</div>";
                echo "</div>";

                echo "</div>"; // End metrics-container
                echo "</div>"; // End employee-card
            }
        } else {
            echo "<p class='text-center'>No outstanding employees found for $criterionLabel.</p>";
        }

        echo "</div>"; // End of category
        $stmt->close();
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Outstanding Employees</title>
        <link href="../../css/styles.css" rel="stylesheet" />
        <link href="../../css/awardee.css" rel="stylesheet"/>
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
                <main class="container-fluid position-relative bg-black">
                    <div class="container" id="calendarContainer" 
                        style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                        width: 700px; display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>   
                    <h1 class="mb-2 text-light ms-2">Outstanding Employees by Evaluation Criteria</h1>            
                    <div class="container text-light">
                        <!-- Top Employees for Different Criteria -->
                        <?php getTopEmployeesByCriterion($conn, 'quality', 'Quality of Work', 1); ?>
                        <?php getTopEmployeesByCriterion($conn, 'communication_skills', 'Communication Skills', 2); ?>
                        <?php getTopEmployeesByCriterion($conn, 'teamwork', 'Teamwork', 3); ?>
                        <?php getTopEmployeesByCriterion($conn, 'punctuality', 'Punctuality', 4); ?>
                        <?php getTopEmployeesByCriterion($conn, 'initiative', 'Initiative', 5); ?>

                        <!-- Navigation buttons for manually controlling the categories -->
                        <div class="text-center">
                            <button class="btn btn-primary" onclick="showPreviousCategory()">Previous</button>
                            <button class="btn btn-primary" onclick="showNextCategory()">Next</button>
                        </div>
                    </div>
                </main>
                    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header border-bottom border-warning">
                                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to log out?
                                </div>
                                <div class="modal-footer border-top border-warning">
                                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                    <form action="../../employee/logout.php" method="POST">
                                        <button type="submit" class="btn btn-danger">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>  
                <footer class="py-4 bg-dark text-light mt-auto border-top border-warning">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Your Website 2024</div>
                            <div>
                                <a href="#">Privacy Policy</a>
                                &middot;
                                <a href="#">Terms & Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <script>

            let currentCategoryIndex = 1;
            const totalCategories = 5; // Total number of categories

            function showNextCategory() {
                // Hide the current category
                document.getElementById(`category-${currentCategoryIndex}`).style.display = 'none';

                // Update to the next category index, loop back to 1 if at the end
                currentCategoryIndex = (currentCategoryIndex % totalCategories) + 1;

                // Show the next category
                document.getElementById(`category-${currentCategoryIndex}`).style.display = 'block';
            }

            function showPreviousCategory() {
                // Hide the current category
                document.getElementById(`category-${currentCategoryIndex}`).style.display = 'none';

                // Update to the previous category index, loop back to totalCategories if at the start
                currentCategoryIndex = (currentCategoryIndex - 1) || totalCategories;

                // Show the previous category
                document.getElementById(`category-${currentCategoryIndex}`).style.display = 'block';
            }

            // Start the slideshow, show the first category immediately
            window.onload = function() {
                // Show the first category immediately
                document.getElementById(`category-1`).style.display = 'block';
                
                // Start the slideshow after showing the first category
                setInterval(showNextCategory, 8000); // Change every 8 seconds
            };

            document.addEventListener('DOMContentLoaded', function() {
                // Initialize progress circles
                const circles = document.querySelectorAll('.progress-circle');
                circles.forEach(circle => {
                    const progress = circle.getAttribute('data-progress');
                    const circumference = 2 * Math.PI * 90; // for r=90
                    const strokeDashoffset = circumference - (progress / 100) * circumference;
                    
                    // Create SVG circle
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('class', 'progress-ring');
                    svg.setAttribute('width', '200');
                    svg.setAttribute('height', '200');
                    
                    const circleElement = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circleElement.setAttribute('class', 'progress-ring__circle');
                    circleElement.setAttribute('stroke', '#22d3ee');
                    circleElement.setAttribute('stroke-width', '4');
                    circleElement.setAttribute('fill', 'transparent');
                    circleElement.setAttribute('r', '90');
                    circleElement.setAttribute('cx', '100');
                    circleElement.setAttribute('cy', '100');
                    circleElement.style.strokeDasharray = `${circumference} ${circumference}`;
                    circleElement.style.strokeDashoffset = strokeDashoffset;
                    
                    svg.appendChild(circleElement);
                    circle.insertBefore(svg, circle.firstChild);
                });
            });

            function showReactions(button) {
                const menu = button.nextElementSibling;
                menu.classList.toggle('show');
            }

            function selectReaction(reaction) {
                const reactionModal = document.getElementById('reactionModal');
                reactionModal.querySelector('.modal-body').textContent = "You reacted with: " + reaction;
                reactionModal.style.display = 'block';
                document.getElementById("reaction-menu").classList.remove("show");
            }

            // Close the reaction menu when clicking outside
            window.onclick = function(event) {
                if (!event.target.matches('.react-btn')) {
                    var dropdowns = document.getElementsByClassName("reaction-dropdown");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            }

            function postComment(event, input) {
                if (event.key === 'Enter' && input.value.trim() !== '') {
                    let commentList = document.querySelector('.modal-body .comment-list');
                    let newComment = document.createElement('p');
                    newComment.textContent = input.value;
                    newComment.classList.add('comment');
                    commentList.appendChild(newComment);

                    input.value = '';

                    // Optional: Send comment to the backend using AJAX
                    // fetch('save_comment.php', {
                    //     method: 'POST',
                    //     body: JSON.stringify({ comment: input.value }),
                    //     headers: { 'Content-Type': 'application/json' }
                    // });
                }
            }

            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            function openCommentModal() {
                document.getElementById('commentModal').style.display = 'block';
            }

            function showPopup(emoji, popupId) {
                document.getElementById(popupId).style.opacity = '1';
            }

            function hidePopup(popupId) {
                document.getElementById(popupId).style.opacity = '0';
            }

            // Ensure the modal is initialized correctly
            var logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
                keyboard: false
            });
        </script>

        <!-- Comment Modal -->
        <div id="commentModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('commentModal')">&times;</span>
                <input type="text" class="comment-input" placeholder="Write your comment..." onkeypress="postComment(event, this)">
                <div class="modal-body">
                    <div class="comment-list"></div>
                </div>
            </div>
        </div>

        <!-- Reaction Modal -->
        <div id="reactionModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('reactionModal')">&times;</span>
                <div class="modal-body"></div>
            </div>
        </div>

        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="../../js/employee.js"></script>
    </body>
    </html>

    <?php
    // Close the database connection
    $conn->close();
    ?>