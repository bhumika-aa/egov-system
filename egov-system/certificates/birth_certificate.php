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

// Get birth registration details
$stmt = $conn->prepare("SELECT br.*, u.full_name as applicant_name 
                        FROM birth_registration br 
                        JOIN users u ON br.user_id = u.id 
                        WHERE br.id = ? AND (br.user_id = ? OR ? = (SELECT id FROM users WHERE role = 'admin' LIMIT 1))");
$admin_id = $_SESSION['user_role'] == 'admin' ? $_SESSION['user_id'] : 0;
$stmt->bind_param("iii", $id, $user_id, $admin_id);
$stmt->execute();
$birth = $stmt->get_result()->fetch_assoc();

if (!$birth) {
    $_SESSION['error'] = 'Certificate not found or access denied.';
    redirect('../citizen/my_applications.php');
}

// Check if certificate can be downloaded
if (!canDownloadCertificate($birth['status']) && $_SESSION['user_role'] != 'admin') {
    $_SESSION['error'] = 'Certificate is not available for download. Application status: ' . $birth['status'];
    redirect('../citizen/my_applications.php');
}

// Generate PDF certificate
require_once('../tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(SITE_NAME);
$pdf->SetAuthor(SITE_NAME);
$pdf->SetTitle('Birth Certificate - ' . $birth['child_first_name'] . ' ' . $birth['child_last_name']);
$pdf->SetSubject('Official Birth Certificate');

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
$pdf->SetTextColor(52, 152, 219);
$pdf->SetXY(0, 30);
$pdf->Cell(0, 0, 'OFFICIAL BIRTH CERTIFICATE', 0, 1, 'C');

// Certificate Number
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(20, 50);
$pdf->Cell(0, 0, 'Certificate Number: ' . ($birth['registration_number'] ?? 'BR-' . $birth['id']), 0, 1, 'L');

// Line separator
$pdf->Line(20, 60, 190, 60);

// Certificate Content
$pdf->SetFont('helvetica', '', 12);
$y_position = 70;

// Child Details
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Child\'s Full Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['child_first_name'] . ' ' . ($birth['child_middle_name'] ? $birth['child_middle_name'] . ' ' : '') . $birth['child_last_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Date of Birth:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, formatDate($birth['dob_ad'], 'F j, Y'), 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Time of Birth:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['birth_time'] ?? 'Not specified', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Gender:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['gender'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Place of Birth:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['birth_place'], 0, 1, 'L');
$y_position += 15;

// Father Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Father\'s Details:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Full Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['father_first_name'] . ' ' . ($birth['father_middle_name'] ? $birth['father_middle_name'] . ' ' : '') . $birth['father_last_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Citizenship No:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['father_citizenship_no'] ?? 'Not provided', 0, 1, 'L');
$y_position += 15;

// Mother Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Mother\'s Details:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Full Name:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['mother_first_name'] . ' ' . ($birth['mother_middle_name'] ? $birth['mother_middle_name'] . ' ' : '') . $birth['mother_last_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Citizenship No:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['mother_citizenship_no'] ?? 'Not provided', 0, 1, 'L');
$y_position += 15;

// Permanent Address
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Permanent Address:', 0, 1, 'L');
$y_position += 10;

$address = $birth['permanent_address_district'] . ', ' . $birth['permanent_address_municipality'];
if ($birth['permanent_address_ward']) {
    $address .= ', Ward ' . $birth['permanent_address_ward'];
}

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->MultiCell(150, 10, $address, 0, 'L');
$y_position += 20;

// Registration Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(20, $y_position);
$pdf->Cell(0, 10, 'Registration Details:', 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Registered By:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['applicant_name'], 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Registration Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, formatDate($birth['created_at'], 'F j, Y'), 0, 1, 'L');
$y_position += 10;

$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY(20, $y_position);
$pdf->Cell(40, 10, 'Application Status:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(100, 10, $birth['status'], 0, 1, 'L');
$y_position += 20;

// Official Stamp Area
$pdf->SetLineWidth(0.5);
$pdf->Rect(130, 200, 60, 40, 'D');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(130, 210);
$pdf->Cell(60, 10, 'OFFICIAL STAMP', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(130, 225);
$pdf->Cell(60, 10, '_________________________', 0, 1, 'C');

$pdf->SetXY(130, 230);
$pdf->Cell(60, 10, 'Authorized Signatory', 0, 1, 'C');

$pdf->SetXY(130, 235);
$pdf->Cell(60, 10, 'Local Registrar', 0, 1, 'C');

// QR Code for verification (placeholder)
$pdf->SetXY(20, 210);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 10, 'Scan to verify:', 0, 1, 'L');

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
$pdf->Cell(0, 10, 'Verification URL: ' . SITE_URL . 'verify_certificate.php?id=' . $birth['id'] . '&type=birth', 0, 1, 'L');

// Output PDF
$filename = 'Birth_Certificate_' . $birth['child_first_name'] . '_' . $birth['child_last_name'] . '.pdf';
$pdf->Output($filename, 'I');

// Log download activity
logActivity('CERTIFICATE_DOWNLOADED', 'birth_registration', $id, null, 'Birth certificate downloaded');
?>