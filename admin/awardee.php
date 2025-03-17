<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['a_id'])) {
    header("Location: ../../admin/login.php");
    exit();
}

// Include database connection
include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

function calculateProgressCircle($averageScore) {
    return ($averageScore / 10) * 100;
}

function getTopEmployeesByCriterion($conn) {
    // SQL query to fetch the highest average score for each employee across all criteria
    $sql = "SELECT e.employee_id, e.firstname, e.lastname, e.department, e.pfp, e.email, 
                   AVG(ae.quality) AS avg_quality,
                   AVG(ae.communication_skills) AS avg_communication,
                   AVG(ae.teamwork) AS avg_teamwork,
                   AVG(ae.punctuality) AS avg_punctuality,
                   AVG(ae.initiative) AS avg_initiative
            FROM employee_register e
            JOIN evaluations ae ON e.employee_id = ae.employee_id
            GROUP BY e.employee_id
            ORDER BY (AVG(ae.quality) + AVG(ae.communication_skills) + AVG(ae.teamwork) + AVG(ae.punctuality) + AVG(ae.initiative)) DESC
            LIMIT 5"; // Fetch top 5 employees

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    // Path to the default profile picture
    $defaultPfpPath = '../img/defaultpfp.jpg';
    $defaultPfp = base64_encode(file_get_contents($defaultPfpPath));

    return $result;
}

// Fetch top employees with all criteria scores
$topEmployees = getTopEmployeesByCriterion($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard Slideshow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for star icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .carousel-item {
            text-align: center;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
        }
        .criteria-scores {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }
        .criteria-scores div {
            text-align: center;
        }
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .star-rating {
            color: #ffc107; /* Gold color for stars */
            font-size: 1.2rem;
        }
        .score {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Top Performers - All Categories</h2>
        <div class="row">
            <div class="col-md-8 mx-auto">
                <!-- Bootstrap Carousel -->
                <div id="leaderboardCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        if ($topEmployees->num_rows > 0) {
                            $rank = 1;
                            $isFirst = true;
                            while ($row = $topEmployees->fetch_assoc()) {
                                $activeClass = $isFirst ? "active" : "";
                                // Use the employee's profile picture if available, otherwise use the default
                                $profilePicture = !empty($row['pfp']) ? $row['pfp'] : 'data:image/jpeg;base64,' . base64_encode(file_get_contents('../img/defaultpfp.jpg'));
                                echo "<div class='carousel-item {$activeClass}'>
                                        <h3>Rank #{$rank}</h3>
                                        <img src='{$profilePicture}' alt='Profile Picture' class='profile-picture'>
                                        <h4>{$row['firstname']} {$row['lastname']}</h4>
                                        <p class='text-muted'>{$row['department']}</p>
                                        <div class='criteria-scores'>
                                            <div>
                                                <h5>Quality</h5>
                                                <div class='star-rating'>" . getStarRating($row['avg_quality']) . "</div>
                                                <div class='score'>" . number_format($row['avg_quality'], 2) . "</div>
                                            </div>
                                            <div>
                                                <h5>Communication</h5>
                                                <div class='star-rating'>" . getStarRating($row['avg_communication']) . "</div>
                                                <div class='score'>" . number_format($row['avg_communication'], 2) . "</div>
                                            </div>
                                            <div>
                                                <h5>Teamwork</h5>
                                                <div class='star-rating'>" . getStarRating($row['avg_teamwork']) . "</div>
                                                <div class='score'>" . number_format($row['avg_teamwork'], 2) . "</div>
                                            </div>
                                            <div>
                                                <h5>Punctuality</h5>
                                                <div class='star-rating'>" . getStarRating($row['avg_punctuality']) . "</div>
                                                <div class='score'>" . number_format($row['avg_punctuality'], 2) . "</div>
                                            </div>
                                            <div>
                                                <h5>Initiative</h5>
                                                <div class='star-rating'>" . getStarRating($row['avg_initiative']) . "</div>
                                                <div class='score'>" . number_format($row['avg_initiative'], 2) . "</div>
                                            </div>
                                        </div>
                                      </div>";
                                $rank++;
                                $isFirst = false;
                            }
                        } else {
                            echo "<div class='carousel-item active'>
                                    <h3>No Data Found</h3>
                                  </div>";
                        }
                        ?>
                    </div>
                    <!-- Carousel Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#leaderboardCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#leaderboardCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Function to generate star rating based on score (max 6 stars)
function getStarRating($score) {
    $maxStars = 6;
    $fullStars = floor($score); // Number of full stars
    $halfStar = ($score - $fullStars) >= 0.5 ? 1 : 0; // Check for half star
    $emptyStars = $maxStars - $fullStars - $halfStar; // Remaining empty stars

    $stars = '';
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }
    // Half star
    if ($halfStar) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
    }
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }

    return $stars;
}

// Close the database connection
$conn->close();
?>