<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>


<?php
require_once 'dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

// Connect to database
include("conn.php");

// Query active leases again (same query)
$stmt = $conn->prepare("
    SELECT 
        s.stud_id,
        s.name,
        s.program,
        s.year_level,
        b.bh_name,
        b.bh_address
    FROM reservations r
    INNER JOIN tenants s ON r.tenant_id = s.tenant_id
    INNER JOIN rooms ro ON r.room_id = ro.room_id
    INNER JOIN boarding_houses b ON ro.bh_id = b.bh_id
    WHERE r.status = ?
");
$status = 'active';
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();

// Build HTML
$html = '
<h2 style="text-align:center;">Active Leases Report</h2>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
    <thead>
        <tr style="background:#f2f2f2; text-align:center;">
            <th>Student ID</th>
            <th>Name</th>
            <th>Program</th>
            <th>Year</th>
            <th>Boarding House</th>
            <th>Address</th>
        </tr>
    </thead>
    <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '
        <tr>
            <td>'.htmlspecialchars($row['stud_id']).'</td>
            <td>'.htmlspecialchars($row['name']).'</td>
            <td>'.htmlspecialchars($row['program']).'</td>
            <td>'.htmlspecialchars($row['year_level']).'</td>
            <td>'.htmlspecialchars($row['bh_name']).'</td>
            <td>'.htmlspecialchars($row['bh_address']).'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="6" style="text-align:center;">No active leases found.</td></tr>';
}

$html .= '
    </tbody>
</table>
<p style="text-align:right; margin-top:15px;">Generated on: '.date("F j, Y, g:i a").'</p>
';

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output PDF

$filename = "Active_Leases_Report(" . date('Y-m-d') . ").pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>
