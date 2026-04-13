<?php
include 'conn.php';

// Update expired tokens in the database first
$updateStmt = "
    UPDATE file_access_tokens 
    SET status = 'expired' 
    WHERE status = 'pending' 
    AND expires_at <= UTC_TIMESTAMP()
";
mysqli_query($conn, $updateStmt);

// Fetch landlord data
$statement = "
    SELECT l.landlord_id, l.landlord_name, l.landlord_email, l.landlord_number,
        l.password_set, bh.bh_name, bh.bh_id,
        fat.status AS token_status, fat.expires_at
    FROM landlords l
    LEFT JOIN boarding_houses bh ON l.bh_id = bh.bh_id
    LEFT JOIN file_access_tokens fat 
        ON l.landlord_id = fat.landlord_id
        AND fat.token_id = (
            SELECT MAX(token_id) 
            FROM file_access_tokens 
            WHERE landlord_id = l.landlord_id
        )
    ORDER BY l.landlord_id ASC
    // LIMIT 1; // Remove or comment out if you want all landlords
";

$query = mysqli_query($conn, $statement);

if ($query && $query->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        // Set status text based on password_set and token_status
        if (!empty($row['password_set'])) {
            $statusText = "<span class='badge bg-success'>Verified</span>";
        } elseif ($row['token_status'] === 'expired') {
            $statusText = "<span class='badge bg-danger'>Expired</span>";
        } else {
            $statusText = "<span class='badge bg-warning text-dark'>Pending</span>";
        }

        // Determine if Send Link button should be enabled
        $canSendLink = empty($row['password_set']) && ($row['token_status'] !== 'pending' && $row['token_status'] !== 'used');

        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['landlord_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['landlord_email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['landlord_number']) . '</td>';
        echo '<td>' . htmlspecialchars($row['bh_name'] ?? '—') . '</td>';
        echo '<td>' . $statusText . '</td>';
        echo '<td>';
        echo '<div class="d-flex flex-column gap-2">';
        echo '<button class="btn btn-primary btn-sm w-100 editLandlordBtn" 
                data-id="' . $row['landlord_id'] . '"
                data-name="' . htmlspecialchars($row['landlord_name']) . '"
                data-email="' . htmlspecialchars($row['landlord_email']) . '"
                data-number="' . htmlspecialchars($row['landlord_number']) . '"
                data-bh="' . htmlspecialchars($row['bh_id']) . '"
              >
                Edit Landlord
              </button>';
        echo '<form method="POST" action="superadminprocess.php">';
        echo '<input type="hidden" value="' . $row['landlord_id'] . '" name="landlord_id">';
        echo '<button 
                type="submit" name="send_link"
                class="btn btn-secondary btn-sm w-100 sendLinkBtn"
                data-id="' . $row['landlord_id'] . '"
                data-status="' . htmlspecialchars($row['token_status'] ?? 'none') . '"
                data-expires="' . htmlspecialchars($row['expires_at'] ?? '') . '"
                ' . ((!empty($row['password_set']) || ($row['token_status'] === 'pending' || $row['token_status'] === 'used')) ? 'disabled' : '') . '
              >
                ' . (!empty($row['password_set']) ? 'Already Set' : ($row['token_status'] === 'pending' ? 'Link Sent' : ($row['token_status'] === 'expired' ? 'Send Link' : 'Send Link'))) . '
              </button>';
        echo '</form>';
        echo '<!-- Remove Landlord Form -->';
        echo '<form action="superadminprocess.php" method="POST" onsubmit="return confirm(\'Are you sure you want to remove this landlord?\');" class="d-grid">';
        echo '<input type="hidden" name="landlord_id" value="' . $row['landlord_id'] . '">';
        echo '<button type="submit" name="deleteLandlord" class="btn btn-danger btn-sm w-100">';
        echo 'Remove Landlord';
        echo '</button>';
        echo '</form>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo "<tr><td colspan='6' class='text-center'>No landlords added yet.</td></tr>";
}

$conn->close();
?>