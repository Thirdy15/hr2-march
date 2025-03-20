<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Employee registration portal with face recognition" />
    <meta name="author" content="HR Department" />
    <title>Employee Account Registration</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Face API -->
    <script src="/HR2/face-api.js-master/dist/face-api.min.js"></script>
    
    <style>
        /* Variables */
        :root {
            /* Colors */
            --primary: #4361ee;
            --primary-light: #5a75f3;
            --primary-dark: #3a56d4;
            --secondary: #2ec4b6;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
            
            /* Light Theme */
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --text-muted: #6c757d;
            --border-color: rgba(0, 0, 0, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.1);
            
            /* Dark Theme */
            --bg-color-dark: #121212;
            --card-bg-dark: #1e1e2c;
            --text-color-dark: #f8f9fa;
            --text-muted-dark: #adb5bd;
            --border-color-dark: rgba(255, 255, 255, 0.1);
            --shadow-color-dark: rgba(0, 0, 0, 0.3);
            
            /* Dimensions */
            --header-height: 70px;
            --footer-height: 80px;
            --border-radius: 12px;
            --btn-border-radius: 8px;
            --input-border-radius: 8px;
            
            /* Transitions */
            --transition-speed: 0.3s;
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color-dark);
            color: var(--text-color-dark);
            line-height: 1.6;
            transition: background-color var(--transition-speed) ease, 
                        color var(--transition-speed) ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        body.light-theme {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: white;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .lead {
            font-size: 1.2rem;
            color: var(--text-muted-dark);
            margin-bottom: 2rem;
        }

        body.light-theme .lead {
            color: var(--text-muted);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background-color: var(--card-bg-dark);
            box-shadow: 0 2px 10px var(--shadow-color-dark);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background-color var(--transition-speed) ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        body.light-theme .header {
            background-color: var(--card-bg);
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .logo i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform var(--transition-speed) ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Registration Status */
        .registration-status {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }

        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--text-muted-dark);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
            transition: background-color var(--transition-speed) ease;
        }

        body.light-theme .step-number {
            background-color: var(--text-muted);
        }

        .status-step.active .step-number,
        .status-step.completed .step-number {
            background-color: var(--primary);
        }

        .status-step.completed .step-number::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .step-label {
            font-size: 0.9rem;
            color: var(--text-muted-dark);
        }

        body.light-theme .step-label {
            color: var(--text-muted);
        }

        .status-step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .status-connector {
            flex-grow: 1;
            height: 3px;
            background-color: var(--text-muted-dark);
            margin: 0 15px;
            position: relative;
            top: -20px;
            max-width: 100px;
        }

        body.light-theme .status-connector {
            background-color: var(--text-muted);
        }

        .status-connector.active {
            background-color: var(--primary);
        }

        /* Alert Container */
        .alert-container {
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .alert::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .alert.alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert.alert-success::before {
            content: '\f058'; /* check-circle */
        }

        .alert.alert-warning {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .alert.alert-warning::before {
            content: '\f071'; /* exclamation-triangle */
        }

        .alert.alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .alert.alert-danger::before {
            content: '\f057'; /* times-circle */
        }

        .alert.alert-info {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--info);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .alert.alert-info::before {
            content: '\f05a'; /* info-circle */
        }

        /* Cards */
        .card {
            background-color: var(--card-bg-dark);
            border-radius: var(--border-radius);
            box-shadow: 0 5px 20px var(--shadow-color-dark);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform var(--transition-speed) ease, 
                        box-shadow var(--transition-speed) ease,
                        background-color var(--transition-speed) ease;
            border: none;
        }

        body.light-theme .card {
            background-color: var(--card-bg);
            box-shadow: 0 5px 20px var(--shadow-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px var(--shadow-color-dark);
        }

        body.light-theme .card:hover {
            box-shadow: 0 10px 30px var(--shadow-color);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color-dark);
            background-color: var(--primary);
            position: relative;
            color: white;
        }

        body.light-theme .card-header {
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border-color-dark);
            border-radius: var(--input-border-radius);
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-color-dark);
            transition: border-color var(--transition-speed) ease, 
                        box-shadow var(--transition-speed) ease;
        
        }

        body.light-theme .form-control {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-muted-dark);
            margin-top: 0.5rem;
        }

        body.light-theme .form-hint {
            color: var(--text-muted);
        }

        .form-actions {
            margin-top: 2rem;
        }

        /* Custom File Upload */
        .custom-file-upload {
            border: 2px dashed var(--border-color-dark);
            border-radius: var(--input-border-radius);
            padding: 1.5rem;
            text-align: center;
            position: relative;
            transition: border-color var(--transition-speed) ease;
            cursor: pointer;
        }

        body.light-theme .custom-file-upload {
            border: 2px dashed var(--border-color);
        }

        .custom-file-upload:hover {
            border-color: var(--primary);
        }

        .custom-file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-muted-dark);
        }

        body.light-theme .file-upload-label {
            color: var(--text-muted);
        }

        .file-upload-label i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .file-preview {
            margin-top: 1rem;
            display: none;
        }

        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--input-border-radius);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: var(--btn-border-radius);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            border: none;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Video Container */
        .video-container {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            background-color: #000;
            margin-bottom: 1.5rem;
            aspect-ratio: 4/3;
        }

        #videoFeed {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            pointer-events: none;
        }

        .face-detection-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px dashed rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }

        .scanning-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            animation: scanning 2s linear infinite;
        }

        @keyframes scanning {
            0% { transform: translateY(0); }
            50% { transform: translateY(100%); }
            100% { transform: translateY(0); }
        }

        .model-status {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Camera Controls */
        .camera-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        /* Captured Face */
        .captured-face-container {
            text-align: center;
        }

        .captured-face-wrapper {
            width: 160px;
            height: 160px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin: 0 auto 1rem;
            border: 3px solid var(--border-color-dark);
            background-color: rgba(255, 255, 255, 0.05);
        }

        body.light-theme .captured-face-wrapper {
            border: 3px solid var(--border-color);
            background-color: rgba(0, 0, 0, 0.05);
        }

        #capturedFace {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--text-muted-dark);
        }

        body.light-theme .placeholder {
            color: var(--text-muted);
        }

        .placeholder i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .placeholder p {
            margin: 0;
            font-size: 0.8rem;
        }

        /* Instructions Card */
        .instructions-card {
            margin-top: 3rem;
        }

        .instructions-card h3 {
            margin-bottom: 1.5rem;
        }

        .instructions-card h3 i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .instructions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .instruction-item {
            display: flex;
            align-items: flex-start;
        }

        .instruction-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(67, 97, 238, 0.2);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .instruction-content h4 {
            margin-bottom: 0.5rem;
        }

        .instruction-content p {
            color: var(--text-muted-dark);
            margin: 0;
        }

        body.light-theme .instruction-content p {
            color: var(--text-muted);
        }

        /* Footer */
        .footer {
            background-color: var(--card-bg-dark);
            padding: 1.5rem 0;
            margin-top: auto;
            border-top: 1px solid var(--border-color-dark);
            transition: background-color var(--transition-speed) ease;
        }

        body.light-theme .footer {
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copyright {
            color: var(--text-muted-dark);
        }

        body.light-theme .copyright {
            color: var(--text-muted);
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links a {
            color: var(--text-muted-dark);
            text-decoration: none;
            transition: color var(--transition-speed) ease;
        }

        body.light-theme .footer-links a {
            color: var(--text-muted);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .camera-controls {
                flex-direction: column;
                gap: 1rem;
            }
            
            .camera-controls .btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .registration-status {
                flex-direction: column;
                gap: 1rem;
            }
            
            .status-connector {
                width: 3px;
                height: 20px;
                margin: 0;
                top: 0;
            }
            
            .instructions-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .header {
                padding: 0 1rem;
            }
            
            .logo span {
                display: none;
            }
            
            .card-header, .card-body {
                padding: 1.25rem;
            }
        }
        
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-id-badge"></i>
            <span>HR Portal</span>
        </div>
        <div class="theme-toggle" id="themeToggle">
            <i class="fas fa-sun"></i>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Employee Registration</h1>
                <p class="lead">Create your account with facial recognition</p>
            </div>

            <div class="registration-status" id="registrationStatus">
                <div class="status-step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Setup</div>
                </div>
                <div class="status-connector"></div>
                <div class="status-step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Face Capture</div>
                </div>
                <div class="status-connector"></div>
                <div class="status-step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Complete</div>
                </div>
            </div>

            <div class="alert-container">
                <div class="alert" id="alertMessage" style="display: none;"></div>
            </div>

            <div class="row">
                <!-- Registration Form -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Account Details</h3>
                        </div>
                        <div class="card-body text-white">
                        <form id="registrationForm" action="registeremployee_db.php" method="POST" enctype="multipart/form-data" style="color: white;">
                            <div class="form-group">
                                <label for="inputEmail">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" id="inputEmail" name="email" class="form-control" 
                                    placeholder="name@company.com" required>
                            </div>

                            <div class="form-group">
                                <label for="face_image">
                                    <i class="fas fa-image"></i> Upload Face Image
                                </label>
                                <div class="custom-file-upload">
                                    <input type="file" id="face_image" name="photo[]" 
                                        accept="image/*" multiple required>
                                    <div class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Choose a file or drag it here</span>
                                    </div>
                                    <div class="file-preview" id="filePreview"></div>
                                </div>
                                <div class="form-hint">Upload a clear photo of your face</div>
                            </div>

                            <!-- Hidden face descriptor input -->
                            <input type="hidden" id="faceDescriptorInput" name="face_descriptor">

                            <div class="form-actions">
                                <button class="btn btn-primary" type="submit" id="submitBtn" disabled>
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

                <!-- Face Registration Section -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Face Recognition</h3>
                        </div>
                        <div class="card-body">
                            <div class="video-container">
                                <video id="videoFeed" autoplay muted></video>
                                <div class="video-overlay" id="faceDetectionOverlay">
                                    <div class="face-detection-box"></div>
                                    <div class="scanning-line"></div>
                                </div>
                                <div class="model-status" id="modelStatus">
                                    <div class="spinner"></div>
                                    <span>Loading face recognition models...</span>
                                </div>
                            </div>

                            <div class="camera-controls">
                                <button type="button" class="btn btn-outline" id="startCameraBtn">
                                    <i class="fas fa-camera"></i> Start Camera
                                </button>
                                <button type="button" class="btn btn-success" id="captureFaceBtn" disabled>
                                    <i class="fas fa-camera-retro"></i> Capture Face
                                </button>
                            </div>

                            <div class="captured-face-container">
                                <h4>Captured Image</h4>
                                <div class="captured-face-wrapper">
                                    <img id="capturedFace" src="/placeholder.svg" alt="Captured Face" style="display: none;">
                                    <div class="placeholder" id="capturedPlaceholder">
                                        <i class="fas fa-user"></i>
                                        <p>No image captured</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline" id="downloadFaceBtn" disabled>
                                    <i class="fas fa-download"></i> Download Image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions Section -->
            <div class="card instructions-card">
                <div class="card-body">
                    <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                    <div class="instructions-grid">
                        <div class="instruction-item">
                            <div class="instruction-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="instruction-content">
                                <h4>Start Camera</h4>
                                <p>Click the button to activate your webcam for face detection</p>
                            </div>
                        </div>
                        <div class="instruction-item">
                            <div class="instruction-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="instruction-content">
                                <h4>Capture Face</h4>
                                <p>Position your face in the frame and take a clear photo</p>
                            </div>
                        </div>
                        <div class="instruction-item">
                            <div class="instruction-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="instruction-content">
                                <h4>Create Account</h4>
                                <p>Submit the form to complete your employee registration</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    &copy; 2023 Your Company. All rights reserved.
                </div>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help Center</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Face Recognition Script -->
    <script>
        // Global variables
        let modelsLoaded = false;
        let stream = null;
        let faceDetectionInterval = null;
        let currentStep = 1;

        // DOM Elements
        const videoFeed = document.getElementById('videoFeed');
        const capturedFace = document.getElementById('capturedFace');
        const capturedPlaceholder = document.getElementById('capturedPlaceholder');
        const startCameraBtn = document.getElementById('startCameraBtn');
        const captureFaceBtn = document.getElementById('captureFaceBtn');
        const downloadFaceBtn = document.getElementById('downloadFaceBtn');
        const faceImageInput = document.getElementById('face_image');
        const faceDescriptorInput = document.getElementById('faceDescriptorInput');
        const submitBtn = document.getElementById('submitBtn');
        const modelStatus = document.getElementById('modelStatus');
        const faceDetectionOverlay = document.getElementById('faceDetectionOverlay');
        const alertMessage = document.getElementById('alertMessage');
        const filePreview = document.getElementById('filePreview');
        const themeToggle = document.getElementById('themeToggle');
        const registrationStatus = document.getElementById('registrationStatus');

        // Load the face recognition models
        async function loadModels() {
            try {
                await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/');
                await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/');
                await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/');
                
                // Set the flag to true once models are loaded
                modelsLoaded = true;
                console.log("Face recognition models loaded successfully");
                
                // Update UI
                modelStatus.style.display = 'none';
                startCameraBtn.disabled = false;
                updateStep(1);
                showAlert('Face recognition models loaded successfully', 'success');
            } catch (error) {
                console.error("Error loading models:", error);
                showAlert('Failed to load face recognition models. Please refresh the page.', 'danger');
            }
        }

        // Start loading models as soon as the page is ready
        document.addEventListener('DOMContentLoaded', () => {
            loadModels();
            initThemeToggle();
            initFileUpload();
        });

        // Function to process face from the uploaded image
        async function processFace() {
            if (!modelsLoaded) {
                showAlert('Models are not loaded yet. Please wait.', 'warning');
                return;
            }

            const file = faceImageInput.files[0];
            if (!file) return;

            try {
                // Show processing indicator
                showAlert('Processing face image...', 'info');
                
                // Read the image file as an HTML image element
                const img = await faceapi.bufferToImage(file);

                // Detect the face and get its descriptor
                const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();

                if (!detections) {
                    showAlert('No face detected. Please upload a valid image.', 'danger');
                    submitBtn.disabled = true;
                    return;
                }

                // Convert the face descriptor (Float32Array) to a simple string format
                const descriptorArray = Array.from(detections.descriptor);
                faceDescriptorInput.value = JSON.stringify(descriptorArray);

                // Display the uploaded image
                capturedFace.src = URL.createObjectURL(file);
                capturedFace.style.display = 'block';
                capturedPlaceholder.style.display = 'none';
                downloadFaceBtn.disabled = false;

                // Enable submit button after face is processed
                submitBtn.disabled = false;
                updateStep(2);
                showAlert('Face processed successfully! You can now submit the form.', 'success');
            } catch (error) {
                console.error("Error processing face:", error);
                showAlert('Error processing face image. Please try again.', 'danger');
            }
        }

        // Initialize file upload preview
        function initFileUpload() {
            faceImageInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        filePreview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                        filePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                    processFace();
                }
            });
        }

        // Function to start the camera
        async function startCamera() {
            // Ensure models are loaded before starting the camera
            if (!modelsLoaded) {
                showAlert('Models are not loaded yet. Please wait.', 'warning');
                return;
            }

            try {
                // Request access to the video feed
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: "user"
                    } 
                });
                
                videoFeed.srcObject = stream;
                
                // Show the scanning animation
                faceDetectionOverlay.style.display = 'block';
                
                // Update button states
                startCameraBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Camera';
                startCameraBtn.classList.add('active');
                
                // Start face detection when video is playing
                videoFeed.onloadedmetadata = () => {
                    videoFeed.play();
                    startFaceDetection();
                };
                
                updateStep(2);
                showAlert('Camera started. Position your face in the frame.', 'info');
            } catch (error) {
                handleCameraError(error);
            }
        }

        // Function to stop the camera
        function stopCamera() {
            if (stream) {
                // Stop all tracks
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                
                // Clear the video source
                videoFeed.srcObject = null;
                
                // Hide the scanning animation
                faceDetectionOverlay.style.display = 'none';
                
                // Reset button
                startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
                startCameraBtn.classList.remove('active');
                
                // Clear detection interval
                if (faceDetectionInterval) {
                    clearInterval(faceDetectionInterval);
                    faceDetectionInterval = null;
                }
                
                captureFaceBtn.disabled = true;
                showAlert('Camera stopped.', 'info');
            }
        }

        // Handle camera errors
        function handleCameraError(error) {
            let errorMessage = 'An unknown error occurred when accessing the camera.';
            
            if (error.name === 'NotReadableError') {
                errorMessage = 'Camera is already in use by another application.';
            } else if (error.name === 'NotAllowedError') {
                errorMessage = 'Permission to access the camera was denied.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera device found.';
            } else {
                errorMessage = `Camera error: ${error.message}`;
            }
            
            console.error(errorMessage, error);
            showAlert(errorMessage, 'danger');
        }

        // Start face detection on video feed
        function startFaceDetection() {
            if (!modelsLoaded || !videoFeed.srcObject) return;
            
            // Run face detection every 500ms
            faceDetectionInterval = setInterval(async () => {
                if (videoFeed.paused || videoFeed.ended) return;
                
                try {
                    const detections = await faceapi.detectSingleFace(videoFeed).withFaceLandmarks().withFaceDescriptor();
                    
                    if (detections) {
                        // Face detected, enable capture button
                        captureFaceBtn.disabled = false;
                        showAlert('Face detected! You can now capture your image.', 'success');
                    } else {
                        // No face detected, disable capture button
                        captureFaceBtn.disabled = true;
                    }
                } catch (error) {
                    console.error("Error during face detection:", error);
                }
            }, 500);
        }

        // Capture face from video feed
        async function captureFace() {
            if (!modelsLoaded || !videoFeed.srcObject) {
                showAlert('Camera is not active.', 'warning');
                return;
            }
            
            try {
                // Create a canvas to capture the current video frame
                const canvas = document.createElement('canvas');
                canvas.width = videoFeed.videoWidth;
                canvas.height = videoFeed.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoFeed, 0, 0, canvas.width, canvas.height);
                
                // Get the image data URL
                const imageDataURL = canvas.toDataURL('image/png');
                
                // Display the captured image
                capturedFace.src = imageDataURL;
                capturedFace.style.display = 'block';
                capturedPlaceholder.style.display = 'none';
                downloadFaceBtn.disabled = false;
                
                // Process the captured face
                const detections = await faceapi.detectSingleFace(canvas).withFaceLandmarks().withFaceDescriptor();
                
                if (detections) {
                    // Save the face descriptor
                    const descriptorArray = Array.from(detections.descriptor);
                    faceDescriptorInput.value = JSON.stringify(descriptorArray);
                    
                    // Enable submit button
                    submitBtn.disabled = false;
                    updateStep(3);
                    showAlert('Face captured successfully! You can now submit the form.', 'success');
                    
                    // Convert canvas to blob and create a file
                    canvas.toBlob((blob) => {
                        const file = new File([blob], "captured-face.png", { type: "image/png" });
                        
                        // Create a FileList-like object
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        
                        // Set the file input's files
                        faceImageInput.files = dataTransfer.files;
                        
                        // Update file preview
                        filePreview.innerHTML = `<img src="${imageDataURL}" alt="Preview">`;
                        filePreview.style.display = 'block';
                    });
                } else {
                    showAlert('No face detected in the captured image. Please try again.', 'warning');
                }
            } catch (error) {
                console.error("Error capturing face:", error);
                showAlert('Error capturing face. Please try again.', 'danger');
            }
        }

        // Download captured face image
        function downloadCapturedFace() {
            if (capturedFace.src && capturedFace.style.display !== 'none') {
                const link = document.createElement('a');
                link.href = capturedFace.src;
                link.download = 'captured-face.png';
                link.click();
            } else {
                showAlert('No face image to download.', 'warning');
            }
        }

        // Update registration step
        function updateStep(step) {
            currentStep = step;
            
            // Update step indicators
            const steps = registrationStatus.querySelectorAll('.status-step');
            const connectors = registrationStatus.querySelectorAll('.status-connector');
            
            steps.forEach((stepEl, index) => {
                const stepNum = index + 1;
                
                if (stepNum < step) {
                    stepEl.classList.add('completed');
                    stepEl.classList.remove('active');
                } else if (stepNum === step) {
                    stepEl.classList.add('active');
                    stepEl.classList.remove('completed');
                } else {
                    stepEl.classList.remove('active', 'completed');
                }
            });
            
            // Update connectors
            connectors.forEach((connector, index) => {
                if (index < step - 1) {
                    connector.classList.add('active');
                } else {
                    connector.classList.remove('active');
                }
            });
        }

        // Show alert message
        function showAlert(message, type) {
            alertMessage.textContent = message;
            alertMessage.className = `alert alert-${type}`;
            alertMessage.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                alertMessage.style.display = 'none';
            }, 5000);
        }

        // Initialize theme toggle
        function initThemeToggle() {
            // Set default to dark theme (already applied in CSS)
            // Toggle theme on click
            themeToggle.addEventListener('click', () => {
                document.body.classList.toggle('light-theme');
                
                if (document.body.classList.contains('light-theme')) {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('theme', 'light');
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('theme', 'dark');
                }
            });
            
            // Check for saved theme preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        }


        // Toggle camera on button click
        startCameraBtn.addEventListener('click', function() {
            if (stream) {
                stopCamera();
            } else {
                startCamera();
            }
        });

        // Capture face on button click
        captureFaceBtn.addEventListener('click', captureFace);

        // Download face on button click
        downloadFaceBtn.addEventListener('click', downloadCapturedFace);
    </script>
</body>

</html>