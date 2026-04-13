<?php
require_once 'dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

include("conn.php");

// Query for pending + waiting reservations
$stmt = $conn->prepare("
    SELECT 
        s.stud_id,
        s.name AS student_name,
        b.bh_name,
        ro.room_type,
        r.reserved_at,
        r.status
    FROM reservations r
    INNER JOIN tenants s ON r.tenant_id = s.tenant_id
    INNER JOIN rooms ro ON r.room_id = ro.room_id
    INNER JOIN boarding_houses b ON ro.bh_id = b.bh_id
    WHERE r.status IN (?, ?)
");

$status1 = 'pending';
$status2 = 'waiting';
$stmt->bind_param("ss", $status1, $status2);
$stmt->execute();
$result = $stmt->get_result();

// Build PDF content
$html = '
<h2 style="text-align:center;">Pending Applications Report</h2>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
    <thead>
        <tr style="background:#f2f2f2; text-align:center;">
            <th>Student ID</th>
            <th>Name</th>
            <th>Boarding House</th>
            <th>Room Type</th>
            <th>Date Reserved</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '
        <tr>
            <td>'.htmlspecialchars($row['stud_id']).'</td>
            <td>'.htmlspecialchars($row['student_name']).'</td>
            <td>'.htmlspecialchars($row['bh_name']).'</td>
            <td>'.htmlspecialchars($row['room_type']).'</td>
            <td>'.
                date("Y-m-d H:i", strtotime($row['reserved_at'])) .
            '</td>
            <td>'.ucfirst(htmlspecialchars($row['status'])).'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" style="text-align:center;">No pending applications found.</td></tr>';
}

$html .= '
    </tbody>
</table>
<p style="text-align:right; margin-top:15px;">Generated on: '.date("F j, Y, g:i a").'</p>
';

// Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output the PDF
$filename = "Pending_Applications_Report(" . date('Y-m-d') . ").pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>
