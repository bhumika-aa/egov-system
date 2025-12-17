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
        redirect('death_form.php');
    }
    
    // Collect and sanitize form data
    $data = [
        'deceased_first_name' => sanitize($_POST['deceased_first_name']),
        'deceased_middle_name' => sanitize($_POST['deceased_middle_name'] ?? ''),
        'deceased_last_name' => sanitize($_POST['deceased_last_name']),
        'gender' => sanitize($_POST['gender']),
        'date_of_death' => sanitize($_POST['date_of_death']),
        'date_of_birth' => sanitize($_POST['date_of_birth'] ?? ''),
        'time_of_death' => sanitize($_POST['time_of_death'] ?? ''),
        'place_of_death' => sanitize($_POST['place_of_death']),
        'death_cause' => sanitize($_POST['death_cause'] ?? ''),
        'death_certified_by' => sanitize($_POST['death_certified_by'] ?? ''),
        
        // Address
        'permanent_address_district' => sanitize($_POST['permanent_address_district']),
        'permanent_address_municipality' => sanitize($_POST['permanent_address_municipality']),
        'permanent_address_ward' => intval($_POST['permanent_address_ward'] ?? 0),
        
        // Citizenship
        'citizenship_number' => sanitize($_POST['citizenship_number'] ?? ''),
        
        // Family
        'father_name' => sanitize($_POST['father_name'] ?? ''),
        'mother_name' => sanitize($_POST['mother_name'] ?? ''),
        'spouse_name' => sanitize($_POST['spouse_name'] ?? ''),
        
        // Informant
        'informant_name' => sanitize($_POST['informant_name'] ?? ''),
        'informant_relationship' => sanitize($_POST['informant_relationship'] ?? ''),
        
        // Medical
        'hospital_name' => sanitize($_POST['hospital_name'] ?? ''),
        'doctor_name' => sanitize($_POST['doctor_name'] ?? ''),
        
        // Additional
        'marital_status' => sanitize($_POST['marital_status'] ?? ''),
        'education' => sanitize($_POST['education'] ?? ''),
        'occupation' => sanitize($_POST['occupation'] ?? ''),
        'religion' => sanitize($_POST['religion'] ?? ''),
        'caste_ethnicity' => sanitize($_POST['caste_ethnicity'] ?? ''),
        'mother_tongue' => sanitize($_POST['mother_tongue'] ?? ''),
        
        'user_id' => $user_id
    ];
    
    // Calculate age if date of birth is provided
    if (!empty($data['date_of_birth']) && !empty($data['date_of_death'])) {
        $birth = new DateTime($data['date_of_birth']);
        $death = new DateTime($data['date_of_death']);
        $data['age_at_death'] = $death->diff($birth)->y;
    }
    
    // Insert into database
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $types = str_repeat('s', count($data));
    
    $stmt = $conn->prepare("INSERT INTO death_registration ($columns) VALUES ($placeholders)");
    $stmt->bind_param($types, ...array_values($data));
    
    if ($stmt->execute()) {
        $application_id = $stmt->insert_id;
        logActivity('DEATH_APPLICATION_SUBMITTED', 'death_registration', $application_id, null, json_encode($data));
        
        $_SESSION['success'] = 'Death registration submitted successfully! Your application ID: DR-' . $application_id;
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
    <title>Register Death - Citizen Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard">
    <?php require_once '../header.php'; ?>
    
    <div class="dashboard-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-cross"></i> Death Registration Form</h1>
                <p>Fill in the details below to register a death</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-section" id="deathForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Deceased Details -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Deceased Person Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="deceased_first_name" required 
                                   placeholder="Enter first name">
                        </div>
                        
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="deceased_middle_name" 
                                   placeholder="Enter middle name">
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="deceased_last_name" required 
                                   placeholder="Enter last name">
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
                            <label>Date of Death *</label>
                            <input type="date" name="date_of_death" required 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Time of Death</label>
                            <input type="time" name="time_of_death">
                        </div>
                        
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Place of Death *</label>
                            <input type="text" name="place_of_death" required 
                                   placeholder="Hospital, Home, or other location">
                        </div>
                        
                        <div class="form-group">
                            <label>Cause of Death</label>
                            <textarea name="death_cause" rows="3" 
                                      placeholder="Enter cause of death"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Certified By</label>
                            <input type="text" name="death_certified_by" 
                                   placeholder="Doctor or medical professional name">
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
                
                <!-- Citizenship Details -->
                <div class="form-section">
                    <h3><i class="fas fa-id-card"></i> Citizenship Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Citizenship Number</label>
                            <input type="text" name="citizenship_number" 
                                   placeholder="Enter citizenship number">
                        </div>
                    </div>
                </div>
                
                <!-- Family Details -->
                <div class="form-section">
                    <h3><i class="fas fa-users"></i> Family Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" 
                                   placeholder="Enter father's name">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" 
                                   placeholder="Enter mother's name">
                        </div>
                        
                        <div class="form-group">
                            <label>Spouse's Name</label>
                            <input type="text" name="spouse_name" 
                                   placeholder="Enter spouse's name">
                        </div>
                    </div>
                </div>
                
                <!-- Informant Details -->
                <div class="form-section">
                    <h3><i class="fas fa-user-check"></i> Informant Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Informant Name</label>
                            <input type="text" name="informant_name" 
                                   placeholder="Person reporting the death">
                        </div>
                        
                        <div class="form-group">
                            <label>Relationship with Deceased</label>
                            <input type="text" name="informant_relationship" 
                                   placeholder="Son, daughter, relative, etc.">
                        </div>
                    </div>
                </div>
                
                <!-- Hospital Details -->
                <div class="form-section">
                    <h3><i class="fas fa-hospital"></i> Medical Details (If Applicable)</h3>
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
                
                <!-- Additional Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Marital Status</label>
                            <select name="marital_status">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Education Level</label>
                            <input type="text" name="education" 
                                   placeholder="Enter education level">
                        </div>
                        
                        <div class="form-group">
                            <label>Occupation</label>
                            <input type="text" name="occupation" 
                                   placeholder="Enter occupation">
                        </div>
                        
                        <div class="form-group">
                            <label>Religion</label>
                            <input type="text" name="religion" 
                                   placeholder="Enter religion">
                        </div>
                        
                        <div class="form-group">
                            <label>Caste/Ethnicity</label>
                            <input type="text" name="caste_ethnicity" 
                                   placeholder="Enter caste or ethnicity">
                        </div>
                        
                        <div class="form-group">
                            <label>Mother Tongue</label>
                            <input type="text" name="mother_tongue" 
                                   placeholder="Enter mother tongue">
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
    // Form validation
    document.getElementById('deathForm').addEventListener('submit', function(e) {
        const deathDate = document.querySelector('input[name="date_of_death"]').value;
        const birthDate = document.querySelector('input[name="date_of_birth"]').value;
        const today = new Date().toISOString().split('T')[0];
        
        if (deathDate > today) {
            e.preventDefault();
            alert('Date of death cannot be in the future.');
            return false;
        }
        
        if (birthDate && birthDate > deathDate) {
            e.preventDefault();
            alert('Date of birth cannot be after date of death.');
            return false;
        }
        
        return true;
    });
    </script>
    
    <?php require_once '../footer.php'; ?>
</body>
</html>