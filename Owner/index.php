<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include '../connection.php';

// Cek apakah user sudah login dan role-nya owner
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Owner') {
  header("Location: ../signin.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['create_account'])) {
    // Create new account
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];
    $division_id = $_POST['division_id'];

    // Handle photo upload
    $photo_name = "";
    if (!empty($_FILES['photo']['name'])) {
      $photo_name = time() . "_" . basename($_FILES['photo']['name']);
      $target = "../uploads/" . $photo_name;
      move_uploaded_file($_FILES['photo']['tmp_name'], $target);
    }

    $sql = "INSERT INTO users (name, username, email, password, role_id, division_id, photo) 
            VALUES ('$name', '$username', '$email', '$password', '$role_id', '$division_id', '$photo_name')";

    if (mysqli_query($conn, $sql)) {
      $_SESSION['success'] = "Akun berhasil dibuat!";
      header("Location: index.php?tab=manage-accounts");
      exit();
    } else {
      $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
  } elseif (isset($_POST['update_account'])) {
    // Update existing account
    $id = $_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role_id = $_POST['role_id'];
    $division_id = $_POST['division_id'];
    $status = $_POST['status'];

    // Handle photo upload
    $photo_sql = "";
    if (!empty($_FILES['photo']['name'])) {
      $photo_name = time() . "_" . basename($_FILES['photo']['name']);
      $target = "../uploads/" . $photo_name;
      move_uploaded_file($_FILES['photo']['tmp_name'], $target);
      $photo_sql = ", photo = '$photo_name'";
    }

    // Handle password update
    $password_sql = "";
    if (!empty($_POST['password'])) {
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $password_sql = ", password = '$password'";
    }

    $sql = "UPDATE users SET 
            name = '$name', 
            username = '$username', 
            email = '$email', 
            role_id = '$role_id', 
            division_id = '$division_id', 
            status = '$status'
            $photo_sql $password_sql 
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
      $_SESSION['success'] = "Akun berhasil diperbarui!";
      header("Location: index.php?tab=manage-accounts");
      exit();
    } else {
      $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
  } elseif (isset($_POST['update_profile'])) {
    // Update owner profile
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Handle photo upload
    $photo_sql = "";
    if (!empty($_FILES['photo']['name'])) {
      $photo_name = time() . "_" . basename($_FILES['photo']['name']);
      $target = "../uploads/" . $photo_name;
      move_uploaded_file($_FILES['photo']['tmp_name'], $target);
      $photo_sql = ", photo = '$photo_name'";
      $_SESSION['photo'] = $photo_name;
    }

    // Handle password update
    $password_sql = "";
    if (!empty($_POST['password'])) {
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $password_sql = ", password = '$password'";
    }

    $sql = "UPDATE users SET 
            name = '$name', 
            username = '$username', 
            email = '$email'
            $photo_sql $password_sql 
            WHERE id = $user_id";

    if (mysqli_query($conn, $sql)) {
      $_SESSION['user_name'] = $name;
      $_SESSION['success'] = "Profil berhasil diperbarui!";
      header("Location: index.php?tab=profile");
      exit();
    } else {
      $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
  } elseif (isset($_POST['approve_leave'])) {
    // Approve leave request
    $leave_id = $_POST['leave_id'];
    $update_leave = mysqli_query($conn, "UPDATE leave_requests SET status = 'Approved' WHERE id = $leave_id");
    if ($update_leave) {
      $_SESSION['success'] = "Cuti berhasil disetujui!";
    } else {
      $_SESSION['error'] = "Gagal menyetujui cuti: " . mysqli_error($conn);
    }
    header("Location: index.php?tab=leave-approval");
    exit();
  } elseif (isset($_POST['reject_leave'])) {
    // Reject leave request
    $leave_id = $_POST['leave_id'];
    $update_leave = mysqli_query($conn, "UPDATE leave_requests SET status = 'Rejected' WHERE id = $leave_id");
    if ($update_leave) {
      $_SESSION['success'] = "Cuti berhasil ditolak!";
    } else {
      $_SESSION['error'] = "Gagal menolak cuti: " . mysqli_error($conn);
    }
    header("Location: index.php?tab=leave-approval");
    exit();
  } elseif (isset($_POST['approve_overtime'])) {
    // Approve overtime request
    $overtime_id = $_POST['overtime_id'];
    $update_overtime = mysqli_query($conn, "UPDATE overtime SET status = 'Approved' WHERE id = $overtime_id");
    if ($update_overtime) {
      $_SESSION['success'] = "Lembur berhasil disetujui!";
    } else {
      $_SESSION['error'] = "Gagal menyetujui lembur: " . mysqli_error($conn);
    }
    header("Location: index.php?tab=overtime-approval");
    exit();
  } elseif (isset($_POST['reject_overtime'])) {
    // Reject overtime request
    $overtime_id = $_POST['overtime_id'];
    $update_overtime = mysqli_query($conn, "UPDATE overtime SET status = 'Rejected' WHERE id = $overtime_id");
    if ($update_overtime) {
      $_SESSION['success'] = "Lembur berhasil ditolak!";
    } else {
      $_SESSION['error'] = "Gagal menolak lembur: " . mysqli_error($conn);
    }
    header("Location: index.php?tab=overtime-approval");
    exit();
  } elseif (isset($_POST['update_payroll_status'])) {
    // Update payroll status and upload payment proof
    $payroll_id = $_POST['payroll_id'];
    $payment_status = $_POST['payment_status'];
    
    $payment_proof_sql = "";
    if (!empty($_FILES['payment_proof']['name'])) {
      $payment_proof_name = time() . "_payment_" . basename($_FILES['payment_proof']['name']);
      $target = "../uploads/" . $payment_proof_name;
      move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target);
      $payment_proof_sql = ", payment_proof = '$payment_proof_name'";
    }
    
    $sql = "UPDATE payroll SET payment_status = '$payment_status' $payment_proof_sql WHERE id = $payroll_id";
    if (mysqli_query($conn, $sql)) {
      $_SESSION['success'] = "Status payroll berhasil diperbarui!";
    } else {
      $_SESSION['error'] = "Gagal memperbarui status payroll: " . mysqli_error($conn);
    }
    header("Location: index.php?tab=payroll");
    exit();
  }
}

// Handle delete account
if (isset($_GET['delete'])) {
  $delete_id = $_GET['delete'];
  if ($delete_id != $user_id) { // Prevent self-deletion
    $sql = "DELETE FROM users WHERE id = $delete_id";
    if (mysqli_query($conn, $sql)) {
      $_SESSION['success'] = "Akun berhasil dihapus!";
      header("Location: index.php?tab=manage-accounts");
      exit();
    } else {
      $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
  } else {
    $_SESSION['error'] = "Tidak dapat menghapus akun sendiri!";
  }
}

// Handle session messages
if (isset($_SESSION['success'])) {
  $success = $_SESSION['success'];
  unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
  $error = $_SESSION['error'];
  unset($_SESSION['error']);
}

// Get user data for edit
$edit_user = null;
if (isset($_GET['edit'])) {
  $edit_id = $_GET['edit'];
  $result = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = $edit_id");
  $edit_user = mysqli_fetch_assoc($result);
}

// Get current user data
$current_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Get all users with role and division names
$users_query = mysqli_query($conn, "
    SELECT u.*, r.role_name, d.division_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    LEFT JOIN divisions d ON u.division_id = d.id 
    WHERE r.role_name != 'Owner'
    ORDER BY u.created_at DESC
");

// Get roles and divisions for dropdowns
$roles = mysqli_query($conn, "SELECT * FROM roles WHERE role_name != 'Owner'");
$divisions = mysqli_query($conn, "SELECT * FROM divisions");

// Statistics
$total_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id != 1")->fetch_assoc()['total'];
$active_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'active' AND role_id != 1")->fetch_assoc()['total'];
$hr_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'HR')")->fetch_assoc()['total'];
$leader_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Leader')")->fetch_assoc()['total'];
$employee_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Employee')")->fetch_assoc()['total'];

// Get data for dashboard
$pending_leaves = mysqli_query($conn, "SELECT COUNT(*) as total FROM leave_requests WHERE status='Pending'")->fetch_assoc()['total'];
$pending_overtime = mysqli_query($conn, "SELECT COUNT(*) as total FROM overtime WHERE status='Pending'")->fetch_assoc()['total'];
$total_divisions = mysqli_query($conn, "SELECT COUNT(*) as total FROM divisions")->fetch_assoc()['total'];
$total_payroll_processed = mysqli_query($conn, "SELECT COUNT(*) as total FROM payroll")->fetch_assoc()['total'];
$total_payroll_amount = mysqli_query($conn, "SELECT SUM(total_salary) as total FROM payroll")->fetch_assoc()['total'];

// Get leave requests for approval (semua divisi)
$leave_requests = mysqli_query($conn, "SELECT lr.*, u.name as employee_name, d.division_name, lt.type_name
                                      FROM leave_requests lr 
                                      JOIN users u ON lr.user_id = u.id 
                                      LEFT JOIN divisions d ON u.division_id = d.id 
                                      LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
                                      WHERE lr.status = 'Pending' 
                                      ORDER BY lr.start_date DESC");

// Get overtime requests for approval (semua divisi)
$overtime_requests = mysqli_query($conn, "SELECT ot.*, u.name as employee_name, d.division_name 
                                         FROM overtime ot 
                                         JOIN users u ON ot.user_id = u.id 
                                         LEFT JOIN divisions d ON u.division_id = d.id 
                                         WHERE ot.status = 'Pending' 
                                         ORDER BY ot.date DESC");

// Get payroll data
$payroll_data = mysqli_query($conn, "SELECT p.*, u.name, d.division_name
                                    FROM payroll p 
                                    JOIN users u ON p.user_id = u.id 
                                    LEFT JOIN divisions d ON u.division_id = d.id
                                    ORDER BY p.pay_date DESC");

// Get attendance data for reports (semua karyawan kecuali owner)
$attendance_data = mysqli_query($conn, "SELECT a.*, u.name, d.division_name 
                                       FROM attendance a 
                                       JOIN users u ON a.user_id = u.id 
                                       LEFT JOIN divisions d ON u.division_id = d.id 
                                       WHERE u.role_id != 1
                                       ORDER BY a.date DESC 
                                       LIMIT 50");

// Data for charts
$attendance_chart_data = mysqli_query($conn, "
    SELECT DATE(date) as day, COUNT(*) as count 
    FROM attendance 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date)
    ORDER BY day DESC
    LIMIT 7
");

$payroll_chart_data = mysqli_query($conn, "
    SELECT MONTH(pay_date) as month, SUM(total_salary) as total
    FROM payroll 
    WHERE YEAR(pay_date) = YEAR(CURDATE())
    GROUP BY MONTH(pay_date)
    ORDER BY month
");

$division_chart_data = mysqli_query($conn, "
    SELECT d.division_name, COUNT(u.id) as employee_count
    FROM divisions d
    LEFT JOIN users u ON d.id = u.division_id AND u.role_id != 1
    GROUP BY d.id, d.division_name
");

// Format tanggal Indonesia
function indonesianDate($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    
    $months = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    $days = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    
    try {
        $english_day = date('l', strtotime($date));
        $english_month = date('F', strtotime($date));
        
        $indonesian_day = $days[$english_day] ?? $english_day;
        $indonesian_month = $months[$english_month] ?? $english_month;
        
        return $indonesian_day . ', ' . date('d', strtotime($date)) . ' ' . $indonesian_month . ' ' . date('Y', strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Owner | Slippy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Modern Premium Design System */
    :root {
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --accent-primary: linear-gradient(135deg, #ff6b35, #ff8e35);
      --accent-secondary: #ff6b35;
      --accent-tertiary: #ff8e35;
      --border-primary: rgba(0, 0, 0, 0.05);
      --border-secondary: rgba(0, 0, 0, 0.1);
      --card-bg: rgba(255, 255, 255, 0.95);
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.03);
      --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      --glass: rgba(255, 255, 255, 0.7);
      --sidebar-width: 280px;
    }

    .dark {
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --accent-primary: linear-gradient(135deg, #ff6b35, #ff8e35);
      --accent-secondary: #ff6b35;
      --accent-tertiary: #ff8e35;
      --border-primary: rgba(255, 255, 255, 0.05);
      --border-secondary: rgba(255, 255, 255, 0.1);
      --card-bg: rgba(15, 23, 42, 0.95);
      --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
      --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.25);
      --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.3);
      --glass: rgba(15, 23, 42, 0.7);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      overflow-x: hidden;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      transition: var(--transition);
    }

    /* Layout */
    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: var(--sidebar-width);
      background: var(--glass);
      border-right: 1px solid var(--border-primary);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      z-index: 100;
      transition: var(--transition);
    }

    .sidebar-header {
      padding: 2rem 1.5rem 1.5rem;
      border-bottom: 1px solid var(--border-primary);
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-primary);
      text-decoration: none;
    }

    .logo span {
      background: var(--accent-primary);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-weight: 700;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 1rem;
      padding: 1rem;
      background: var(--card-bg);
      border-radius: var(--radius-md);
      border: 1px solid var(--border-primary);
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--accent-secondary);
    }

    .user-details h4 {
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 2px;
    }

    .user-details p {
      font-size: 0.8rem;
      color: var(--text-secondary);
    }

    .nav-links {
      padding: 1.5rem 0;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.75rem 1.5rem;
      color: var(--text-secondary);
      text-decoration: none;
      transition: var(--transition);
      border-left: 3px solid transparent;
      cursor: pointer;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      font-family: inherit;
      font-size: inherit;
    }

    .nav-item:hover,
    .nav-item.active {
      background: var(--card-bg);
      color: var(--accent-secondary);
      border-left-color: var(--accent-secondary);
    }

    .nav-item svg {
      width: 20px;
      height: 20px;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      padding: 2rem;
      position: relative;
      min-height: 100vh;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-primary);
    }

    .header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    /* Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      backdrop-filter: blur(10px);
      transition: var(--transition);
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      background: var(--accent-primary);
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
    }

    .stat-icon svg {
      width: 24px;
      height: 24px;
      color: white;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .stat-label {
      color: var(--text-secondary);
      font-size: 0.9rem;
    }

    /* Charts */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .chart-container {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      backdrop-filter: blur(10px);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .chart-header h3 {
      font-size: 1.1rem;
      font-weight: 600;
    }

    .chart-wrapper {
      position: relative;
      height: 300px;
    }

    /* Content Cards */
    .content-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: 2rem;
      backdrop-filter: blur(10px);
      margin-bottom: 2rem;
      box-shadow: var(--shadow-sm);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .card-header h2 {
      font-size: 1.3rem;
      font-weight: 600;
    }

    /* Tables */
    .table-container {
      overflow-x: auto;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-primary);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }

    th,
    td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border-primary);
    }

    th {
      font-weight: 600;
      color: var(--text-secondary);
      font-size: 0.9rem;
      background: var(--bg-primary);
      position: sticky;
      top: 0;
    }

    .user-avatar-sm {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      display: inline-block;
      text-align: center;
      min-width: 80px;
    }

    .status-active {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .status-inactive {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.1);
      color: #d97706;
      border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .status-approved {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .status-rejected {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .status-present {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .status-absent {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .status-paid {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .status-unpaid {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Buttons */
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-sm);
      font-weight: 500;
      transition: var(--transition);
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      font-family: inherit;
    }

    .btn:focus {
      outline: 2px solid var(--accent-secondary);
      outline-offset: 2px;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
    }

    .btn-primary {
      background: var(--accent-primary);
      color: white;
      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }

    .btn-success {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .btn-success:hover:not(:disabled) {
      background: rgba(34, 197, 94, 0.2);
    }

    .btn-danger {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-danger:hover:not(:disabled) {
      background: rgba(239, 68, 68, 0.2);
    }

    .btn-warning {
      background: rgba(245, 158, 11, 0.1);
      color: #d97706;
      border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .btn-warning:hover:not(:disabled) {
      background: rgba(245, 158, 11, 0.2);
    }

    .btn-outline {
      border: 1px solid var(--border-secondary);
      color: var(--text-primary);
      background: var(--glass);
    }

    .btn-outline:hover:not(:disabled) {
      border-color: var(--accent-secondary);
      color: var(--accent-secondary);
    }

    .btn-sm {
      padding: 0.5rem 1rem;
      font-size: 0.8rem;
    }

    /* Forms */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      background: var(--card-bg);
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      color: var(--text-primary);
      transition: var(--transition);
      outline: none;
      font-family: inherit;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
      border-color: var(--accent-secondary);
      box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }

    .form-textarea {
      resize: vertical;
      min-height: 100px;
    }

    .file-upload {
      border: 2px dashed var(--border-secondary);
      border-radius: var(--radius-sm);
      padding: 2rem;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
    }

    .file-upload:hover {
      border-color: var(--accent-secondary);
    }

    .file-upload:focus {
      outline: 2px solid var(--accent-secondary);
      outline-offset: 2px;
    }

    .preview-container {
      margin-top: 1rem;
      text-align: center;
    }

    .preview-container img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--accent-secondary);
    }

    /* Notifications */
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      animation: slideIn 0.3s ease-out;
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.1);
      border: 1px solid rgba(34, 197, 94, 0.2);
      color: #16a34a;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
      color: #dc2626;
    }

    /* Theme Toggle */
    .theme-toggle {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--glass);
      border: 1px solid var(--border-secondary);
      cursor: pointer;
      transition: var(--transition);
    }

    .theme-toggle:hover {
      border-color: var(--accent-secondary);
    }

    .theme-toggle:focus {
      outline: 2px solid var(--accent-secondary);
      outline-offset: 2px;
    }

    .theme-toggle svg {
      width: 20px;
      height: 20px;
      color: var(--text-primary);
    }

    /* Close button for edit form */
    .close-edit-form {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1rem;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .charts-grid {
        grid-template-columns: 1fr;
      }

      .main-content {
        padding: 1rem;
      }

      .action-buttons {
        flex-direction: column;
        gap: 0.5rem;
      }

      .action-buttons .btn {
        width: 100%;
        justify-content: center;
      }

      .table-container {
        font-size: 0.8rem;
      }

      th,
      td {
        padding: 0.5rem;
      }
    }

    @media (max-width: 480px) {
      .header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }

      .header-actions {
        width: 100%;
        justify-content: space-between;
      }

      .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }
    }

    /* Animations */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .fade-in {
      animation: fadeIn 0.5s ease-out;
    }

    /* Tab Content */
    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease-out;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 3rem 2rem;
      color: var(--text-secondary);
    }

    .empty-state svg {
      width: 64px;
      height: 64px;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    /* Loading Spinner */
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: var(--bg-secondary);
      border-radius: var(--radius-lg);
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-secondary);
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <div class="logo">Sli<span>ppy</span></div>
        <div class="user-info">
          <img src="../uploads/<?= $_SESSION['photo'] ?? 'default.png' ?>" alt="Profile" class="user-avatar" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNGRjZCMzUiLz4KPHBhdGggZD0iTTIwIDIyQzIyLjIwOTEgMjIgMjQgMjAuMjA5MSAyNCAxOEMyNCAxNS43OTA5IDIyLjIwOTEgMTQgMjAgMTRDMTcuNzkwOSAxNCAxNiAxNS43OTA5IDE2IDE4QzE2IDIwLjIwOTEgMTcuNzkwOSAyMiAyMCAyMloiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0yMCAyNkMxNS41ODIyIDI2IDEyIDI4LjIzODUgMTIgMzFDMjggMzEgMjggMzEgMjggMzFDMjggMjguMjM4NSAyNC40MTc4IDI2IDIwIDI2WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+'">
          <div class="user-details">
            <h4><?= htmlspecialchars($_SESSION['user_name']) ?></h4>
            <p><?= htmlspecialchars($_SESSION['role_name']) ?></p>
          </div>
        </div>
      </div>

      <nav class="nav-links">
        <button class="nav-item active" onclick="switchTab('dashboard')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </button>
        <button class="nav-item" onclick="switchTab('manage-accounts')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
          </svg>
          Kelola Akun
        </button>
        <button class="nav-item" onclick="switchTab('leave-approval')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Approval Cuti
        </button>
        <button class="nav-item" onclick="switchTab('overtime-approval')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Approval Lembur
        </button>
        <button class="nav-item" onclick="switchTab('payroll')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Payroll
        </button>
        <button class="nav-item" onclick="switchTab('reports')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Laporan
        </button>
        <button class="nav-item" onclick="switchTab('create-account')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Buat Akun Baru
        </button>
        <button class="nav-item" onclick="switchTab('profile')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Edit Profil
        </button>
        <a href="../logout.php" class="nav-item" onclick="return confirm('Yakin ingin logout?')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Logout
        </a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1>Dashboard Owner</h1>
        <div class="header-actions">
          <button class="theme-toggle" aria-label="Toggle theme">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </button>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="alert alert-success fade-in">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <?= $success ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert alert-error fade-in">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <?= $error ?>
        </div>
      <?php endif; ?>

      <!-- Dashboard Tab -->
      <div class="tab-content active" id="dashboard">
        <div class="stats-grid">
          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-label">Total Karyawan</div>
          </div>

          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="stat-value"><?= $active_users ?></div>
            <div class="stat-label">Karyawan Aktif</div>
          </div>

          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="stat-value"><?= $total_divisions ?></div>
            <div class="stat-label">Total Divisi</div>
          </div>

          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="stat-value"><?= $total_payroll_processed ?></div>
            <div class="stat-label">Payroll Diproses</div>
          </div>

          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div class="stat-value"><?= $pending_leaves ?></div>
            <div class="stat-label">Cuti Pending</div>
          </div>

          <div class="stat-card fade-in">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="stat-value"><?= $pending_overtime ?></div>
            <div class="stat-label">Lembur Pending</div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
          <div class="chart-container fade-in">
            <div class="chart-header">
              <h3>Distribusi Karyawan per Divisi</h3>
            </div>
            <div class="chart-wrapper">
              <canvas id="divisionChart"></canvas>
            </div>
          </div>

          <div class="chart-container fade-in">
            <div class="chart-header">
              <h3>Payroll Bulanan (<?= date('Y') ?>)</h3>
            </div>
            <div class="chart-wrapper">
              <canvas id="payrollChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Pengajuan Cuti yang Perlu Disetujui</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Jenis Cuti</th>
                  <th>Tanggal Mulai</th>
                  <th>Tanggal Selesai</th>
                  <th>Alasan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($leave_requests) > 0): ?>
                  <?php while ($leave = mysqli_fetch_assoc($leave_requests)): ?>
                    <tr>
                      <td><?= htmlspecialchars($leave['employee_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['division_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['type_name'] ?? '') ?></td>
                      <td><?= indonesianDate($leave['start_date'] ?? '') ?></td>
                      <td><?= indonesianDate($leave['end_date'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['reason'] ?? '') ?></td>
                      <td>
                        <div class="action-buttons">
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                            <button type="submit" name="approve_leave" class="btn btn-success btn-sm">Setujui</button>
                          </form>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                            <button type="submit" name="reject_leave" class="btn btn-danger btn-sm">Tolak</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p>Tidak ada pengajuan cuti yang perlu disetujui</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Pengajuan Lembur yang Perlu Disetujui</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Tanggal</th>
                  <th>Jam Lembur</th>
                  <th>Deskripsi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($overtime_requests) > 0): ?>
                  <?php while ($overtime = mysqli_fetch_assoc($overtime_requests)): ?>
                    <tr>
                      <td><?= htmlspecialchars($overtime['employee_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($overtime['division_name'] ?? '') ?></td>
                      <td><?= indonesianDate($overtime['date'] ?? '') ?></td>
                      <td><?= $overtime['hours'] ?? '0' ?> jam</td>
                      <td><?= htmlspecialchars($overtime['description'] ?? '') ?></td>
                      <td>
                        <div class="action-buttons">
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="overtime_id" value="<?= $overtime['id'] ?>">
                            <button type="submit" name="approve_overtime" class="btn btn-success btn-sm">Setujui</button>
                          </form>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="overtime_id" value="<?= $overtime['id'] ?>">
                            <button type="submit" name="reject_overtime" class="btn btn-danger btn-sm">Tolak</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Tidak ada pengajuan lembur yang perlu disetujui</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Manage Accounts Tab -->
      <div class="tab-content" id="manage-accounts">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Kelola Semua Akun Karyawan</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Divisi</th>
                  <th>Status</th>
                  <th>Tanggal Bergabung</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                mysqli_data_seek($users_query, 0); // Reset pointer
                while ($user = mysqli_fetch_assoc($users_query)):
                ?>
                  <tr>
                    <td>
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="../uploads/<?= $user['photo'] ?? 'default.png' ?>" alt="Profile" class="user-avatar-sm" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiNGRjZCMzUiLz4KPHBhdGggZD0iTTE2IDE4QzE4LjIwOTEgMTggMjAgMTYuMjA5MSAyMCAxNEMyMCAxMS43OTA5IDE4LjIwOTEgMTAgMTYgMTBDMTMuNzkwOSAxMCAxMiAxMS43OTA5IDEyIDE0QzEyIDE2LjIwOTEgMTMuNzkwOSAxOCAxNiAxOFoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xNiAyMkMxMS41ODIyIDIyIDggMjQuMjM4NSA4IDI3QzI0IDI3IDI0IDI3IDI0IDI3QzI0IDI0LjIzODUgMjAuNDE3OCAyMiAxNiAyMloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg=='">
                        <div>
                          <div style="font-weight: 500;"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                          <div style="font-size: 0.8rem; color: var(--text-secondary);">@<?= htmlspecialchars($user['username'] ?? '') ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['role_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['division_name'] ?? '-') ?></td>
                    <td>
                      <span class="status-badge <?= ($user['status'] ?? '') === 'active' ? 'status-active' : 'status-inactive' ?>">
                        <?= ucfirst($user['status'] ?? '') ?>
                      </span>
                    </td>
                    <td><?= date('d M Y', strtotime($user['created_at'] ?? '')) ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="?edit=<?= $user['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <?php if ($user['id'] != $user_id): ?>
                          <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus akun ini?')">Hapus</a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Leave Approval Tab -->
      <div class="tab-content" id="leave-approval">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Semua Pengajuan Cuti</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Jenis Cuti</th>
                  <th>Tanggal Mulai</th>
                  <th>Tanggal Selesai</th>
                  <th>Alasan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $all_leaves = mysqli_query($conn, "SELECT lr.*, u.name as employee_name, d.division_name, lt.type_name
                                                 FROM leave_requests lr 
                                                 JOIN users u ON lr.user_id = u.id 
                                                 LEFT JOIN divisions d ON u.division_id = d.id 
                                                 LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id 
                                                 ORDER BY lr.start_date DESC");
                ?>
                <?php if (mysqli_num_rows($all_leaves) > 0): ?>
                  <?php while ($leave = mysqli_fetch_assoc($all_leaves)): ?>
                    <tr>
                      <td><?= htmlspecialchars($leave['employee_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['division_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['type_name'] ?? '') ?></td>
                      <td><?= indonesianDate($leave['start_date'] ?? '') ?></td>
                      <td><?= indonesianDate($leave['end_date'] ?? '') ?></td>
                      <td><?= htmlspecialchars($leave['reason'] ?? '') ?></td>
                      <td>
                        <span class="status-badge status-<?= strtolower($leave['status'] ?? '') ?>">
                          <?= $leave['status'] ?? '' ?>
                        </span>
                      </td>
                      <td>
                        <?php if (($leave['status'] ?? '') == 'Pending'): ?>
                          <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                              <button type="submit" name="approve_leave" class="btn btn-success btn-sm">Setujui</button>
                            </form>
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                              <button type="submit" name="reject_leave" class="btn btn-danger btn-sm">Tolak</button>
                            </form>
                          </div>
                        <?php else: ?>
                          <span style="color: var(--text-secondary);">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p>Belum ada pengajuan cuti</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Overtime Approval Tab -->
      <div class="tab-content" id="overtime-approval">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Semua Pengajuan Lembur</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Tanggal</th>
                  <th>Jam Lembur</th>
                  <th>Deskripsi</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $all_overtime = mysqli_query($conn, "SELECT ot.*, u.name as employee_name, d.division_name 
                                                   FROM overtime ot 
                                                   JOIN users u ON ot.user_id = u.id 
                                                   LEFT JOIN divisions d ON u.division_id = d.id 
                                                   ORDER BY ot.date DESC");
                ?>
                <?php if (mysqli_num_rows($all_overtime) > 0): ?>
                  <?php while ($overtime = mysqli_fetch_assoc($all_overtime)): ?>
                    <tr>
                      <td><?= htmlspecialchars($overtime['employee_name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($overtime['division_name'] ?? '') ?></td>
                      <td><?= indonesianDate($overtime['date'] ?? '') ?></td>
                      <td><?= $overtime['hours'] ?? '0' ?> jam</td>
                      <td><?= htmlspecialchars($overtime['description'] ?? '') ?></td>
                      <td>
                        <span class="status-badge status-<?= strtolower($overtime['status'] ?? '') ?>">
                          <?= $overtime['status'] ?? '' ?>
                        </span>
                      </td>
                      <td>
                        <?php if (($overtime['status'] ?? '') == 'Pending'): ?>
                          <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="overtime_id" value="<?= $overtime['id'] ?>">
                              <button type="submit" name="approve_overtime" class="btn btn-success btn-sm">Setujui</button>
                            </form>
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="overtime_id" value="<?= $overtime['id'] ?>">
                              <button type="submit" name="reject_overtime" class="btn btn-danger btn-sm">Tolak</button>
                            </form>
                          </div>
                        <?php else: ?>
                          <span style="color: var(--text-secondary);">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Belum ada pengajuan lembur</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Payroll Tab -->
      <div class="tab-content" id="payroll">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Riwayat Payroll</h2>
            <div>
              <strong>Total Digaji: <?= formatRupiah($total_payroll_amount) ?></strong>
            </div>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Periode</th>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Gaji Pokok</th>
                  <th>Tunjangan</th>
                  <th>Lembur</th>
                  <th>Potongan</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Bukti Bayar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($payroll_data) > 0): ?>
                  <?php while ($payroll = mysqli_fetch_assoc($payroll_data)): ?>
                    <tr>
                      <td><?= date('F Y', strtotime($payroll['pay_date'] ?? '')) ?></td>
                      <td><?= htmlspecialchars($payroll['name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($payroll['division_name'] ?? '') ?></td>
                      <td><?= formatRupiah($payroll['base_salary'] ?? 0) ?></td>
                      <td><?= formatRupiah(($payroll['allowance'] ?? 0) + ($payroll['transport_allowance'] ?? 0) + ($payroll['meal_allowance'] ?? 0) + ($payroll['position_allowance'] ?? 0)) ?></td>
                      <td><?= formatRupiah($payroll['overtime_pay'] ?? 0) ?></td>
                      <td><?= formatRupiah(($payroll['deduction'] ?? 0) + ($payroll['tax_pph21'] ?? 0) + ($payroll['bpjs_kes'] ?? 0) + ($payroll['bpjs_tk'] ?? 0)) ?></td>
                      <td><strong><?= formatRupiah($payroll['total_salary'] ?? 0) ?></strong></td>
                      <td>
                        <span class="status-badge status-<?= strtolower($payroll['payment_status'] ?? 'unpaid') ?>">
                          <?= $payroll['payment_status'] ?? 'Unpaid' ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($payroll['payment_proof'] ?? ''): ?>
                          <a href="../uploads/<?= $payroll['payment_proof'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td>
                        <button class="btn btn-outline btn-sm" onclick="openPayrollModal(<?= $payroll['id'] ?>, '<?= $payroll['payment_status'] ?? 'Unpaid' ?>')">
                          Update Status
                        </button>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="11" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Belum ada data payroll</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reports Tab -->
      <div class="tab-content" id="reports">
        <div class="charts-grid">
          <div class="chart-container fade-in">
            <div class="chart-header">
              <h3>Trend Absensi 7 Hari Terakhir</h3>
            </div>
            <div class="chart-wrapper">
              <canvas id="attendanceChart"></canvas>
            </div>
          </div>

          <div class="chart-container fade-in">
            <div class="chart-header">
              <h3>Status Karyawan</h3>
            </div>
            <div class="chart-wrapper">
              <canvas id="employeeStatusChart"></canvas>
            </div>
          </div>
        </div>

        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Laporan Absensi Terbaru</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Nama Karyawan</th>
                  <th>Divisi</th>
                  <th>Clock In</th>
                  <th>Clock Out</th>
                  <th>Status</th>
                  <th>Mode</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($attendance_data) > 0): ?>
                  <?php while ($attendance = mysqli_fetch_assoc($attendance_data)): ?>
                    <tr>
                      <td><?= indonesianDate($attendance['date'] ?? '') ?></td>
                      <td><?= htmlspecialchars($attendance['name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($attendance['division_name'] ?? '') ?></td>
                      <td><?= date('H:i', strtotime($attendance['clock_in'] ?? '')) ?></td>
                      <td><?= $attendance['clock_out'] ? date('H:i', strtotime($attendance['clock_out'])) : '--:--' ?></td>
                      <td>
                        <span class="status-badge status-<?= strtolower($attendance['status'] ?? '') ?>">
                          <?= $attendance['status'] ?? '' ?>
                        </span>
                      </td>
                      <td><?= $attendance['mode'] ?? '' ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">
                      <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p>Belum ada data absensi</p>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Create Account Tab -->
      <div class="tab-content" id="create-account">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Buat Akun Baru</h2>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                  <option value="">Pilih Role</option>
                  <?php
                  mysqli_data_seek($roles, 0);
                  while ($role = mysqli_fetch_assoc($roles)): ?>
                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Divisi</label>
                <select name="division_id" class="form-select" required>
                  <option value="">Pilih Divisi</option>
                  <?php
                  mysqli_data_seek($divisions, 0);
                  while ($division = mysqli_fetch_assoc($divisions)):
                  ?>
                    <option value="<?= $division['id'] ?>"><?= htmlspecialchars($division['division_name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Foto Profil (Opsional)</label>
              <div class="file-upload" onclick="document.getElementById('photo-create').click()">
                <div style="margin-bottom: 1rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <div style="color: var(--text-secondary);">Klik untuk upload foto profil</div>
                <input type="file" name="photo" id="photo-create" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview-create')">
              </div>
              <div class="preview-container">
                <img id="preview-create" src="#" alt="Preview" style="display: none;">
              </div>
            </div>

            <button type="submit" name="create_account" class="btn btn-primary">Buat Akun</button>
          </form>
        </div>
      </div>

      <!-- Edit Profile Tab -->
      <div class="tab-content" id="profile">
        <div class="content-card fade-in">
          <div class="card-header">
            <h2>Edit Profil Saya</h2>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($current_user['name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($current_user['username']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($current_user['email']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Password Baru (Opsional)</label>
                <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Foto Profil</label>
              <div class="file-upload" onclick="document.getElementById('photo-profile').click()">
                <div style="margin-bottom: 1rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <div style="color: var(--text-secondary);">Klik untuk upload foto profil baru</div>
                <input type="file" name="photo" id="photo-profile" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview-profile')">
              </div>
              <div class="preview-container">
                <img id="preview-profile" src="../uploads/<?= $current_user['photo'] ?? 'default.png' ?>" alt="Current Profile" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIiBmaWxsPSIjRkY2QjM1Ii8+CjxwYXRoIGQ9Ik01MCA1NUM1Ny43MzEgNTUgNjQgNDguNzMxIDY0IDQxQzY0IDMzLjI2OSA1Ny43MzEgMjcgNTAgMjdDNDIuMjY5IDI3IDM2IDMzLjI2OSAzNiA0MUMzNiA0OC43MzEgNDIuMjY5IDU1IDUwIDU1WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTUwIDY1QzM4Ljk1NDUgNjUgMzAgNzMuOTU0NSAzMCA4NUM3MCA4NSA3MCA4NSA3MCA4NUM3MCA3My45NTQ1IDYxLjA0NTUgNjUgNTAgNjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4='">
              </div>
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary">Perbarui Profil</button>
          </form>
        </div>
      </div>

      <!-- Edit Account Form (shown when editing) -->
      <?php if ($edit_user): ?>
        <div class="content-card fade-in" id="edit-form">
          <div class="close-edit-form">
            <a href="index.php?tab=manage-accounts" class="btn btn-outline btn-sm">✕ Tutup Form Edit</a>
          </div>
          <div class="card-header">
            <h2>Edit Akun: <?= htmlspecialchars($edit_user['name']) ?></h2>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($edit_user['name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($edit_user['username']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Password Baru (Opsional)</label>
                <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah">
              </div>
              <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                  <?php
                  mysqli_data_seek($roles, 0);
                  while ($role = mysqli_fetch_assoc($roles)):
                  ?>
                    <option value="<?= $role['id'] ?>" <?= $edit_user['role_id'] == $role['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($role['role_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Divisi</label>
                <select name="division_id" class="form-select" required>
                  <option value="">Pilih Divisi</option>
                  <?php
                  mysqli_data_seek($divisions, 0);
                  while ($division = mysqli_fetch_assoc($divisions)):
                  ?>
                    <option value="<?= $division['id'] ?>" <?= $edit_user['division_id'] == $division['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($division['division_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option value="active" <?= $edit_user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                  <option value="inactive" <?= $edit_user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Foto Profil</label>
              <div class="file-upload" onclick="document.getElementById('photo-edit').click()">
                <div style="margin-bottom: 1rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <div style="color: var(--text-secondary);">Klik untuk upload foto profil baru</div>
                <input type="file" name="photo" id="photo-edit" accept="image/*" style="display: none;" onchange="previewImage(this, 'preview-edit')">
              </div>
              <div class="preview-container">
                <img id="preview-edit" src="../uploads/<?= $edit_user['photo'] ?? 'default.png' ?>" alt="Current Profile" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIiBmaWxsPSIjRkY2QjM1Ii8+CjxwYXRoIGQ9Ik01MCA1NUM1Ny43MzEgNTUgNjQgNDguNzMxIDY0IDQxQzY0IDMzLjI2OSA1Ny43MzEgMjcgNTAgMjdDNDIuMjY5IDI3IDM2IDMzLjI2OSAzNiA0MUMzNiA0OC43MzEgNDIuMjY5IDU1IDUwIDU1WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTUwIDY1QzM4Ljk1NDUgNjUgMzAgNzMuOTU0NSAzMCA4NUM3MCA4NSA3MCA4NSA3MCA4NUM3MCA3My45NTQ1IDYxLjA0NTUgNjUgNTAgNjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4='">
              </div>
            </div>

            <div style="display: flex; gap: 1rem;">
              <button type="submit" name="update_account" class="btn btn-primary">Perbarui Akun</button>
              <a href="index.php?tab=manage-accounts" class="btn btn-outline">Batal</a>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal for updating payroll status -->
  <div class="modal" id="payrollModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Update Status Payroll</h3>
        <button class="modal-close" onclick="closePayrollModal()">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="payroll_id" id="modalPayrollId">
        <div class="form-group">
          <label class="form-label">Status Pembayaran</label>
          <select name="payment_status" class="form-select" id="modalPaymentStatus" required>
            <option value="Unpaid">Unpaid</option>
            <option value="Paid">Paid</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Bukti Pembayaran (Opsional)</label>
          <input type="file" name="payment_proof" class="form-input" accept="image/*,application/pdf">
          <small style="color: var(--text-secondary);">Format: JPG, PNG, PDF. Maks: 5MB</small>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
          <button type="button" class="btn btn-outline" onclick="closePayrollModal()">Batal</button>
          <button type="submit" name="update_payroll_status" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Tab Navigation
    function switchTab(tabName) {
      // Hide all tab contents
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });

      // Remove active class from all nav items
      document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active');
      });

      // Show selected tab content
      document.getElementById(tabName).classList.add('active');

      // Add active class to clicked nav item
      if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
      }

      // Update browser history
      history.replaceState(null, null, `#${tabName}`);
    }

    // Check for hash on page load
    window.addEventListener('load', function() {
      const hash = window.location.hash.substring(1);
      if (hash && document.getElementById(hash)) {
        switchTab(hash);

        // Update active nav item
        document.querySelectorAll('.nav-item').forEach(nav => {
          nav.classList.remove('active');
          if (nav.getAttribute('onclick') && nav.getAttribute('onclick').includes(hash)) {
            nav.classList.add('active');
          }
        });
      }
    });

    // Theme Toggle
    const themeToggle = document.querySelector('.theme-toggle');
    const html = document.documentElement;

    themeToggle.addEventListener('click', () => {
      html.classList.toggle('dark');
      localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
    });

    // Check for saved theme preference
    if (localStorage.getItem('theme') === 'dark' ||
      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      html.classList.add('dark');
    }

    // Image Preview Function
    function previewImage(input, previewId) {
      const preview = document.getElementById(previewId);
      const file = input.files[0];

      if (file) {
        // Validasi file size
        if (file.size > 5 * 1024 * 1024) {
          alert('Ukuran file maksimal 5MB.');
          input.value = '';
          return;
        }

        // Validasi file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
          alert('Hanya file JPG, PNG, dan GIF yang diizinkan.');
          input.value = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
          if (alert.parentNode) {
            alert.remove();
          }
        }, 500);
      });
    }, 5000);

    // If editing a user, show the manage accounts tab and scroll to edit form
    <?php if ($edit_user): ?>
      document.addEventListener('DOMContentLoaded', function() {
        const manageAccountsTab = document.querySelector('[onclick="switchTab(\'manage-accounts\')"]');
        if (manageAccountsTab) {
          manageAccountsTab.click();
        }

        // Scroll to edit form
        const editForm = document.getElementById('edit-form');
        if (editForm) {
          setTimeout(() => {
            editForm.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }, 100);
        }
      });
    <?php endif; ?>

    // Payroll Modal Functions
    function openPayrollModal(payrollId, currentStatus) {
      document.getElementById('modalPayrollId').value = payrollId;
      document.getElementById('modalPaymentStatus').value = currentStatus;
      document.getElementById('payrollModal').classList.add('active');
    }

    function closePayrollModal() {
      document.getElementById('payrollModal').classList.remove('active');
    }

    // Close modal when clicking outside
    document.getElementById('payrollModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closePayrollModal();
      }
    });

    // Charts
    document.addEventListener('DOMContentLoaded', function() {
      // Division Chart
      const divisionCtx = document.getElementById('divisionChart').getContext('2d');
      <?php
      $division_labels = [];
      $division_data = [];
      mysqli_data_seek($division_chart_data, 0);
      while ($row = mysqli_fetch_assoc($division_chart_data)) {
        $division_labels[] = $row['division_name'];
        $division_data[] = $row['employee_count'];
      }
      ?>
      new Chart(divisionCtx, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($division_labels) ?>,
          datasets: [{
            data: <?= json_encode($division_data) ?>,
            backgroundColor: [
              '#ff6b35', '#ff8e35', '#ffa235', '#ffb835', '#ffce35',
              '#ffe435', '#fffa35', '#e4ff35', '#ceff35', '#b8ff35'
            ],
            borderWidth: 2,
            borderColor: 'var(--bg-secondary)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                color: 'var(--text-primary)',
                font: {
                  family: 'Inter'
                }
              }
            }
          }
        }
      });

      // Payroll Chart
      const payrollCtx = document.getElementById('payrollChart').getContext('2d');
      <?php
      $payroll_months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      $payroll_data = array_fill(0, 12, 0);
      mysqli_data_seek($payroll_chart_data, 0);
      while ($row = mysqli_fetch_assoc($payroll_chart_data)) {
        $payroll_data[$row['month'] - 1] = $row['total'];
      }
      ?>
      new Chart(payrollCtx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($payroll_months) ?>,
          datasets: [{
            label: 'Total Payroll (Rp)',
            data: <?= json_encode($payroll_data) ?>,
            backgroundColor: '#ff6b35',
            borderColor: '#ff8e35',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                color: 'var(--text-secondary)',
                callback: function(value) {
                  if (value >= 1000000) {
                    return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                  }
                  return 'Rp ' + value;
                }
              }
            },
            x: {
              ticks: {
                color: 'var(--text-secondary)'
              }
            }
          },
          plugins: {
            legend: {
              labels: {
                color: 'var(--text-primary)'
              }
            }
          }
        }
      });

      // Employee Status Chart
      const employeeStatusCtx = document.getElementById('employeeStatusChart').getContext('2d');
      new Chart(employeeStatusCtx, {
        type: 'pie',
        data: {
          labels: ['Aktif', 'Tidak Aktif'],
          datasets: [{
            data: [<?= $active_users ?>, <?= $total_users - $active_users ?>],
            backgroundColor: ['#16a34a', '#dc2626'],
            borderWidth: 2,
            borderColor: 'var(--bg-secondary)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: 'var(--text-primary)',
                font: {
                  family: 'Inter'
                }
              }
            }
          }
        }
      });

      // Attendance Chart
      const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
      <?php
      $attendance_days = [];
      $attendance_counts = [];
      mysqli_data_seek($attendance_chart_data, 0);
      while ($row = mysqli_fetch_assoc($attendance_chart_data)) {
        $attendance_days[] = date('d M', strtotime($row['day']));
        $attendance_counts[] = $row['count'];
      }
      $attendance_days = array_reverse($attendance_days);
      $attendance_counts = array_reverse($attendance_counts);
      ?>
      new Chart(attendanceCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($attendance_days) ?>,
          datasets: [{
            label: 'Jumlah Absensi',
            data: <?= json_encode($attendance_counts) ?>,
            borderColor: '#ff6b35',
            backgroundColor: 'rgba(255, 107, 53, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                color: 'var(--text-secondary)'
              }
            },
            x: {
              ticks: {
                color: 'var(--text-secondary)'
              }
            }
          },
          plugins: {
            legend: {
              labels: {
                color: 'var(--text-primary)'
              }
            }
          }
        }
      });
    });
  </script>
</body>
</html>