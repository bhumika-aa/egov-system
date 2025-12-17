<?php
// includes/functions.php

// Generate a random registration number
function generateRegistrationNumber($prefix = 'BR') {
    return $prefix . '-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

// Get status badge HTML
function getStatusBadge($status) {
    $classes = [
        'Pending' => 'status-pending',
        'Verified' => 'status-verified',
        'Approved' => 'status-approved',
        'Rejected' => 'status-rejected'
    ];
    
    $class = isset($classes[$status]) ? $classes[$status] : 'status-pending';
    return '<span class="status ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

// Calculate age from date of birth
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $today->diff($birthDate)->y;
    return $age;
}

// Format date for display
function formatDate($date, $format = 'F j, Y') {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

// Validate Nepali date (B.S.)
function validateBSDate($date) {
    // Simple validation for now
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return true;
    }
    return false;
}

// Send email notification
function sendEmailNotification($to, $subject, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Upload file with validation
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    $errors = [];
    $upload_path = 'uploads/' . date('Y/m/');
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $file_name = time() . '_' . basename($file['name']);
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Check file size
    if ($file_size > $max_size) {
        $errors[] = 'File size must be less than ' . ($max_size / 1024 / 1024) . 'MB';
    }
    
    // Check file extension
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = 'Only ' . implode(', ', $allowed_types) . ' files are allowed';
    }
    
    if (empty($errors)) {
        $destination = $upload_path . $file_name;
        if (move_uploaded_file($file_tmp, $destination)) {
            return [
                'success' => true,
                'file_path' => $destination,
                'file_name' => $file_name
            ];
        } else {
            $errors[] = 'Failed to upload file';
        }
    }
    
    return [
        'success' => false,
        'errors' => $errors
    ];
}

// Generate PDF certificate
function generatePDFCertificate($data, $type = 'birth') {
    require_once('tcpdf/tcpdf.php');
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(SITE_NAME);
    $pdf->SetAuthor(SITE_NAME);
    $pdf->SetTitle(($type == 'birth' ? 'Birth' : 'Death') . ' Certificate');
    $pdf->SetSubject('Official Certificate');
    
    // Add a page
    $pdf->AddPage();
    
    // Add content
    $html = '
    <style>
        h1 { color: #2c3e50; text-align: center; }
        .certificate { border: 2px solid #000; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .signature { margin-top: 50px; text-align: right; }
        .watermark { opacity: 0.1; position: absolute; z-index: -1; }
    </style>
    
    <div class="certificate">
        <div class="header">
            <h1>' . ($type == 'birth' ? 'BIRTH CERTIFICATE' : 'DEATH CERTIFICATE') . '</h1>
            <h3>Government of Nepal</h3>
            <p>Registration Number: ' . $data['registration_number'] . '</p>
        </div>
        
        <div class="content">';
    
    if ($type == 'birth') {
        $html .= '
            <p><strong>Child Name:</strong> ' . $data['child_first_name'] . ' ' . $data['child_last_name'] . '</p>
            <p><strong>Date of Birth:</strong> ' . formatDate($data['dob_ad']) . '</p>
            <p><strong>Gender:</strong> ' . $data['gender'] . '</p>
            <p><strong>Father\'s Name:</strong> ' . $data['father_first_name'] . ' ' . $data['father_last_name'] . '</p>
            <p><strong>Mother\'s Name:</strong> ' . $data['mother_first_name'] . ' ' . $data['mother_last_name'] . '</p>
            <p><strong>Birth Place:</strong> ' . $data['birth_place'] . '</p>
            <p><strong>Registration Date:</strong> ' . formatDate($data['created_at']) . '</p>';
    } else {
        $html .= '
            <p><strong>Deceased Name:</strong> ' . $data['deceased_first_name'] . ' ' . $data['deceased_last_name'] . '</p>
            <p><strong>Date of Death:</strong> ' . formatDate($data['dod_ad']) . '</p>
            <p><strong>Date of Birth:</strong> ' . formatDate($data['dob_ad']) . '</p>
            <p><strong>Age at Death:</strong> ' . calculateAge($data['dob_ad'], $data['dod_ad']) . ' years</p>
            <p><strong>Place of Death:</strong> ' . $data['place_of_death'] . '</p>
            <p><strong>Cause of Death:</strong> ' . $data['death_cause'] . '</p>
            <p><strong>Registration Date:</strong> ' . formatDate($data['created_at']) . '</p>';
    }
    
    $html .= '
        </div>
        
        <div class="signature">
            <p><strong>Authorized Signature</strong></p>
            <p>_________________________</p>
            <p>Local Registrar</p>
            <p>Date: ' . date('F j, Y') . '</p>
        </div>
        
        <div class="watermark">
            <h2>' . SITE_NAME . '</h2>
        </div>
    </div>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Save file
    $filename = 'certificates/' . ($type == 'birth' ? 'birth_' : 'death_') . $data['id'] . '.pdf';
    $pdf->Output($filename, 'F');
    
    return $filename;
}

// Check if user can download certificate
function canDownloadCertificate($status) {
    return in_array($status, ['Approved', 'Verified']);
}

// Get dashboard statistics for user
function getUserStatistics($user_id, $conn) {
    $stats = [];
    
    // Birth applications
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM birth_registration WHERE user_id = $user_id");
    $stats['birth'] = $result->fetch_assoc();
    
    // Death applications
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM death_registration WHERE user_id = $user_id");
    $stats['death'] = $result->fetch_assoc();
    
    return $stats;
}
?>