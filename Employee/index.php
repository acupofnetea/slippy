<?php
session_start();
// Set timezone ke Indonesia
date_default_timezone_set('Asia/Jakarta');
include '../connection.php';
// Cek apakah user sudah login dan role-nya employee
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'Employee') {
    header("Location: ../signin.php");
    exit;
}
// Pastikan $user_id selalu didefinisikan
$user_id = $_SESSION['user_id'];
// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['clock_in'])) {
        $current_time = date('H:i:s');
        $today = date('Y-m-d');
        $mode = $_POST['mode']; // Ambil mode dari form
        // Cek apakah sudah clock in hari ini
        $check_attendance = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id = $user_id AND date = '$today'");
        if (mysqli_num_rows($check_attendance) == 0) {
            $insert_attendance = mysqli_query($conn, "INSERT INTO attendance (user_id, date, clock_in, status, mode) 
                                                     VALUES ($user_id, '$today', '$current_time', 'Present', '$mode')");
            if ($insert_attendance) {
                $success = "Clock in berhasil! Mode: $mode";
            } else {
                $error = "Gagal clock in: " . mysqli_error($conn);
            }
        } else {
            $error = "Anda sudah clock in hari ini!";
        }
    }
    if (isset($_POST['clock_out'])) {
        $current_time = date('H:i:s');
        $today = date('Y-m-d');
        $update_attendance = mysqli_query($conn, "UPDATE attendance SET clock_out = '$current_time' 
                                                 WHERE user_id = $user_id AND date = '$today'");
        if ($update_attendance) {
            $success = "Clock out berhasil!";
        } else {
            $error = "Gagal clock out: " . mysqli_error($conn);
        }
    }
    if (isset($_POST['submit_leave'])) {
        $leave_type_id = $_POST['leave_type_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        // Validasi tanggal
        if ($start_date > $end_date) {
            $error = "Tanggal selesai tidak boleh sebelum tanggal mulai!";
        } else {
            // Handle medical certificate upload untuk cuti sakit
            $medical_certificate = null;
            if ($leave_type_id == 2 && isset($_FILES['medical_certificate']) && $_FILES['medical_certificate']['error'] == 0) {
                $medical_certificate_name = time() . "_medical_" . basename($_FILES['medical_certificate']['name']);
                $target = "../uploads/" . $medical_certificate_name;
                if (move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $target)) {
                    $medical_certificate = $medical_certificate_name;
                } else {
                    $error = "Gagal mengupload surat dokter!";
                }
            } elseif ($leave_type_id == 2) {
                $error = "Surat dokter wajib untuk cuti sakit!";
            }
            if (!isset($error)) {
                // Cari leader_id dari divisi yang sama
                $get_leader = mysqli_query($conn, "SELECT u.id FROM users u 
                                                WHERE u.role_id = 3 AND u.division_id = (
                                                    SELECT division_id FROM users WHERE id = $user_id
                                                ) LIMIT 1");
                $leader_id = null;
                if ($get_leader && mysqli_num_rows($get_leader) > 0) {
                    $leader_data = mysqli_fetch_assoc($get_leader);
                    $leader_id = $leader_data['id'];
                }
                
                // PERBAIKAN: Handle nilai NULL untuk leader_id
                if ($leader_id) {
                    if ($medical_certificate) {
                        $insert_leave = mysqli_query($conn, "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, medical_certificate, leader_id) 
                                                            VALUES ($user_id, $leave_type_id, '$start_date', '$end_date', '$reason', '$medical_certificate', $leader_id)");
                    } else {
                        $insert_leave = mysqli_query($conn, "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, leader_id) 
                                                            VALUES ($user_id, $leave_type_id, '$start_date', '$end_date', '$reason', $leader_id)");
                    }
                } else {
                    if ($medical_certificate) {
                        $insert_leave = mysqli_query($conn, "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, medical_certificate) 
                                                            VALUES ($user_id, $leave_type_id, '$start_date', '$end_date', '$reason', '$medical_certificate')");
                    } else {
                        $insert_leave = mysqli_query($conn, "INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason) 
                                                            VALUES ($user_id, $leave_type_id, '$start_date', '$end_date', '$reason')");
                    }
                }
                
                if ($insert_leave) {
                    $success = "Pengajuan cuti berhasil dikirim! Menunggu persetujuan leader.";
                } else {
                    $error = "Gagal mengajukan cuti: " . mysqli_error($conn);
                }
            }
        }
    }
    if (isset($_POST['submit_overtime'])) {
        $date = $_POST['date'];
        $hours = $_POST['hours'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        // Handle proof file upload for overtime
        $proof_file = null;
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
            $proof_file_name = time() . "_overtime_" . basename($_FILES['proof_file']['name']);
            $target = "../uploads/" . $proof_file_name;
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target)) {
                $proof_file = $proof_file_name;
            } else {
                $error = "Gagal mengupload bukti lembur!";
            }
        } else {
            $error = "Bukti lembur wajib diupload!";
        }
        if (!isset($error)) {
            // Cari leader_id dari divisi yang sama
            $get_leader = mysqli_query($conn, "SELECT u.id FROM users u 
                                            WHERE u.role_id = 3 AND u.division_id = (
                                                SELECT division_id FROM users WHERE id = $user_id
                                            ) LIMIT 1");
            $leader_id = null;
            if ($get_leader && mysqli_num_rows($get_leader) > 0) {
                $leader_data = mysqli_fetch_assoc($get_leader);
                $leader_id = $leader_data['id'];
            }
            
            // PERBAIKAN: Handle nilai NULL untuk leader_id
            if ($leader_id) {
                $insert_overtime = mysqli_query($conn, "INSERT INTO overtime (user_id, date, hours, description, proof_file, leader_id) 
                                                      VALUES ($user_id, '$date', $hours, '$reason', '$proof_file', $leader_id)");
            } else {
                $insert_overtime = mysqli_query($conn, "INSERT INTO overtime (user_id, date, hours, description, proof_file) 
                                                      VALUES ($user_id, '$date', $hours, '$reason', '$proof_file')");
            }
            
            if ($insert_overtime) {
                $success = "Pengajuan lembur berhasil dikirim! Menunggu persetujuan leader.";
            } else {
                $error = "Gagal mengajukan lembur: " . mysqli_error($conn);
            }
        }
    }
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        // Handle photo upload
        $photo_sql = "";
        if (!empty($_FILES['photo']['name'])) {
            $photo_name = time() . "_" . basename($_FILES['photo']['name']);
            $target = "../uploads/" . $photo_name;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $photo_sql = ", photo = '$photo_name'";
                $_SESSION['photo'] = $photo_name; // Update session photo
            } else {
                $error = "Gagal mengupload foto profil!";
            }
        }
        // Handle password update
        $password_sql = "";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_sql = ", password = '$password'";
        }
        // Build the SQL query
        $sql = "UPDATE users SET 
                name = '$name', 
                username = '$username', 
                email = '$email'
                $photo_sql $password_sql 
                WHERE id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $name; // Update session name
            $success = "Profil berhasil diperbarui!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
// Get current user data
$current_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.*, d.division_name FROM users u LEFT JOIN divisions d ON u.division_id = d.id WHERE u.id = $user_id"));
// Get leave types for dropdown
$leave_types = mysqli_query($conn, "SELECT * FROM leave_types");
// Get leave requests dengan join ke leave_types
$leave_requests = mysqli_query($conn, "SELECT lr.*, lt.type_name, lt.id as leave_type_id
                                     FROM leave_requests lr 
                                     JOIN leave_types lt ON lr.leave_type_id = lt.id 
                                     WHERE lr.user_id = $user_id 
                                     ORDER BY lr.start_date DESC");
// Get overtime requests
$overtime_requests = mysqli_query($conn, "SELECT * FROM overtime WHERE user_id = $user_id ORDER BY date DESC");
// Get payroll data - PERBAIKAN: Ambil semua field termasuk yang baru
$payroll_data = mysqli_query($conn, "SELECT * FROM payroll WHERE user_id = $user_id ORDER BY pay_date DESC");
// Get today's attendance
$today = date('Y-m-d');
$today_attendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM attendance WHERE user_id = $user_id AND date = '$today'"));

// --- FILTER LOGIC FOR ATTENDANCE ---
$attendance_start = $_GET['attendance_start'] ?? date('Y-m-01'); // Default ke awal bulan ini
$attendance_end = $_GET['attendance_end'] ?? date('Y-m-t');   // Default ke akhir bulan ini
// Validasi tanggal (opsional, tapi baik untuk keamanan)
// Kita asumsikan input dari form date HTML sudah valid
$attendance_filter_sql = " AND date BETWEEN '$attendance_start' AND '$attendance_end'";

// Query data default untuk attendance dengan filter
$attendance_query = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id = $user_id $attendance_filter_sql ORDER BY date DESC LIMIT 30");
// Statistics
$total_attendance = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE user_id = $user_id")->fetch_assoc()['total'];
$approved_leaves = mysqli_query($conn, "SELECT COUNT(*) as total FROM leave_requests WHERE user_id = $user_id AND status = 'Approved'")->fetch_assoc()['total'];
$pending_leaves = mysqli_query($conn, "SELECT COUNT(*) as total FROM leave_requests WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['total'];
$total_overtime = mysqli_query($conn, "SELECT SUM(hours) as total FROM overtime WHERE user_id = $user_id AND status = 'Approved'")->fetch_assoc()['total'];
$total_overtime = $total_overtime ? $total_overtime : 0;
// Hitung sisa cuti tahunan
function getRemainingLeave($conn, $user_id) {
    $current_year = date('Y');
    $used_leave = mysqli_query($conn, "SELECT SUM(DATEDIFF(end_date, start_date) + 1) as total_days 
                                     FROM leave_requests 
                                     WHERE user_id = $user_id 
                                     AND YEAR(start_date) = $current_year 
                                     AND status = 'Approved' 
                                     AND leave_type_id = 1");
    if (!$used_leave) {
        // Handle error if query fails
        error_log("Query getRemainingLeave failed for user_id: $user_id. Error: " . mysqli_error($conn));
        return 12; // Return default max if query fails
    }
    $row = mysqli_fetch_assoc($used_leave);
    $used_days = $row['total_days'] ?: 0;
    $max_leave = 12; // Sesuai dengan max_days di leave_types untuk cuti tahunan
    return $max_leave - $used_days;
}
$remaining_leave = getRemainingLeave($conn, $user_id);
// Format tanggal Indonesia
function indonesianDate($date) {
    $months = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    $days = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $english_day = date('l', strtotime($date));
    $english_month = date('F', strtotime($date));
    $indonesian_day = $days[$english_day] ?? $english_day;
    $indonesian_month = $months[$english_month] ?? $english_month;
    return $indonesian_day . ', ' . date('d', strtotime($date)) . ' ' . $indonesian_month . ' ' . date('Y', strtotime($date));
}
// === CHART DATA ===
$chart_months = [];
$chart_attendance = [];
$chart_overtime = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $chart_months[] = $month_label;
    $att = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE user_id = $user_id AND DATE_FORMAT(date, '%Y-%m') = '$month'"));
    $ot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(hours), 0) as hours FROM overtime WHERE user_id = $user_id AND status = 'Approved' AND DATE_FORMAT(date, '%Y-%m') = '$month'"));
    $chart_attendance[] = (int)$att['count'];
    $chart_overtime[] = (float)$ot['hours'];
}
$leave_status = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM leave_requests WHERE user_id = $user_id GROUP BY status");
$leave_labels = ['Pending', 'Approved', 'Rejected'];
$leave_data = [0, 0, 0];
while ($row = mysqli_fetch_assoc($leave_status)) {
    if ($row['status'] == 'Pending') $leave_data[0] = $row['count'];
    if ($row['status'] == 'Approved') $leave_data[1] = $row['count'];
    if ($row['status'] == 'Rejected') $leave_data[2] = $row['count'];
}
// === END CHART DATA ===
// Query data default untuk overtime (jika diperlukan di tempat lain)
$overtime_requests = mysqli_query($conn, "SELECT * FROM overtime WHERE user_id = $user_id ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Employee | Slippy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Chart.js -->
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
    @font-face {
      font-family: 'SF Pro Display';
      src: url('https://fonts.cdnfonts.com/css/sf-pro-display') format('woff2');
    }
    body {
      font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
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
    }
    .nav-item:hover, .nav-item.active {
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
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .card-header h2 {
      font-size: 1.3rem;
      font-weight: 600;
    }
    /* Tables */
    .table-container {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border-primary);
    }
    th {
      font-weight: 600;
      color: var(--text-secondary);
      font-size: 0.9rem;
    }
    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    .status-present {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
    }
    .status-absent {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
    }
    .status-pending {
      background: rgba(245, 158, 11, 0.1);
      color: #d97706;
    }
    .status-approved {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
    }
    .status-rejected {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
    }
    .status-paid {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
    }
    .status-unpaid {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
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
    }
    .btn-primary {
      background: var(--accent-primary);
      color: white;
      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }
    .btn-outline {
      border: 1px solid var(--border-secondary);
      color: var(--text-primary);
      background: var(--glass);
    }
    .btn-outline:hover {
      border-color: var(--accent-secondary);
      color: var(--accent-secondary);
    }
    .btn-success {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.2);
    }
    .btn-success:hover {
      background: rgba(34, 197, 94, 0.2);
    }
    .btn-warning {
      background: rgba(245, 158, 11, 0.1);
      color: #d97706;
      border: 1px solid rgba(245, 158, 11, 0.2);
    }
    .btn-warning:hover {
      background: rgba(245, 158, 11, 0.2);
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
    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      background: var(--card-bg);
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      color: var(--text-primary);
      transition: var(--transition);
      outline: none;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
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
    /* Attendance Card */
    .attendance-card {
      background: var(--glass);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: 2rem;
      text-align: center;
      backdrop-filter: blur(10px);
    }
    .time-display {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 1rem;
      background: var(--accent-primary);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .date-display {
      color: var(--text-secondary);
      margin-bottom: 2rem;
    }
    .attendance-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
    }
    /* Notifications */
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
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
    .theme-toggle svg {
      width: 20px;
      height: 20px;
      color: var(--text-primary);
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
      .main-content {
        padding: 1rem;
      }
      .attendance-buttons {
        flex-direction: column;
      }
      /* Mobile Menu Button */
      #menu-toggle {
        display: block;
        position: fixed;
        top: 1rem;
        left: 1rem;
        background: var(--accent-primary);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1.2rem;
        z-index: 200;
      }
    }
    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in {
      animation: fadeIn 0.5s ease-out;
    }
    /* Salary Slip - PERBAIKAN BESAR */
    .salary-slip {
      background: white !important;
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: var(--shadow-lg);
      max-width: 800px;
      margin: 0 auto;
      color: #333 !important;
      border: 1px solid #e2e8f0;
    }
    .salary-slip * {
      color: #333 !important;
    }
    .salary-header {
      text-align: center;
      margin-bottom: 2rem;
      border-bottom: 2px solid var(--accent-secondary);
      padding-bottom: 1rem;
    }
    .salary-header h2 {
      color: #1e293b !important;
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
    }
    .salary-header p {
      color: #64748b !important;
      margin-bottom: 0.25rem;
    }
    .salary-details {
      display: grid;
      gap: 1rem;
    }
    .salary-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid #e2e8f0;
    }
    .salary-row:last-child {
      border-bottom: none;
    }
    .salary-total {
      font-weight: 700;
      font-size: 1.3rem;
      color: var(--accent-secondary) !important;
      border-top: 2px solid #cbd5e1;
      margin-top: 1rem;
      padding-top: 1rem;
      background-color: #f8fafc;
      padding: 1rem;
      border-radius: var(--radius-sm);
    }
    .salary-section {
      margin: 1.5rem 0;
    }
    .salary-section h3 {
      color: #1e293b !important;
      font-size: 1.2rem;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    /* Medical Certificate */
    .medical-certificate-container {
      display: none;
      margin-top: 1rem;
      padding: 1rem;
      background: var(--card-bg);
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-secondary);
    }
    .medical-certificate-preview {
      max-width: 200px;
      max-height: 200px;
      margin-top: 1rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-secondary);
    }
    /* Overtime Proof */
    .overtime-proof-container {
      display: block; /* Selalu tampilkan karena wajib */
      margin-top: 1rem;
      padding: 1rem;
      background: var(--card-bg);
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-secondary);
    }
    .overtime-proof-preview {
      max-width: 200px;
      max-height: 200px;
      margin-top: 1rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-secondary);
    }
    /* Tab Content */
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    /* Chart Styling */
    canvas {
      max-height: 200px;
    }
    /* Filter Row Styling */
    .filter-row {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .filter-label {
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .filter-input, .filter-select {
      padding: 0.5rem;
      border: 1px solid var(--border-secondary);
      border-radius: var(--radius-sm);
      background: var(--card-bg);
      color: var(--text-primary);
    }
    .filter-btn {
      padding: 0.5rem 1rem;
      background: var(--accent-primary);
      color: white;
      border: none;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 0.9rem;
      align-self: flex-end;
    }
    /* Salary Modal - PERBAIKAN */
    .salary-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }
    .salary-modal-content {
      background: white;
      border-radius: var(--radius-lg);
      padding: 0;
      max-width: 900px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
    }
    .salary-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0;
      padding: 1.5rem 2rem;
      border-bottom: 1px solid #e2e8f0;
      background: var(--accent-primary);
      color: white;
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }
    .salary-modal-header h2 {
      margin: 0;
      color: white !important;
      font-size: 1.5rem;
    }
    .salary-modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: white !important;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: var(--transition);
    }
    .salary-modal-close:hover {
      background: rgba(255,255,255,0.2);
    }
    .payment-proof-section {
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border-secondary);
    }
    .payment-proof-image {
      max-width: 100%;
      max-height: 300px;
      margin-top: 0.5rem;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-secondary);
    }
    /* Status in salary slip */
    .salary-status {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }
    .status-paid {
      background: rgba(34, 197, 94, 0.1);
      color: #16a34a !important;
    }
    .status-unpaid {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626 !important;
    }
  </style>
</head>
<body>
<!-- Mobile Menu Toggle -->
<button id="menu-toggle" style="display: none;" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</button>
<div class="dashboard-container">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo">Sli<span>ppy</span></div>
      <div class="user-info">
        <img src="../uploads/<?= $current_user['photo'] ?? 'default.png' ?>" alt="Profile" class="user-avatar" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNGRjZCMzUiLz4KPHBhdGggZD0iTTIwIDIyQzIyLjIwOTEgMjIgMjQgMjAuMjA5MSAyNCAxOEMyNCAxNS43OTA5IDIyLjIwOTEgMTQgMjAgMTRDMTcuNzkwOSAxNCAxNiAxNS43OTA5IDE2IDE4QzE2IDIwLjIwOTEgMTcuNzkwOSAyMiAyMCAyMloiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0yMCAyNkMxNS41ODIyIDI2IDEyIDI4LjIzODUgMTIgMzFDMjggMzEgMjggMzEgMjggMzFDMjggMjguMjM4NSAyNC40MTc4IDI2IDIwIDI2WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+'">
        <div class="user-details">
          <h4><?= htmlspecialchars($current_user['name']) ?></h4>
          <p><?= htmlspecialchars($current_user['division_name'] ?? 'No Division') ?></p>
        </div>
      </div>
    </div>
    <nav class="nav-links">
      <a href="#" class="nav-item active" onclick="switchTab('dashboard')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Dashboard
      </a>
      <a href="#" class="nav-item" onclick="switchTab('attendance')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Absensi
      </a>
      <a href="#" class="nav-item" onclick="switchTab('leave')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Cuti
      </a>
      <a href="#" class="nav-item" onclick="switchTab('overtime')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Lembur
      </a>
      <a href="#" class="nav-item" onclick="switchTab('salary')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Slip Gaji
      </a>
      <a href="#" class="nav-item" onclick="switchTab('profile')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Profil
      </a>
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
      <h1>Dashboard Employee</h1>
      <div class="header-actions">
        <button class="theme-toggle" aria-label="Toggle theme">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>
      </div>
    </div>
    <?php if(isset($success)): ?>
      <div class="alert alert-success fade-in">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <?= $success ?>
      </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
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
      <!-- CHARTS -->
      <div class="stats-grid">
        <div class="stat-card fade-in">
          <canvas id="attendanceChart" height="100"></canvas>
        </div>
        <div class="stat-card fade-in">
          <canvas id="overtimeChart" height="100"></canvas>
        </div>
        <div class="stat-card fade-in">
          <canvas id="leaveChart" height="100"></canvas>
        </div>
      </div>
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Absensi Hari Ini</h2>
        </div>
        <?php if($today_attendance): ?>
          <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 1.2rem; margin-bottom: 1rem;">Status: <span class="status-badge status-present">Sudah Clock In</span></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; max-width: 500px; margin: 0 auto;">
              <div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">Clock In</div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?= date('H:i', strtotime($today_attendance['clock_in'])) ?></div>
              </div>
              <div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">Clock Out</div>
                <div style="font-size: 1.5rem; font-weight: 600;">
                  <?= $today_attendance['clock_out'] ? date('H:i', strtotime($today_attendance['clock_out'])) : '--:--' ?>
                </div>
              </div>
              <div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">Mode Kerja</div>
                <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem;">
                  <span class="status-badge status-present"><?= $today_attendance['mode'] ?></span>
                </div>
              </div>
            </div>
            <?php if(!$today_attendance['clock_out']): ?>
              <form method="POST" style="margin-top: 2rem;">
                <button type="submit" name="clock_out" class="btn btn-primary">Clock Out Sekarang</button>
              </form>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 1.2rem; margin-bottom: 2rem;">Belum melakukan clock in hari ini</div>
            <form method="POST">
              <div class="form-group" style="margin-bottom: 1rem; max-width: 300px; margin: 0 auto 2rem auto;">
                <label class="form-label">Mode Kerja</label>
                <select name="mode" class="form-select" required>
                  <option value="WFO">WFO (Work From Office)</option>
                  <option value="WFH">WFH (Work From Home)</option>
                  <option value="Hybrid">Hybrid</option>
                </select>
              </div>
              <button type="submit" name="clock_in" class="btn btn-primary">Clock In Sekarang</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Riwayat Absensi Terbaru</h2>
          <form method="GET" class="filter-row">
            <input type="hidden" name="tab" value="dashboard"> <!-- Pastikan tetap di tab dashboard -->
            <div class="filter-group">
              <label class="filter-label">Dari</label>
              <input type="date" name="attendance_start" class="filter-input" value="<?= $attendance_start ?>" required>
            </div>
            <div class="filter-group">
              <label class="filter-label">Sampai</label>
              <input type="date" name="attendance_end" class="filter-input" value="<?= $attendance_end ?>" required>
            </div>
            <button type="submit" class="filter-btn">Filter</button>
            <a href="?tab=dashboard" class="btn btn-outline btn-sm">Reset</a>
          </form>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Status</th>
                <th>Mode</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Gunakan hasil query yang sudah difilter
              while($attendance = mysqli_fetch_assoc($attendance_query)):
              ?>
              <tr>
                <td><?= indonesianDate($attendance['date']) ?></td>
                <td><?= date('H:i', strtotime($attendance['clock_in'])) ?></td>
                <td><?= $attendance['clock_out'] ? date('H:i', strtotime($attendance['clock_out'])) : '--:--' ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($attendance['status']) ?>">
                    <?= $attendance['status'] ?>
                  </span>
                </td>
                <td><?= $attendance['mode'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Attendance Tab -->
    <div class="tab-content" id="attendance">
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Absensi</h2>
        </div>
        <div class="attendance-card">
          <div class="time-display" id="current-time"><?= date('H:i:s') ?></div>
          <div class="date-display" id="current-date"><?= indonesianDate(date('Y-m-d')) ?></div>
          <?php if($today_attendance): ?>
            <div style="margin-bottom: 2rem;">
              <div style="font-size: 1.2rem; margin-bottom: 1rem;">
                Status: <span class="status-badge status-present">Sudah Clock In</span>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; max-width: 500px; margin: 0 auto;">
                <div>
                  <div style="font-size: 0.9rem; color: var(--text-secondary);">Clock In</div>
                  <div style="font-size: 1.5rem; font-weight: 600;"><?= date('H:i', strtotime($today_attendance['clock_in'])) ?></div>
                </div>
                <div>
                  <div style="font-size: 0.9rem; color: var(--text-secondary);">Clock Out</div>
                  <div style="font-size: 1.5rem; font-weight: 600;">
                    <?= $today_attendance['clock_out'] ? date('H:i', strtotime($today_attendance['clock_out'])) : '--:--' ?>
                  </div>
                </div>
                <div>
                  <div style="font-size: 0.9rem; color: var(--text-secondary);">Mode Kerja</div>
                  <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem;">
                    <span class="status-badge status-present"><?= $today_attendance['mode'] ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php if(!$today_attendance['clock_out']): ?>
              <form method="POST">
                <button type="submit" name="clock_out" class="btn btn-primary">Clock Out Sekarang</button>
              </form>
            <?php else: ?>
              <div style="color: var(--text-secondary);">Anda sudah menyelesaikan absensi hari ini</div>
            <?php endif; ?>
          <?php else: ?>
            <div style="margin-bottom: 2rem;">
              <div style="font-size: 1.2rem; margin-bottom: 2rem;">
                Status: <span class="status-badge status-absent">Belum Clock In</span>
              </div>
            </div>
            <form method="POST">
              <div class="form-group" style="margin-bottom: 1rem; max-width: 300px; margin: 0 auto 2rem auto;">
                <label class="form-label">Mode Kerja</label>
                <select name="mode" class="form-select" required>
                  <option value="WFO">WFO (Work From Office)</option>
                  <option value="WFH">WFH (Work From Home)</option>
                  <option value="Hybrid">Hybrid</option>
                </select>
              </div>
              <button type="submit" name="clock_in" class="btn btn-primary">Clock In Sekarang</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Riwayat Absensi</h2>
          <form method="GET" class="filter-row">
            <input type="hidden" name="tab" value="attendance"> <!-- Pastikan tetap di tab attendance -->
            <div class="filter-group">
              <label class="filter-label">Dari</label>
              <input type="date" name="attendance_start" class="filter-input" value="<?= $attendance_start ?>" required>
            </div>
            <div class="filter-group">
              <label class="filter-label">Sampai</label>
              <input type="date" name="attendance_end" class="filter-input" value="<?= $attendance_end ?>" required>
            </div>
            <button type="submit" class="filter-btn">Filter</button>
            <a href="?tab=attendance" class="btn btn-outline btn-sm">Reset</a>
          </form>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Status</th>
                <th>Mode</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Reset pointer untuk query difilter di tab ini juga
              mysqli_data_seek($attendance_query, 0);
              while($attendance = mysqli_fetch_assoc($attendance_query)):
              ?>
              <tr>
                <td><?= indonesianDate($attendance['date']) ?></td>
                <td><?= date('H:i', strtotime($attendance['clock_in'])) ?></td>
                <td><?= $attendance['clock_out'] ? date('H:i', strtotime($attendance['clock_out'])) : '--:--' ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($attendance['status']) ?>">
                    <?= $attendance['status'] ?>
                  </span>
                </td>
                <td><?= $attendance['mode'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Leave Tab -->
    <div class="tab-content" id="leave">
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Ajukan Cuti</h2>
          <div style="color: var(--text-secondary); font-size: 0.9rem;">
            Sisa Cuti Tahunan: <strong><?= $remaining_leave ?> hari</strong>
          </div>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Jenis Cuti</label>
              <select name="leave_type_id" class="form-select" id="leave_type" required onchange="toggleMedicalCertificate()">
                <option value="">Pilih Jenis Cuti</option>
                <?php
                mysqli_data_seek($leave_types, 0);
                while($leave_type = mysqli_fetch_assoc($leave_types)):
                ?>
                  <option value="<?= $leave_type['id'] ?>"><?= htmlspecialchars($leave_type['type_name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="start_date" class="form-input" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="end_date" class="form-input" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label class="form-label">Alasan</label>
              <textarea name="reason" class="form-textarea" placeholder="Jelaskan alasan cuti..." required></textarea>
            </div>
          </div>
          <!-- Medical Certificate Section -->
          <div class="medical-certificate-container" id="medicalCertificateContainer">
            <div class="form-group">
              <label class="form-label">Surat Dokter (Medical Certificate)</label>
              <div class="file-upload" onclick="document.getElementById('medical_certificate').click()">
                <div style="margin-bottom: 1rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <div style="color: var(--text-secondary);">Klik untuk upload surat dokter</div>
                <input type="file" name="medical_certificate" id="medical_certificate" accept="image/*,.pdf,.doc,.docx" style="display: none;" onchange="previewMedicalCertificate(this)">
              </div>
              <div class="preview-container">
                <img id="medical-certificate-preview" class="medical-certificate-preview" style="display: none;">
                <div id="medical-certificate-filename" style="margin-top: 0.5rem; color: var(--text-secondary);"></div>
              </div>
              <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                Format yang didukung: JPG, PNG, PDF, DOC (Maks. 5MB)
              </div>
            </div>
          </div>
          <button type="submit" name="submit_leave" class="btn btn-primary">Ajukan Cuti</button>
        </form>
      </div>
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Riwayat Pengajuan Cuti</h2>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Jenis</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Surat Dokter</th>
              </tr>
            </thead>
            <tbody>
              <?php
              mysqli_data_seek($leave_requests, 0);
              while($leave = mysqli_fetch_assoc($leave_requests)):
              ?>
              <tr>
                <td><?= htmlspecialchars($leave['type_name']) ?></td>
                <td><?= indonesianDate($leave['start_date']) ?></td>
                <td><?= indonesianDate($leave['end_date']) ?></td>
                <td><?= htmlspecialchars($leave['reason']) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($leave['status']) ?>">
                    <?= $leave['status'] ?>
                  </span>
                </td>
                <td>
                  <?php if($leave['medical_certificate']): ?>
                    <a href="../uploads/<?= $leave['medical_certificate'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                  <?php else: ?>
                    <span style="color: var(--text-secondary);">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Overtime Tab -->
    <div class="tab-content" id="overtime">
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Ajukan Lembur</h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Tanggal</label>
              <input type="date" name="date" class="form-input" required max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Jam Lembur</label>
              <input type="number" name="hours" class="form-input" step="0.5" min="0.5" max="12" placeholder="Contoh: 2.5" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label class="form-label">Alasan Lembur</label>
              <textarea name="reason" class="form-textarea" placeholder="Jelaskan alasan dan detail pekerjaan lembur..." required></textarea>
            </div>
          </div>
          <!-- Overtime Proof File Section -->
          <div class="overtime-proof-container" id="overtimeProofContainer">
            <div class="form-group">
              <label class="form-label">Bukti Lembur *</label>
              <div class="file-upload" onclick="document.getElementById('proof_file').click()">
                <div style="margin-bottom: 1rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                </div>
                <div style="color: var(--text-secondary);">Klik untuk upload bukti lembur (wajib)</div>
                <input type="file" name="proof_file" id="proof_file" accept="image/*,.pdf,.doc,.docx" style="display: none;" onchange="previewOvertimeProof(this)" required>
              </div>
              <div class="preview-container">
                <img id="overtime-proof-preview" class="overtime-proof-preview" style="display: none;">
                <div id="overtime-proof-filename" style="margin-top: 0.5rem; color: var(--text-secondary);"></div>
              </div>
              <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                Format yang didukung: JPG, PNG, PDF, DOC (Maks. 5MB)
              </div>
            </div>
          </div>
          <button type="submit" name="submit_overtime" class="btn btn-primary">Ajukan Lembur</button>
        </form>
      </div>
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Riwayat Pengajuan Lembur</h2>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Bukti</th>
              </tr>
            </thead>
            <tbody>
              <?php
              mysqli_data_seek($overtime_requests, 0);
              while($overtime = mysqli_fetch_assoc($overtime_requests)):
              ?>
              <tr>
                <td><?= indonesianDate($overtime['date']) ?></td>
                <td><?= $overtime['hours'] ?> jam</td>
                <td><?= htmlspecialchars($overtime['description']) ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($overtime['status']) ?>">
                    <?= $overtime['status'] ?>
                  </span>
                </td>
                <td>
                  <?php if($overtime['proof_file']): ?>
                    <a href="../uploads/<?= $overtime['proof_file'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                  <?php else: ?>
                    <span style="color: var(--text-secondary);">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Salary Tab -->
    <div class="tab-content" id="salary">
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Slip Gaji</h2>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Periode</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Lembur</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $payrolls = [];
              while($payroll = mysqli_fetch_assoc($payroll_data)) {
                  $payrolls[] = $payroll;
              }
              if (count($payrolls) > 0) {
                  foreach($payrolls as $payroll):
              ?>
              <tr>
                <td><?= date('F Y', strtotime($payroll['pay_date'])) ?></td>
                <td>Rp <?= number_format($payroll['base_salary'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($payroll['allowance'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($payroll['overtime_pay'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($payroll['total_salary'], 0, ',', '.') ?></td>
                <td>
                  <span class="status-badge status-<?= strtolower($payroll['payment_status']) ?>">
                    <?= $payroll['payment_status'] ?>
                  </span>
                </td>
                <td>
                  <button class="btn btn-outline btn-sm"
                          onclick='viewSalarySlip(<?= json_encode($payroll) ?>)'>Lihat Detail</button>
                </td>
              </tr>
              <?php endforeach; 
              } else { ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                    Belum ada data gaji
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Profile Tab -->
    <div class="tab-content" id="profile">
      <div class="content-card fade-in">
        <div class="card-header">
          <h2>Profil Saya</h2>
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
              <label class="form-label">Divisi</label>
              <input type="text" class="form-input" value="<?= htmlspecialchars($current_user['division_name'] ?? 'Tidak ada') ?>" disabled>
            </div>
            <div class="form-group">
              <label class="form-label">Password Baru (Opsional)</label>
              <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah">
            </div>
            <div class="form-group">
              <label class="form-label">Role</label>
              <input type="text" class="form-input" value="Employee" disabled>
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
  </div>
</div>
<!-- Salary Slip Modal -->
<div id="salaryModal" class="salary-modal">
  <div class="salary-modal-content">
    <div class="salary-modal-header">
      <h2>Detail Slip Gaji</h2>
      <button class="salary-modal-close" onclick="closeSalaryModal()">&times;</button>
    </div>
    <div id="salaryModalContent" style="padding: 2rem;">
      <!-- Content will be loaded here -->
    </div>
  </div>
</div>
<script>
  // Simple Tab Navigation Function
  function switchTab(tabName) {
    // Update URL dengan parameter tab tanpa reload halaman
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    // Pastikan filter tanggal ikut dalam URL
    const startInput = document.querySelector('input[name="attendance_start"]');
    const endInput = document.querySelector('input[name="attendance_end"]');
    if (startInput && endInput) {
        url.searchParams.set('attendance_start', startInput.value);
        url.searchParams.set('attendance_end', endInput.value);
    }
    window.history.pushState({}, '', url);

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
    event.currentTarget.classList.add('active');
    // Close sidebar on mobile
    if (window.innerWidth <= 1024) {
      document.querySelector('.sidebar').classList.remove('active');
    }
  }

  // Set tab aktif berdasarkan URL parameter
  function setActiveTabFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'dashboard';
    // Isi input filter dari URL jika ada
    const start = urlParams.get('attendance_start');
    const end = urlParams.get('attendance_end');
    if (start) document.querySelector('input[name="attendance_start"]').value = start;
    if (end) document.querySelector('input[name="attendance_end"]').value = end;

    switchTab(tab);
  }

  // Handle browser back/forward buttons
  window.addEventListener('popstate', setActiveTabFromURL);

  // Set tab aktif saat halaman dimuat
  document.addEventListener('DOMContentLoaded', setActiveTabFromURL);

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
  } else {
    html.classList.remove('dark');
  }
  // Update current time dengan format Indonesia
  function updateTime() {
    const now = new Date();
    const options = {
      timeZone: 'Asia/Jakarta',
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    };
    const timeString = now.toLocaleTimeString('id-ID', options);
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
      timeElement.textContent = timeString;
    }
    // Update tanggal Indonesia
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const dayName = days[now.getDay()];
    const date = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();
    const dateString = `${dayName}, ${date} ${monthName} ${year}`;
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
      dateElement.textContent = dateString;
    }
  }
  // Update waktu setiap detik
  setInterval(updateTime, 1000);
  updateTime(); // Panggil sekali saat pertama load
  // Image Preview Function
  function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  }
  // Medical Certificate Functions
  function toggleMedicalCertificate() {
    const leaveType = document.getElementById('leave_type').value;
    const medicalContainer = document.getElementById('medicalCertificateContainer');
    if (leaveType == '2') { // ID 2 = Cuti Sakit
      medicalContainer.style.display = 'block';
    } else {
      medicalContainer.style.display = 'none';
      // Reset file input
      document.getElementById('medical_certificate').value = '';
      document.getElementById('medical-certificate-preview').style.display = 'none';
      document.getElementById('medical-certificate-filename').textContent = '';
    }
  }
  function previewMedicalCertificate(input) {
    const preview = document.getElementById('medical-certificate-preview');
    const filename = document.getElementById('medical-certificate-filename');
    const file = input.files[0];
    if (file) {
      filename.textContent = file.name;
      // Hanya preview untuk file gambar
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    }
  }
  // Overtime Proof Functions
  function previewOvertimeProof(input) {
    const preview = document.getElementById('overtime-proof-preview');
    const filename = document.getElementById('overtime-proof-filename');
    const file = input.files[0];
    if (file) {
      filename.textContent = file.name;
      // Hanya preview untuk file gambar
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    }
  }
  // Salary Slip Functions - PERBAIKAN BESAR
  function viewSalarySlip(payroll) {
    const modalContent = document.getElementById('salaryModalContent');
    
    // PERBAIKAN: Format tanggal dengan benar
    const payDate = payroll.pay_date ? new Date(payroll.pay_date) : new Date();
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const period = `${months[payDate.getMonth()]} ${payDate.getFullYear()}`;
    
    // PERBAIKAN: Hitung total tunjangan dari semua komponen
    const totalAllowance = (parseFloat(payroll.allowance) || 0) + 
                          (parseFloat(payroll.transport_allowance) || 0) + 
                          (parseFloat(payroll.meal_allowance) || 0) + 
                          (parseFloat(payroll.position_allowance) || 0);
    
    // PERBAIKAN: Hitung total potongan
    const totalDeduction = (parseFloat(payroll.deduction) || 0) + 
                          (parseFloat(payroll.bpjs_kes) || 0) + 
                          (parseFloat(payroll.bpjs_tk) || 0) + 
                          (parseFloat(payroll.tax_pph21) || 0);

    // PERBAIKAN: Struktur HTML yang lebih baik untuk slip gaji
    modalContent.innerHTML = `
      <div class="salary-slip">
        <div class="salary-header">
          <h2>SLIP GAJI KARYAWAN</h2>
          <p>PT. Slippy Indonesia</p>
          <p>Periode: ${period}</p>
        </div>
        <div class="salary-details">
          <div class="salary-section">
            <h3>Informasi Karyawan</h3>
            <div class="salary-row">
              <span>Nama</span>
              <span><?= htmlspecialchars($current_user['name']) ?></span>
            </div>
            <div class="salary-row">
              <span>Divisi</span>
              <span><?= htmlspecialchars($current_user['division_name'] ?? '-') ?></span>
            </div>
            <div class="salary-row">
              <span>Jabatan</span>
              <span>Employee</span>
            </div>
          </div>

          <div class="salary-section">
            <h3>Pendapatan</h3>
            <div class="salary-row">
              <span>Gaji Pokok</span>
              <span>Rp ${formatRupiah(payroll.base_salary || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Tunjangan</span>
              <span>Rp ${formatRupiah(totalAllowance)}</span>
            </div>
            <div class="salary-row">
              <span>Lembur</span>
              <span>Rp ${formatRupiah(payroll.overtime_pay || 0)}</span>
            </div>
            <div class="salary-row">
              <span>THR</span>
              <span>Rp ${formatRupiah(payroll.thr || 0)}</span>
            </div>
          </div>

          ${totalAllowance > 0 ? `
          <div class="salary-section">
            <h3>Rincian Tunjangan</h3>
            <div class="salary-row">
              <span>Tunjangan Tetap</span>
              <span>Rp ${formatRupiah(payroll.allowance || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Tunjangan Transport</span>
              <span>Rp ${formatRupiah(payroll.transport_allowance || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Tunjangan Makan</span>
              <span>Rp ${formatRupiah(payroll.meal_allowance || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Tunjangan Jabatan</span>
              <span>Rp ${formatRupiah(payroll.position_allowance || 0)}</span>
            </div>
          </div>
          ` : ''}

          ${totalDeduction > 0 ? `
          <div class="salary-section">
            <h3>Potongan</h3>
            <div class="salary-row">
              <span>BPJS Kesehatan</span>
              <span>- Rp ${formatRupiah(payroll.bpjs_kes || 0)}</span>
            </div>
            <div class="salary-row">
              <span>BPJS Tenaga Kerja</span>
              <span>- Rp ${formatRupiah(payroll.bpjs_tk || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Pajak (PPh21)</span>
              <span>- Rp ${formatRupiah(payroll.tax_pph21 || 0)}</span>
            </div>
            <div class="salary-row">
              <span>Potongan Lainnya</span>
              <span>- Rp ${formatRupiah(payroll.deduction || 0)}</span>
            </div>
          </div>
          ` : ''}

          <div class="salary-section">
            <div class="salary-row salary-total">
              <span>Total Gaji Bersih</span>
              <span>Rp ${formatRupiah(payroll.total_salary || 0)}</span>
            </div>
          </div>

          <div class="salary-section">
            <div class="salary-row">
              <span>Status Pembayaran</span>
              <span class="salary-status status-${(payroll.payment_status || 'Unpaid').toLowerCase()}">
                ${(payroll.payment_status || 'Unpaid') === 'Paid' ? '✅ LUNAS' : '❌ BELUM BAYAR'}
              </span>
            </div>
          </div>

          ${payroll.payment_proof ? `
          <div class="payment-proof-section">
            <h3>Bukti Pembayaran</h3>
            <div style="margin-top: 0.5rem;">
              <a href="../uploads/${payroll.payment_proof}" target="_blank" class="btn btn-success btn-sm">Lihat Bukti Pembayaran</a>
            </div>
            ${payroll.payment_proof.match(/\.(jpg|jpeg|png|gif)$/i) ? `
              <img src="../uploads/${payroll.payment_proof}" alt="Bukti Pembayaran" class="payment-proof-image">
            ` : ''}
          </div>
          ` : ''}
        </div>
      </div>
    `;
    document.getElementById('salaryModal').style.display = 'flex';
  }
  
  function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID').format(amount);
  }
  
  function closeSalaryModal() {
    document.getElementById('salaryModal').style.display = 'none';
  }
  
  // Close modal when clicking outside
  document.getElementById('salaryModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeSalaryModal();
    }
  });
  
  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.5s ease';
      setTimeout(() => alert.remove(), 500);
    });
  }, 5000);
  
  // === CHARTS ===
  // Attendance Chart
  const ctx1 = document.getElementById('attendanceChart').getContext('2d');
  new Chart(ctx1, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chart_months) ?>,
      datasets: [{
        label: 'Kehadiran per Bulan',
        data: <?= json_encode($chart_attendance) ?>,
        backgroundColor: '#ff6b35'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });

  // Overtime Chart
  const ctx2 = document.getElementById('overtimeChart').getContext('2d');
  new Chart(ctx2, {
    type: 'line',
    data: {
      labels: <?= json_encode($chart_months) ?>,
      datasets: [{
        label: 'Jam Lembur per Bulan',
        data: <?= json_encode($chart_overtime) ?>,
        borderColor: '#ff8e35',
        backgroundColor: 'rgba(255, 142, 53, 0.1)',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  // Leave Status Chart
  const ctx3 = document.getElementById('leaveChart').getContext('2d');
  new Chart(ctx3, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($leave_labels) ?>,
      datasets: [{
        data: <?= json_encode($leave_data) ?>,
        backgroundColor: ['#f59e0b', '#10b981', '#ef4444']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });
</script>
</body>
</html>