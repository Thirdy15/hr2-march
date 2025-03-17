<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Employee Account Registration</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="/HR2/face-api.js-master/dist/face-api.min.js"></script>
<style>
    /* General Styles */
/* General Styles */
body {
    background-color: #0e0e0e;
    color: #ffffff;
    font-family: 'Arial', sans-serif;
    padding-top: 50px;
    height: 100%;
}

h3, h4 {
    font-family: 'Segoe UI', sans-serif;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.container-fluid {
    margin-bottom: 50px;
}

/* Card Styling */
.card {
    background-color: #1a1a1a;
    border: 1px solid #444;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.7);
}

.card-header {
    background: linear-gradient(135deg, #444, #333);
    border-bottom: 1px solid #555;
    color: #f1f1f1;
    font-size: 1.5rem;
    text-align: center;
}

.card-body {
    padding: 25px;
}

/* Form Styling */
.form-control {
    background-color: #2c2c2c;
    border: 1px solid #555;
    color: #ffffff;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 20px;
    font-size: 1rem;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.form-control:focus {
    background-color: #3d3d3d;
    border-color: #777;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}

.form-label {
    color: #ffffff;
    margin-bottom: 7px;
    font-size: 1.1rem;
}

/* Button Styling */
.btn {
    border-radius: 6px;
    padding: 12px 25px;
    font-weight: bold;
    font-size: 1.1rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
    text-align: center;
}

.btn-primary {
    background-color: #007bff;
    border: none;
}

.btn-primary:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

.btn-success {
    background-color: #28a745;
    border: none;
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

/* Video Feed Styling */
#videoFeed {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 2px solid #555;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

/* Captured Face Styling */
#capturedFace {
    width: 160px;
    height: 160px;
    border-radius: 8px;
    border: 2px solid #444;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

/* Loading Spinner */
.loading-spinner {
    display: none;
    text-align: center;
    margin-top: 20px;
}

.loading-spinner.active {
    display: block;
}

.loading-spinner .spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Footer Styling */
footer {
    background-color: #1a1a1a;
    padding: 20px;
    text-align: center;
    color: #ccc;
    font-size: 0.9rem;
    border-top: 1px solid #333;
}

footer a {
    color: #bbbbbb;
    transition: color 0.3s ease;
}

footer a:hover {
    color: #ffffff;
}

</style>
</head>

<body class="bg-black">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container-fluid">
                    <div class="row justify-content-around align-items-center">
                        <!-- Registration Form -->
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5 my-5">
                                <div class="card-header">
                                    <h3 class="text-center text-light font-weight-light my-4">Create Employee Account</h3>
                                    <div id="form-feedback" class="alert text-center" style="display: none;"></div>
                                </div>
                                <div class="card-body">
                                    <form id="registrationForm" action="registeremployee_db.php" method="POST" enctype="multipart/form-data">
                                        <!-- Email Input -->
                                        <div class="form-control mb-3">
                                            <label for="inputEmail" class="form-label">Email</label>
                                            <input type="email" id="inputEmail" name="email" class="form-control" required>
                                        </div>

                                        <!-- Face Image Upload -->
                                        <div class="form-control mb-3">
                                            <label for="face_image" class="form-label">Upload Face Image</label>
                                            <input type="file" id="face_image" name="photo[]" accept="image/*" multiple required>
                                        </div>

                                        <!-- Hidden face descriptor input -->
                                        <input type="hidden" id="faceDescriptorInput" name="face_descriptor">

                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-block" type="submit" id="submitBtn" disabled>Upload Photo</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center">
                                    <p class="small text-muted mt-1">Human Resource 2</p>
                                </div>
                            </div>
                        </div>

                        <!-- Face Registration Section -->
                        <div class="col-md-5">
                            <div class="card shadow-lg border-0 rounded-lg mb-4">
                                <div class="card-body">
                                    <h4 class="text-light">Register Face</h4>
                                    <div class="mb-3">
                                        <video id="videoFeed" width="440" height="280" autoplay></video>
                                    </div>
                                    <input type="hidden" id="faceDescriptorInput" name="face_descriptor">
                                    <button type="button" class="btn btn-primary" id="startCameraBtn">Start Camera</button>
                                    <button type="button" class="btn btn-success" id="captureFaceBtn" disabled>Capture Face</button>
                                </div>
                            </div>
                            <div class="card p-3 mb-2">
                                <h5 class="text-center mb-3">Captured Face</h5>
                                <div class="d-flex justify-content-around">
                                    <div id="capturedFaceContainer">
                                        <img id="capturedFace" src="" alt="Captured Face" class="img-fluid rounded" style="display: block;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 bg-dark text-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#" class="text-light">Privacy Policy</a>
                            &middot;
                            <a href="#" class="text-light">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script>
// Flag to check if models are loaded
let modelsLoaded = false;

// Load the face recognition models
async function loadModels() {
    await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/');
    await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/');
    
    // Set the flag to true once models are loaded
    modelsLoaded = true;
    console.log("Models loaded successfully");
}

// Wait for models to load
loadModels(); // Call to load models as soon as the page is ready

const faceImageInput = document.getElementById('face_image');  // Changed to face_image
const faceDescriptorInput = document.getElementById('faceDescriptorInput');
const submitBtn = document.getElementById('submitBtn');

// Function to process face from the uploaded image
async function processFace() {
    if (!modelsLoaded) {
        alert("Models are not loaded yet. Please wait.");
        return;
    }

    const file = faceImageInput.files[0]; // Changed to face_image
    if (!file) return;

    // Read the image file as an HTML image element
    const img = await faceapi.bufferToImage(file);

    // Detect the face and get its descriptor
    const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();

    if (!detections) {
        alert("No face detected. Please upload a valid image.");
        submitBtn.disabled = true;
        return;
    }

    // Convert the face descriptor (Float32Array) to a simple string format
    const descriptorArray = Array.from(detections.descriptor);
    faceDescriptorInput.value = JSON.stringify(descriptorArray); // Store in hidden input

    // Enable submit button after face is processed
    submitBtn.disabled = false;
}

// Listen for file upload changes to trigger face processing
faceImageInput.addEventListener('change', processFace);  // Changed to face_image

// Function to start the camera and capture face
async function startCamera() {
    // Ensure models are loaded before starting the camera
    if (!modelsLoaded) {
        alert("Models are not loaded yet. Please wait.");
        return;
    }

    const video = document.getElementById('videoFeed');

    try {
        // Request access to the video feed
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;

        // Wait for the video to play before starting face detection
        video.addEventListener('play', () => {
            detectFace(video);
        });
    } catch (error) {
        // Handle possible errors
        if (error.name === 'NotReadableError') {
            alert("Camera is already in use by another application.");
        } else if (error.name === 'NotAllowedError') {
            alert("Permission to access the camera was denied.");
        } else if (error.name === 'NotFoundError') {
            alert("No camera device found.");
        } else {
            alert("An unknown error occurred: " + error.message);
        }
    }
}

// Function to detect and capture face from the video feed
async function detectFace(video) {
    if (!modelsLoaded) {
        alert("Models are not loaded yet. Please wait.");
        return;
    }

    // Ensure faceapi canvas is set up and ready to capture the image
    const canvas = faceapi.createCanvasFromMedia(video);
    document.body.append(canvas); // You can append this canvas to the body or a specific container
    
    // Perform face detection
    const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
    if (detections) {
        // Draw the detections on the canvas
        faceapi.draw.drawDetections(canvas, detections);
        faceapi.draw.drawFaceLandmarks(canvas, detections);

        // Capture the image from the video feed after drawing the face detections
        const imgDataUrl = canvas.toDataURL();
        const imgElement = document.getElementById('capturedFace');
        imgElement.src = imgDataUrl;
        imgElement.style.display = "block";

        // Save the captured face descriptor
        const descriptorArray = Array.from(detections.descriptor);
        faceDescriptorInput.value = JSON.stringify(descriptorArray); // Store in hidden input

        // Enable the capture button
        document.getElementById('captureFaceBtn').disabled = false;
    }

    // Request the next animation frame for continuous face detection
    requestAnimationFrame(() => detectFace(video));
}

// Attach event listeners for buttons
document.getElementById('startCameraBtn').addEventListener('click', startCamera);
document.getElementById('captureFaceBtn').addEventListener('click', () => {
    // Save face image to local storage or computer
    const imgElement = document.getElementById('capturedFace');
    const dataUrl = imgElement.src;

    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = 'captured-face.png';
    link.click();
});

        const positionsByDepartment = {
            "Finance Department": ["Financial Controller", "Accountant", "Credit Analyst", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Administration Department": ["Facilities Manager", "Operations Manager", "Customer Service Representative", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Sales Department": ["Sales Manager", "Sales Representative", "Marketing Coordinator", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Credit Department": ["Loan Officer", "Loan Collection Officer", "Credit Risk Analyst", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Human Resource Department": ["HR Manager", "Recruitment Specialists", "Training Coordinator", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "IT Department": ["IT Manager", "Network Administrator", "System Administrator", "IT Support Specialist", "Supervisor", "Staff", "Field Worker", "Contractual"]
        };

        function filterPositions() {
            const departmentSelect = document.getElementById("inputDepartment");
            const positionSelect = document.getElementById("inputPosition");
            const selectedDepartment = departmentSelect.value;

            // Clear the previous options in the position dropdown
            positionSelect.innerHTML = '<option value="" disabled selected></option>';

            // Populate the position dropdown with positions relevant to the selected department
            if (positionsByDepartment[selectedDepartment]) {
                positionsByDepartment[selectedDepartment].forEach(position => {
                    const option = document.createElement("option");
                    option.value = position;
                    option.textContent = position;
                    positionSelect.appendChild(option);
                });
            }
        }

        // Attach event listener to department dropdown
        document.getElementById("inputDepartment").addEventListener("change", filterPositions);

        document.getElementById('registrationForm').addEventListener('submit', async function (event) {
            event.preventDefault(); // Prevent form submission

            const email = document.getElementById('inputEmail').value;

            // Check if email already has face data
            const response = await fetch(`db/check_face_data.php?email=${encodeURIComponent(email)}`);
            const data = await response.json();

            if (data.hasFaceData) {
                alert('This email already has face data. Please choose another email.');
                return;
            }

            // If no face data exists, proceed with form submission
            this.submit();
        });
</script>
</body>

</html>


