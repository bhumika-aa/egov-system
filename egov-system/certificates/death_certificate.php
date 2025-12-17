<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check authentication
Auth::checkSessionTimeout();
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to access certificates.';
    redirect('../login.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get application ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    $_SESSION['error'] = 'Invalid certificate request.';
    redirect('../citizen/my_applications.php');
}

// Get death registration details
$stmt = $conn->prepare("SELECT dr.*, u.full_name as applicant_name 
                        FROM death_registration dr 
                        JOIN users u ON dr.user_id = u.id 
                        WHERE dr.id = ? AND (dr.user_id = ? OR ? = (SELECT id FROM users WHERE role = 'admin' LIMIT 1))");
$admin_id = $_SESSION['user_role'] == 'admin' ? $_SESSION['user_id'] : 0;
$stmt->bind_param("iii", $id, $user_id, $admin_id);
$stmt->execute();
$death = $stmt->get_result()->fetch_assoc();

if (!$death) {
    $_SESSION['error'] = 'Certificate not found or access denied.';
    redirect('../citizen/my_applications.php');
}

// Check if certificate can be downloaded
if (!canDownloadCertificate($death['status']) && $_SESSION['user_role'] != 'admin') {
    $_SESSION['error'] = 'Certificate is not available for download. Application status: ' . $death['status'];
    redirect('../citizen/my_applications.php');
}

// Generate PDF certificate
require_once('../tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(SITE_NAME);
$pdf->SetAuthor(SITE_NAME);
$pdf->SetTitle('Death Certificate - ' . $death['deceased_first_name'] . ' ' . $death['deceased_last_name']);
$pdf->SetSubject('Official Death Certificate');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Certificate border
$pdf->SetLineWidth(1.5);
$pdf->Rect(10, 10, 190, 277, 'D');

// Government Logo/Emblem (placeholder)
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetTextColor(44, 62, 80);
$pdf->SetXY(0, 20);
$pdf->Cell(0, 0, 'GOVERNMENT OF NEPAL', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(231, 76, 60);
$pdf->SetXY(0, 30);
$pdf->Cell(0, 0, 'OFFICIAL DEATH CERTIFICATE', 0, 1, 'C');

// Certificate Number
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(20, 50);
$pdf->Cell(0, 0, 'Certificate Number: ' . ($death['registration_number'] ?? 'DR-' . $death['id']), 0, 1, 'L');

// Line separator
$pdf->Line(20, 60, 190, 60);

// Certificate Content
$pdf->SetFont('helvetica', '', 12);
$y_position = 70;

// Deceased Details
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Deceased Full Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['deceased_first_name'] . ' ' . ($death['deceased_middle_name'] ? $death['deceased_middle_name'] . ' ' : '') . $death['deceased_last_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Date of Death:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, formatDate($death['date_of_death'], 'F j, Y'), 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Time of Death:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['time_of_death'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Date of Birth:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['date_of_birth'] ? formatDate($death['date_of_birth'], 'F j, Y') : 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Age at Death:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['age_at_death'] ? $death['age_at_death'] . ' years' : 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Gender:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['gender'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Place of Death:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['place_of_death'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Cause of Death:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['death_cause'] ?? 'Not specified', 0, 1, 'L');
$y_position += 15;

// Permanent Address
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Permanent Address:', 0, 1, 'L');
$y_position += 10;

$address = $death['permanent_address_district'] . ', ' . $death['permanent_address_municipality'];
if ($death['permanent_address_ward']) {
    $address .= ', Ward ' . $death['permanent_address_ward'];
}

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->MultiCell(150, 10, $address, 0, 'L');
$y_position += 20;

// Family Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Family Details:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Father\'s Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['father_name'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Mother\'s Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['mother_name'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Spouse\'s Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['spouse_name'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Marital Status:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['marital_status'] ?? 'Not specified', 0, 1, 'L');
$y_position += 15;

// Additional Information
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Additional Information:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Citizenship No:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['citizenship_number'] ?? 'Not provided', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Occupation:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['occupation'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Education:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['education'] ?? 'Not specified', 0, 1, 'L');
$y_position += 15;

// Registration Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Registration Details:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Registered By:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['applicant_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Registration Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, formatDate($death['created_at'], 'F j, Y'), 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Application Status:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $death['status'], 0, 1, 'L');
$y_position += 20;

// Informant Details
if ($death['informant_name']) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetXY(20, $y_position);
    $pdf->Cell(0, 10, 'Informant Details:', 0, 1, 'L');
    $y_position += 10;
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(20, $y_position);
    $pdf->Cell(40, 10, 'Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(100, 10, $death['informant_name'], 0, 1, 'L');
    $y_position += 10;
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetXY(20, $y_position);
    $pdf->Cell(40, 10, 'Relationship:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(100, 10, $death['informant_relationship'], 0, 1, 'L');
    $y_position += 20;
}

// Official Stamp Area
$pdf->SetLineWidth(0.5);
$pdf->Rect(130, 220, 60, 40, 'D');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(130, 230);
$pdf->Cell(60, 10, 'OFFICIAL STAMP', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(130, 245);
$pdf->Cell(60, 10, '_________________________', 0, 1, 'C');

$pdf->SetXY(130, 250);
$pdf->Cell(60, 10, 'Authorized Signatory', 0, 1, 'C');

$pdf->SetXY(130, 255);
$pdf->Cell(60, 10, 'Local Registrar', 0, 1, 'C');

// Watermark
$pdf->SetFont('helvetica', 'B', 40);
$pdf->SetTextColor(200, 200, 200);
$pdf->SetAlpha(0.1);
$pdf->Rotate(45, 100, 150);
$pdf->SetXY(0, 100);
$pdf->Cell(0, 0, 'OFFICIAL CERTIFICATE', 0, 1, 'C');
$pdf->Rotate(0);

// Footer note
$pdf->SetAlpha(1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetXY(20, 260);
$pdf->Cell(0, 10, 'This is a computer-generated certificate. No physical signature required.', 0, 1, 'L');

$pdf->SetXY(20, 265);
$pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y, g:i a'), 0, 1, 'L');

$pdf->SetXY(20, 270);
$pdf->Cell(0, 10, 'Verification URL: ' . SITE_URL . 'verify_certificate.php?id=' . $death['id'] . '&type=death', 0, 1, 'L');

// Output PDF
$filename = 'Death_Certificate_' . $death['deceased_first_name'] . '_' . $death['deceased_last_name'] . '.pdf';
$pdf->Output($filename, 'I');

// Log download activity
logActivity('CERTIFICATE_DOWNLOADED', 'death_registration', $id, null, 'Death certificate downloaded');
?>