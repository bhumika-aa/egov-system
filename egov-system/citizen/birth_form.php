<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check authentication and citizen role
Auth::checkSessionTimeout();
if (!Auth::hasRole('citizen')) {
    $_SESSION['error'] = 'Access denied. Please login as citizen.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        redirect('birth_form.php');
    }
    
    // Collect and sanitize form data
    $data = [
        'child_first_name' => sanitize($_POST['child_first_name']),
        'child_middle_name' => sanitize($_POST['child_middle_name'] ?? ''),
        'child_last_name' => sanitize($_POST['child_last_name']),
        'gender' => sanitize($_POST['gender']),
        'dob_ad' => sanitize($_POST['dob_ad']),
        'dob_bs' => sanitize($_POST['dob_bs'] ?? ''),
        'birth_time' => sanitize($_POST['birth_time'] ?? ''),
        'birth_place' => sanitize($_POST['birth_place']),
        'birth_type' => sanitize($_POST['birth_type'] ?? 'Single'),
        'birth_weight' => sanitize($_POST['birth_weight'] ?? ''),
        'disability' => sanitize($_POST['disability'] ?? 'No'),
        'disability_details' => sanitize($_POST['disability_details'] ?? ''),
        
        // Father details
        'father_first_name' => sanitize($_POST['father_first_name']),
        'father_middle_name' => sanitize($_POST['father_middle_name'] ?? ''),
        'father_last_name' => sanitize($_POST['father_last_name']),
        'father_citizenship_no' => sanitize($_POST['father_citizenship_no'] ?? ''),
        'father_date_of_birth' => sanitize($_POST['father_date_of_birth'] ?? ''),
        
        // Mother details
        'mother_first_name' => sanitize($_POST['mother_first_name']),
        'mother_middle_name' => sanitize($_POST['mother_middle_name'] ?? ''),
        'mother_last_name' => sanitize($_POST['mother_last_name']),
        'mother_citizenship_no' => sanitize($_POST['mother_citizenship_no'] ?? ''),
        'mother_date_of_birth' => sanitize($_POST['mother_date_of_birth'] ?? ''),
        
        // Address
        'permanent_address_district' => sanitize($_POST['permanent_address_district']),
        'permanent_address_municipality' => sanitize($_POST['permanent_address_municipality']),
        'permanent_address_ward' => intval($_POST['permanent_address_ward'] ?? 0),
        
        // Hospital
        'hospital_name' => sanitize($_POST['hospital_name'] ?? ''),
        'doctor_name' => sanitize($_POST['doctor_name'] ?? ''),
        
        'user_id' => $user_id
    ];
    
    // Insert into database
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $types = str_repeat('s', count($data));
    
    $stmt = $conn->prepare("INSERT INTO birth_registration ($columns) VALUES ($placeholders)");
    $stmt->bind_param($types, ...array_values($data));
    
    if ($stmt->execute()) {
        $application_id = $stmt->insert_id;
        logActivity('BIRTH_APPLICATION_SUBMITTED', 'birth_registration', $application_id, null, json_encode($data));
        
        $_SESSION['success'] = 'Birth registration submitted successfully! Your application ID: BR-' . $application_id;
        redirect('my_applications.php');
    } else {
        $_SESSION['error'] = 'Error submitting application: ' . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Birth - Citizen Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-baby"></i> Birth Registration Form</h1>
                <p>Fill in the details below to register a new birth</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-section" id="birthForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Child Details -->
                <div class="form-section">
                    <h3><i class="fas fa-baby"></i> Newborn Child Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="child_first_name" required 
                                   placeholder="Enter child's first name">
                        </div>
                        
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="child_middle_name" 
                                   placeholder="Enter child's middle name">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="child_last_name" required 
                                   placeholder="Enter child's last name">
                        </div>
                        
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth (A.D.) *</label>
                            <input type="date" name="dob_ad" required 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth (B.S.)</label>
                            <input type="date" name="dob_bs">
                        </div>
                        
                        <div class="form-group">
                            <label>Time of Birth</label>
                            <input type="time" name="birth_time">
                        </div>
                        
                        <div class="form-group">
                            <label>Place of Birth *</label>
                            <input type="text" name="birth_place" required 
                                   placeholder="Hospital, Home, or Health Center">
                        </div>
                        
                        <div class="form-group">
                            <label>Birth Type</label>
                            <select name="birth_type">
                                <option value="Single">Single</option>
                                <option value="Twins">Twins</option>
                                <option value="Multiple">Multiple</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Birth Weight (kg)</label>
                            <input type="number" step="0.01" name="birth_weight" 
                                   placeholder="Enter weight in kilograms">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Any Physical Disability?</label>
                        <select name="disability" id="disabilitySelect">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="disabilityDetails" style="display: none;">
                        <label>Disability Details</label>
                        <textarea name="disability_details" rows="3" 
                                  placeholder="Specify the disability"></textarea>
                    </div>
                </div>
                
                <!-- Father Details -->
                <div class="form-section">
                    <h3><i class="fas fa-male"></i> Father's Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="father_first_name" required 
                                   placeholder="Enter father's first name">
                        </div>
                        
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="father_middle_name" 
                                   placeholder="Enter father's middle name">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="father_last_name" required 
                                   placeholder="Enter father's last name">
                        </div>
                        
                        <div class="form-group">
                            <label>Citizenship Number</label>
                            <input type="text" name="father_citizenship_no" 
                                   placeholder="Enter citizenship number">
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="father_date_of_birth" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Mother Details -->
                <div class="form-section">
                    <h3><i class="fas fa-female"></i> Mother's Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="mother_first_name" required 
                                   placeholder="Enter mother's first name">
                        </div>
                        
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="mother_middle_name" 
                                   placeholder="Enter mother's middle name">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="mother_last_name" required 
                                   placeholder="Enter mother's last name">
                        </div>
                        
                        <div class="form-group">
                            <label>Citizenship Number</label>
                            <input type="text" name="mother_citizenship_no" 
                                   placeholder="Enter citizenship number">
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="mother_date_of_birth" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Address Details -->
                <div class="form-section">
                    <h3><i class="fas fa-home"></i> Permanent Address</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>District *</label>
                            <input type="text" name="permanent_address_district" required 
                                   placeholder="Enter district">
                        </div>
                        
                        <div class="form-group">
                            <label>Municipality/Rural Municipality *</label>
                            <input type="text" name="permanent_address_municipality" required 
                                   placeholder="Enter municipality">
                        </div>
                        
                        <div class="form-group">
                            <label>Ward Number</label>
                            <input type="number" name="permanent_address_ward" min="1" max="35" 
                                   placeholder="Enter ward number">
                        </div>
                    </div>
                </div>
                
                <!-- Hospital Details -->
                <div class="form-section">
                    <h3><i class="fas fa-hospital"></i> Hospital Details (If Applicable)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hospital Name</label>
                            <input type="text" name="hospital_name" 
                                   placeholder="Enter hospital name">
                        </div>
                        
                        <div class="form-group">
                            <label>Doctor's Name</label>
                            <input type="text" name="doctor_name" 
                                   placeholder="Enter doctor's name">
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                    <a href="dashboard.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
                
                <div class="form-info">
                    <p><i class="fas fa-info-circle"></i> Note: Fields marked with * are required. Please ensure all information is accurate.</p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Show/hide disability details
    document.getElementById('disabilitySelect').addEventListener('change', function() {
        const detailsDiv = document.getElementById('disabilityDetails');
        detailsDiv.style.display = this.value === 'Yes' ? 'block' : 'none';
    });
    
    // Form validation
    document.getElementById('birthForm').addEventListener('submit', function(e) {
        const dob = document.querySelector('input[name="dob_ad"]').value;
        const today = new Date().toISOString().split('T')[0];
        
        if (dob > today) {
            e.preventDefault();
            alert('Date of birth cannot be in the future.');
            return false;
        }
        
        return true;
    });
    </script>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>