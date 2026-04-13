<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include("conn.php");

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vacancy Report</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { text-align: center; margin-bottom: 10px; }
    p { font-size: 11px; text-align: right; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    th { background-color: #f2f2f2; }
</style>
</head>
<body>

<h2>Vacancy Report</h2>
<p>Generated on: <?php echo date("F j, Y, g:i a"); ?></p>

<table>
    <thead>
        <tr>
            <th>Boarding House</th>
            <th>Address</th>
            <th>Landlord</th>
            <th>Vacant Rooms</th>
            <th>Max Tenants</th>
            <th>Active Tenants</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $stmt = $conn->prepare("
                                        SELECT 
                                            bh.bh_id, 
                                            bh.bh_name, 
                                            bh.bh_address, 
                                            l.landlord_name,
                                            l.password_set
                                        FROM boarding_houses AS bh
                                        LEFT JOIN landlords AS l ON bh.bh_id = l.bh_id WHERE l.password_set = 1
                                    ");
                                    $stmt->execute();
                                    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $bh_id = intval($row['bh_id']);
                                            $bh_name = htmlspecialchars($row['bh_name']);
                                            $bh_address = htmlspecialchars($row['bh_address']);
                                            $check_landlord = intval($row['password_set']);
                                            $landlord_name = htmlspecialchars($row['landlord_name']);
                                            
                                            // Get total rooms in this boarding house
                                            $stmt_total = $conn->prepare("SELECT COUNT(*) AS total_rooms FROM rooms WHERE bh_id = ?");
                                            $stmt_total->bind_param("i", $bh_id);
                                            $stmt_total->execute();
                                            $total_rooms_result = $stmt_total->get_result()->fetch_assoc();
                                            $total_rooms = !empty($total_rooms_result['total_rooms'])
                                            ? intval($total_rooms_result['total_rooms'])
                                            : '0';
                                            $stmt_total->close();
                                
                                            // Count rooms that are vacant
                                            $stmt_vacant = $conn->prepare("
                                                SELECT COUNT(r.room_id) AS vacant_rooms
                                                FROM rooms r
                                                WHERE r.bh_id = ? 
                                                AND r.room_capacity <> 0
                                            ");
                                
                                            $stmt_vacant->bind_param("i", $bh_id);
                                            $stmt_vacant->execute();
                                            $vacant_result = $stmt_vacant->get_result()->fetch_assoc();
                                            $vacant_rooms = $vacant_result['vacant_rooms'];
                                            $stmt_vacant->close();
                                            
                                            if($vacant_rooms == 0 && $total_rooms == 0){
                                                $total_vacant = 0;
                                            }else{
                                                $total_vacant = $vacant_rooms . "/" . $total_rooms;
                                            }
                                
                                            // Count active tenants
                                            $stmt_tenants = $conn->prepare("
                                                SELECT COUNT(*) AS active_tenants
                                                FROM reservations res
                                                INNER JOIN rooms r ON res.room_id = r.room_id
                                                WHERE r.bh_id = ? AND res.status = 'Active'
                                            ");
                                            $stmt_tenants->bind_param("i", $bh_id);
                                            $stmt_tenants->execute();
                                            $tenant_result = $stmt_tenants->get_result()->fetch_assoc();
                                            $active_tenants = (isset($tenant_result['active_tenants']) && intval($tenant_result['active_tenants']) > 0)
                                            ? intval($tenant_result['active_tenants'])
                                            : '0';
                                            
                                            $stmt_tenants->close();
                                
                                            // Determine status
                                            if ($vacant_rooms > 0) {
                                                // Has vacant rooms
                                                $status = "Vacant";
                                                $status_class = "success";
                                            } elseif($vacant_rooms == 0 && $active_tenants > 0) {
                                                $status = "Full";
                                                $status_class = "danger";
                                                // All rooms occupied
                                            }elseif($total_vacant == 0){
                                                $status = "No Rooms";
                                                $status_class = "warning";
                                            }
                                
                                            $stmt_max = $conn->prepare("
                                                SELECT 
                                                    SUM(room_capacity) AS max_tenant
                                                FROM rooms
                                                WHERE bh_id = ?
                                            ");
                                            $stmt_max->bind_param("i", $bh_id);
                                            $stmt_max->execute();
                                            $result_max = $stmt_max->get_result();
                                
                                            if ($row_max = $result_max->fetch_assoc()) {
                                                $maxTenant = $row_max['max_tenant'] ?? 0;
                                
                                                if ($active_tenants > 0 ) {
                                                    // Add only if there are active tenants and landlord is verified
                                                    $max_tenant = $maxTenant + $active_tenants;
                                                }elseif($total_vacant == 0 && $maxTenant == 0 ) {
                                                    $max_tenant = 0;
                                                }else{
                                                    $max_tenant = $maxTenant;
                                                }
                                            }
                                
                                            $stmt_max->close();
                                

            echo "<tr>
                <td>$bh_name</td>
                <td>$bh_address</td>
                <td>$landlord_name</td>
                <td>$total_vacant</td>
                <td>$max_tenant</td>
                <td>$active_tenants</td>
                <td>$status</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No boarding houses available.</td></tr>";
    }
    ?>
    </tbody>
</table>

</body>
</html>

<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$filename = "Vacancy_Report(" . date('Y-m-d') . ").pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>
