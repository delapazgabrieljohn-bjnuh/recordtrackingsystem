<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Administrators only.");
}

include("./connection/config.php");
include("./helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();
$success_message = '';
$requests = [];

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdate'])) {
    $request_id = $_POST['request_id'] ?? '';
	$status = trim(filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW) ?? '');
	$claiming_area = trim(filter_input(INPUT_POST, 'claiming_area', FILTER_UNSAFE_RAW) ?? '');

	$allowed_statuses = ['Pending', 'Processing', 'Approved', 'Ready for Claiming', 'Completed', 'Rejected'];

    if ($request_id && in_array($status, $allowed_statuses) && $claiming_area !== '') {
		$enc_status = $so->encrypt($status);
		$enc_area = $so->encrypt($claiming_area);

		$stmt = $con->prepare("UPDATE document_requests SET status = ?, claiming_area = ? WHERE id = ?");
        $stmt->bind_param("ssi", $enc_status, $enc_area, $request_id);
        
        if ($stmt->execute()) {
            // Post-Redirect-Get pattern to prevent form resubmission on refresh
            $active_tab = $_GET['tab'] ?? 'all';
            header("Location: admin_dashboard.php?success=1");
            exit;
        }
        $stmt->close();
	}
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = 'Request updated successfully.';
}

$current_tab = $_GET['tab'] ?? 'all';

// Fetch all document requests
$query = "SELECT * FROM document_requests ORDER BY id DESC";
if ($result = $con->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $row['status'] = $so->decrypt($row['status']) ?: 'Pending';
        $requests[] = $row;
    }
}

$con->close();

$filtered_requests = [];
foreach ($requests as $request) {
    if ($current_tab === 'all') {
        $filtered_requests[] = $request;
    } elseif ($current_tab === 'pending' && $request['status'] === 'Pending') {
        $filtered_requests[] = $request;
    } elseif ($current_tab === 'processing' && $request['status'] === 'Processing') {
        $filtered_requests[] = $request;
    } elseif ($current_tab === 'approved' && $request['status'] === 'Approved') {
        $filtered_requests[] = $request;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Document Requests</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h2>Admin Dashboard</h2>
    <p>Review submitted document requests and update their status and claiming area.</p>

    <?php if ($success_message): ?>
        <div class="success-alert"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-container">
        <a href="admin_dashboard.php?tab=all" class="tab-btn <?= $current_tab === 'all' ? 'active' : '' ?>">All Requests</a>
        <a href="admin_dashboard.php?tab=pending" class="tab-btn <?= $current_tab === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="admin_dashboard.php?tab=processing" class="tab-btn <?= $current_tab === 'processing' ? 'active' : '' ?>">Processing</a>
        <a href="admin_dashboard.php?tab=approved" class="tab-btn <?= $current_tab === 'approved' ? 'active' : '' ?>">Approved</a>
    </div>

    <h3>Submitted Requests (<?= count($filtered_requests) ?>)</h3>
    
    <div class="table-wrap">
        <table class="request-table" border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Student No.</th>
                    <th>Student Name</th>
                    <th>Program</th>
                    <th>File Type</th>
                    <th>Purpose</th>
                    <th>Claiming Area</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_requests)): ?>
                    <tr><td class="empty-requests" colspan="8">No document requests submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($filtered_requests as $request): ?>
                        <?php
                            // Decrypt fields up front for cleaner HTML rendering
                            $status = $so->decrypt($request['status']) ?: 'Pending';
                            $claiming_area = $so->decrypt($request['claiming_area'] ?? '') ?: '';
                            $form_id = 'request-update-' . (int)$request['id'];
                            $first = $so->decrypt($request['firstname']);
                            $middle = $so->decrypt($request['middlename']) ?: '';
                            $last = $so->decrypt($request['lastname']);
                            $full_name = trim("$first $middle $last");
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($so->decrypt($request['file_no'])) ?></strong></td>
                            <td><?= htmlspecialchars($so->decrypt($request['student_no'])) ?></td>
                            <td><?= htmlspecialchars($full_name) ?></td>
                            <td><?= htmlspecialchars($so->decrypt($request['program'])) ?></td>
                            <td><?= htmlspecialchars($so->decrypt($request['doc_type'])) ?></td>
                            <td><?= htmlspecialchars($so->decrypt($request['purpose'])) ?></td>
                            <td>
                                <input class="request-area" type="text" name="claiming_area" form="<?= $form_id ?>" placeholder="Claiming area" value="<?= htmlspecialchars($claiming_area) ?>" required>
                            </td>
                            <td>
                                <form class="request-form" id="<?= $form_id ?>" method="POST" action="admin_dashboard.php">
                                    <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                    <select name="status" required>
                                        <?php 
                                        $options = ['Pending', 'Processing', 'Approved', 'Ready for Claiming', 'Completed', 'Rejected'];
                                        foreach ($options as $opt): 
                                        ?>
                                            <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <button type="submit" name="btnUpdate">Save Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>