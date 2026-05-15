<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include '../connection.php';

// Cek login dan role HR
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'HR') {
    header("Location: ../signin.php");
    exit;
}

$hr_id = $_SESSION['user_id'];

// Ambil data HR
$current_hr = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT u.*, d.division_name 
     FROM users u 
     LEFT JOIN divisions d ON u.division_id = d.id 
     WHERE u.id = $hr_id"
));

if (!$current_hr) {
    session_destroy();
    header("Location: ../signin.php");
    exit;
}

$hr_division = $current_hr['division_id'];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // APPROVE/REJECT CUTI - PERBAIKAN: Hapus kondisi leader_id
    if (isset($_POST['action']) && $_POST['action'] === 'approve_leave') {
        $leave_id = (int)$_POST['leave_id'];
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved' WHERE id = ?");
        $stmt->bind_param("i", $leave_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Cuti berhasil disetujui.";
        } else {
            $_SESSION['error'] = "Gagal menyetujui cuti.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=leave-requests");
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'reject_leave') {
        $leave_id = (int)$_POST['leave_id'];
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $leave_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Cuti berhasil ditolak.";
        } else {
            $_SESSION['error'] = "Gagal menolak cuti.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=leave-requests");
        exit;
    }
    // APPROVE/REJECT LEMBUR - PERBAIKAN: Hapus kondisi leader_id
    if (isset($_POST['action']) && $_POST['action'] === 'approve_overtime') {
        $ot_id = (int)$_POST['ot_id'];
        $stmt = $conn->prepare("UPDATE overtime SET status = 'Approved' WHERE id = ?");
        $stmt->bind_param("i", $ot_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Lembur berhasil disetujui.";
        } else {
            $_SESSION['error'] = "Gagal menyetujui lembur.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=overtime-requests");
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'reject_overtime') {
        $ot_id = (int)$_POST['ot_id'];
        $stmt = $conn->prepare("UPDATE overtime SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $ot_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Lembur berhasil ditolak.";
        } else {
            $_SESSION['error'] = "Gagal menolak lembur.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=overtime-requests");
        exit;
    }
    // UPDATE PROFILE
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        // Validasi input
        if (empty($name) || empty($username) || empty($email)) {
            $_SESSION['error'] = "Semua field wajib diisi!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Format email tidak valid!";
        } else {
            // Cek email/username duplikat
            $check_user = mysqli_query($conn, 
                "SELECT id FROM users 
                 WHERE (email = '$email' OR username = '$username') 
                 AND id != $hr_id"
            );
            if (mysqli_num_rows($check_user) > 0) {
                $_SESSION['error'] = "Email atau Username sudah digunakan!";
            } else {
                $photo_sql = "";
                // Handle upload foto
                if (!empty($_FILES['photo']['name'])) {
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                    $file_type = $_FILES['photo']['type'];
                    $file_size = $_FILES['photo']['size'];
                    $max_size = 5 * 1024 * 1024;
                    if (!in_array($file_type, $allowed_types)) {
                        $_SESSION['error'] = "Hanya file JPG, JPEG, PNG yang diperbolehkan!";
                    } elseif ($file_size > $max_size) {
                        $_SESSION['error'] = "Ukuran file maksimal 5MB!";
                    } else {
                        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                        $photo_name = time() . "_hr." . $ext;
                        $target = "../uploads/" . $photo_name;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                            $photo_sql = ", photo = '$photo_name'";
                            $_SESSION['photo'] = $photo_name;
                            // Hapus foto lama
                            if (!empty($current_hr['photo']) && $current_hr['photo'] != 'default.png') {
                                $old_photo_path = "../uploads/" . $current_hr['photo'];
                                if (file_exists($old_photo_path)) {
                                    unlink($old_photo_path);
                                }
                            }
                        } else {
                            $_SESSION['error'] = "Gagal mengupload foto.";
                        }
                    }
                }
                $password_sql = "";
                // Handle password
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (strlen($password) >= 6) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $password_sql = ", password = '$hashed_password'";
                    } else {
                        $_SESSION['error'] = "Password minimal 6 karakter!";
                    }
                }
                // Update database jika tidak ada error
                if (!isset($_SESSION['error'])) {
                    $sql = "UPDATE users SET name = ?, username = ?, email = ? ";
                    $params = [$name, $username, $email];
                    $types = "sss";
                    if (!empty($photo_sql)) {
                        $sql .= ", photo = ? ";
                        $params[] = $photo_name;
                        $types .= "s";
                    }
                    if (!empty($password_sql)) {
                        $sql .= ", password = ? ";
                        $params[] = $hashed_password;
                        $types .= "s";
                    }
                    $sql .= "WHERE id = ?";
                    $params[] = $hr_id;
                    $types .= "i";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['success'] = "Profil berhasil diperbarui.";
                        $current_hr = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT u.*, d.division_name 
                             FROM users u 
                             LEFT JOIN divisions d ON u.division_id = d.id 
                             WHERE u.id = $hr_id"
                        ));
                    } else {
                        $_SESSION['error'] = "Gagal memperbarui profil.";
                    }
                }
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=profile");
        exit;
    }
    // CLOCK IN/OUT
    if (isset($_POST['action']) && ($_POST['action'] === 'clock_in' || $_POST['action'] === 'clock_out')) {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $mode = $_POST['mode'] ?? 'WFO';
        $existing_attendance = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT * FROM attendance 
             WHERE user_id = $hr_id 
             AND date = '$date'"
        ));
        if ($_POST['action'] === 'clock_in') {
            if (!$existing_attendance) {
                $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, clock_in, mode, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->bind_param("isss", $hr_id, $date, $time, $mode);
                $stmt->execute();
                $_SESSION['success'] = "Berhasil clock in.";
            } else {
                $_SESSION['error'] = "Anda sudah clock in hari ini.";
            }
        } else if ($_POST['action'] === 'clock_out') {
            if ($existing_attendance && $existing_attendance['clock_in'] && !$existing_attendance['clock_out']) {
                $stmt = $conn->prepare("UPDATE attendance SET clock_out = ? WHERE id = ?");
                $stmt->bind_param("si", $time, $existing_attendance['id']);
                $stmt->execute();
                $_SESSION['success'] = "Berhasil clock out.";
            } else {
                $_SESSION['error'] = "Anda belum clock in atau sudah clock out.";
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=my-attendance");
        exit;
    }
    // AJUKAN CUTI
    if (isset($_POST['action']) && $_POST['action'] === 'request_leave') {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $leave_type_id = (int)$_POST['leave_type_id'];
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $medical_certificate = null;
        
        // Ambil jenis cuti untuk cek apakah sakit
        $leave_type = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT type_name FROM leave_types WHERE id = $leave_type_id"
        ));
        
        // Handle upload surat dokter hanya jika jenis cuti adalah Sakit
        if ($leave_type['type_name'] === 'Sakit' && !empty($_FILES['medical_certificate']['name'])) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $file_type = $_FILES['medical_certificate']['type'];
            $file_size = $_FILES['medical_certificate']['size'];
            $max_size = 5 * 1024 * 1024;
            if (!in_array($file_type, $allowed_types)) {
                $_SESSION['error'] = "Hanya file JPG, JPEG, PNG yang diperbolehkan!";
            } else if ($file_size > $max_size) {
                $_SESSION['error'] = "Ukuran file maksimal 5MB!";
            } else {
                $ext = pathinfo($_FILES['medical_certificate']['name'], PATHINFO_EXTENSION);
                $file_name = time() . "_medical_" . $hr_id . "." . $ext;
                $target = "../uploads/" . $file_name;
                if (move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $target)) {
                    $medical_certificate = $file_name;
                } else {
                    $_SESSION['error'] = "Gagal mengupload surat dokter.";
                }
            }
        } elseif ($leave_type['type_name'] !== 'Sakit' && !empty($_FILES['medical_certificate']['name'])) {
            // Jika bukan cuti sakit, tetap proses tapi jangan simpan file
            $_SESSION['warning'] = "Surat dokter hanya diperlukan untuk cuti sakit. File tidak akan disimpan.";
        }
        
        if (!isset($_SESSION['error'])) {
            $owner_user = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT id FROM users WHERE role_id = 1 LIMIT 1"
            ));
            $owner_id = $owner_user ? $owner_user['id'] : null;
            $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, medical_certificate, status, leader_id) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->bind_param("iissssi", $hr_id, $leave_type_id, $start_date, $end_date, $reason, $medical_certificate, $owner_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Permintaan cuti berhasil dikirim.";
            } else {
                $_SESSION['error'] = "Gagal mengirim permintaan cuti.";
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=my-leave-requests");
        exit;
    }
    // AJUKAN LEMBUR
    if (isset($_POST['action']) && $_POST['action'] === 'request_overtime') {
        $date = $_POST['date'];
        $hours = (float)$_POST['hours'];
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $proof_file = null;
        // Handle upload bukti lembur
        if (!empty($_FILES['proof_file']['name'])) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $file_type = $_FILES['proof_file']['type'];
            $file_size = $_FILES['proof_file']['size'];
            $max_size = 5 * 1024 * 1024;
            if (!in_array($file_type, $allowed_types)) {
                $_SESSION['error'] = "Hanya file JPG, JPEG, PNG, PDF yang diperbolehkan!";
            } else if ($file_size > $max_size) {
                $_SESSION['error'] = "Ukuran file maksimal 5MB!";
            } else {
                $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
                $file_name = time() . "_overtime_" . $hr_id . "." . $ext;
                $target = "../uploads/" . $file_name;
                if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $target)) {
                    $proof_file = $file_name;
                } else {
                    $_SESSION['error'] = "Gagal mengupload bukti.";
                }
            }
        }
        if (!isset($_SESSION['error'])) {
            $owner_user = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT id FROM users WHERE role_id = 1 LIMIT 1"
            ));
            $owner_id = $owner_user ? $owner_user['id'] : null;
            $stmt = $conn->prepare("INSERT INTO overtime (user_id, date, hours, description, proof_file, status, leader_id) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->bind_param("issssi", $hr_id, $date, $hours, $description, $proof_file, $owner_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Permintaan lembur berhasil dikirim.";
            } else {
                $_SESSION['error'] = "Gagal mengirim permintaan lembur.";
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=my-overtime-requests");
        exit;
    }
    // UPDATE GAJI - PERBAIKAN: Hitung pajak langsung dari input persentase tanpa menyimpan persentase
    if (isset($_POST['action']) && $_POST['action'] === 'update_payroll') {
        $user_id = (int)$_POST['user_id'];
        
        // PERBAIKAN: Validasi dan format tanggal dengan benar
        $pay_date_input = $_POST['pay_date'] ?? date('Y-m-01');
        // Pastikan format tanggal benar
        $pay_date = date('Y-m-d', strtotime($pay_date_input));
        if ($pay_date === '1970-01-01') {
            // Jika parsing gagal, gunakan tanggal default
            $pay_date = date('Y-m-01');
        }
        
        $base_salary = (float)$_POST['base_salary'];
        $allowance = (float)$_POST['allowance'];
        $deduction = (float)$_POST['deduction'];
        $transport_allowance = (float)$_POST['transport_allowance'];
        $meal_allowance = (float)$_POST['meal_allowance'];
        $position_allowance = (float)$_POST['position_allowance'];
        $bpjs_kes = (float)$_POST['bpjs_kes'];
        $bpjs_tk = (float)$_POST['bpjs_tk'];
        $thr = (float)$_POST['thr'];
        
        // PERBAIKAN: Ambil persentase pajak dari input untuk perhitungan saja
        $tax_percentage = (float)$_POST['tax_percentage'];
        
        // Hitung lembur otomatis
        $current_month = date('Y-m', strtotime($pay_date));
        $overtime_weekday_hours = 0;
        $overtime_weekend_hours = 0;
        $overtime_rate_weekday = 100000;
        $overtime_rate_weekend = 150000;
        
        $overtime_query = mysqli_query($conn, 
            "SELECT date, hours 
             FROM overtime 
             WHERE user_id = $user_id 
             AND status = 'Approved' 
             AND DATE_FORMAT(date, '%Y-%m') = '$current_month'"
        );
        
        while ($ot_row = mysqli_fetch_assoc($overtime_query)) {
            $ot_date = $ot_row['date'];
            $ot_hours = (float)$ot_row['hours'];
            $day_of_week = date('w', strtotime($ot_date));
            if ($day_of_week == 0 || $day_of_week == 6) {
                $overtime_weekend_hours += $ot_hours;
            } else {
                $overtime_weekday_hours += $ot_hours;
            }
        }
        
        $overtime_pay_weekday = $overtime_weekday_hours * $overtime_rate_weekday;
        $overtime_pay_weekend = $overtime_weekend_hours * $overtime_rate_weekend;
        $overtime_pay = $overtime_pay_weekday + $overtime_pay_weekend;
        
        // Hitung gaji
        $gross_salary = $base_salary + $allowance + $transport_allowance + $meal_allowance + $position_allowance + $overtime_pay + $thr;
        
        // PERBAIKAN: Hitung pajak berdasarkan persentase yang diinput (tidak disimpan)
        $tax_pph21 = $gross_salary * ($tax_percentage / 100);
        
        $total_salary = $gross_salary - $deduction - $bpjs_kes - $bpjs_tk - $tax_pph21;
        
        // Cek apakah data gaji sudah ada
        $payroll_exists = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT id FROM payroll 
             WHERE user_id = $user_id 
             AND DATE_FORMAT(pay_date, '%Y-%m') = '$current_month'"
        ));
        
        // PERBAIKAN: Query tanpa tax_percentage
        if ($payroll_exists) {
            // Update data existing
            $stmt = $conn->prepare("UPDATE payroll SET base_salary = ?, allowance = ?, deduction = ?, overtime_pay = ?, tax_pph21 = ?, thr = ?, total_salary = ?, pay_date = ?, transport_allowance = ?, meal_allowance = ?, position_allowance = ?, overtime_rate = ?, bpjs_kes = ?, bpjs_tk = ? WHERE user_id = ? AND DATE_FORMAT(pay_date, '%Y-%m') = ?");
            $stmt->bind_param("ddddddddddddddis", $base_salary, $allowance, $deduction, $overtime_pay, $tax_pph21, $thr, $total_salary, $pay_date, $transport_allowance, $meal_allowance, $position_allowance, $overtime_rate_weekday, $bpjs_kes, $bpjs_tk, $user_id, $current_month);
        } else {
            // Buat data baru
            $stmt = $conn->prepare("INSERT INTO payroll (user_id, base_salary, allowance, deduction, overtime_pay, tax_pph21, thr, total_salary, pay_date, transport_allowance, meal_allowance, position_allowance, overtime_rate, bpjs_kes, bpjs_tk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idddddddddddddd", $user_id, $base_salary, $allowance, $deduction, $overtime_pay, $tax_pph21, $thr, $total_salary, $pay_date, $transport_allowance, $meal_allowance, $position_allowance, $overtime_rate_weekday, $bpjs_kes, $bpjs_tk);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Data gaji berhasil disimpan.";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data gaji: " . $stmt->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=payroll");
        exit;
    }
    // EXPORT TO EXCEL
    if (isset($_POST['export_excel'])) {
        $tab = $_POST['export_tab'] ?? 'dashboard';
        $filename = "slippy_export_" . $tab . "_" . date('Ymd_His') . ".xls";
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Write BOM for UTF-8 encoding (to fix character encoding issues)
        echo "\xEF\xBB\xBF";
        
        // Create a simple Excel file using HTML table format
        echo "<table border='1'>";
        
        switch($tab) {
            case 'leave-requests':
                echo "<tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Alasan</th><th>Status</th><th>Surat Dokter</th></tr>";
                $leave_filter_sql = " AND lr.start_date BETWEEN '" . ($_GET['leave_start'] ?? date('Y-m-01')) . "' AND '" . ($_GET['leave_end'] ?? date('Y-m-t')) . "' AND u.division_id = $hr_division";
                if (($_GET['leave_status'] ?? 'All') !== 'All') {
                    $leave_filter_sql .= " AND lr.status = '" . ($_GET['leave_status'] ?? 'All') . "'";
                }
                // PERBAIKAN: Query untuk mengambil semua cuti dari divisi HR
                $all_leaves = mysqli_query($conn, 
                    "SELECT lr.*, u.name as employee_name, lt.type_name 
                     FROM leave_requests lr 
                     JOIN users u ON lr.user_id = u.id 
                     JOIN leave_types lt ON lr.leave_type_id = lt.id 
                     WHERE u.division_id = $hr_division $leave_filter_sql 
                     ORDER BY lr.start_date DESC"
                );
                while ($leave = mysqli_fetch_assoc($all_leaves)) {
                    $surat_dokter = $leave['medical_certificate'] ? "Ada" : "Tidak Ada";
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($leave['employee_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($leave['type_name']) . "</td>";
                    echo "<td>" . date('d M Y', strtotime($leave['start_date'])) . " - " . date('d M Y', strtotime($leave['end_date'])) . "</td>";
                    echo "<td>" . htmlspecialchars($leave['reason']) . "</td>";
                    echo "<td>" . $leave['status'] . "</td>";
                    echo "<td>" . $surat_dokter . "</td>";
                    echo "</tr>";
                }
                break;
                
            case 'overtime-requests':
                echo "<tr><th>Karyawan</th><th>Tanggal</th><th>Jam</th><th>Alasan</th><th>Status</th><th>Bukti</th></tr>";
                $ot_filter_sql = " AND ot.date BETWEEN '" . ($_GET['overtime_start'] ?? date('Y-m-01')) . "' AND '" . ($_GET['overtime_end'] ?? date('Y-m-t')) . "' AND u.division_id = $hr_division";
                if (($_GET['overtime_status'] ?? 'All') !== 'All') {
                    $ot_filter_sql .= " AND ot.status = '" . ($_GET['overtime_status'] ?? 'All') . "'";
                }
                // PERBAIKAN: Query untuk mengambil semua lembur dari divisi HR
                $all_overtime = mysqli_query($conn, 
                    "SELECT ot.*, u.name as employee_name 
                     FROM overtime ot 
                     JOIN users u ON ot.user_id = u.id 
                     WHERE u.division_id = $hr_division $ot_filter_sql 
                     ORDER BY ot.date DESC"
                );
                while ($ot = mysqli_fetch_assoc($all_overtime)) {
                    $bukti = $ot['proof_file'] ? "Ada" : "Tidak Ada";
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($ot['employee_name']) . "</td>";
                    echo "<td>" . date('d M Y', strtotime($ot['date'])) . "</td>";
                    echo "<td>" . $ot['hours'] . " jam</td>";
                    echo "<td>" . htmlspecialchars($ot['description']) . "</td>";
                    echo "<td>" . $ot['status'] . "</td>";
                    echo "<td>" . $bukti . "</td>";
                    echo "</tr>";
                }
                break;
                
            case 'attendance':
                echo "<tr><th>Nama</th><th>Tanggal</th><th>Clock In</th><th>Clock Out</th><th>Mode</th><th>Status</th></tr>";
                $all_attendance = mysqli_query($conn, 
                    "SELECT a.*, u.name as employee_name, u.photo 
                     FROM attendance a 
                     JOIN users u ON a.user_id = u.id 
                     WHERE u.division_id = $hr_division 
                     ORDER BY a.date DESC, u.name ASC"
                );
                while ($att = mysqli_fetch_assoc($all_attendance)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($att['employee_name']) . "</td>";
                    echo "<td>" . date('d M Y', strtotime($att['date'])) . "</td>";
                    echo "<td>" . ($att['clock_in'] ? date('H:i', strtotime($att['clock_in'])) : '-') . "</td>";
                    echo "<td>" . ($att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : '-') . "</td>";
                    echo "<td>" . ($att['mode'] ?? '-') . "</td>";
                    echo "<td>" . ($att['clock_in'] ? 'Hadir' : 'Tidak Hadir') . "</td>";
                    echo "</tr>";
                }
                break;
                
            case 'employees':
                echo "<tr><th>Nama</th><th>Role</th><th>Email</th><th>Status</th><th>Divisi</th></tr>";
                $division_employees = mysqli_query($conn, 
                    "SELECT * FROM users 
                     WHERE division_id = $hr_division 
                     ORDER BY role_id, name"
                );
                while ($emp = mysqli_fetch_assoc($division_employees)) {
                    $role_name = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT role_name FROM roles WHERE id = " . $emp['role_id']
                    ))['role_name'];
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($emp['name']) . "</td>";
                    echo "<td>" . $role_name . "</td>";
                    echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
                    echo "<td>" . ucfirst($emp['status']) . "</td>";
                    echo "<td>" . htmlspecialchars($emp['division_name'] ?? 'No Division') . "</td>";
                    echo "</tr>";
                }
                break;
                
            case 'payroll':
                echo "<tr><th>Nama</th><th>Tanggal Gaji</th><th>Gaji Pokok</th><th>Tunjangan</th><th>Lembur</th><th>THR</th><th>PPH21</th><th>Total Gaji</th><th>Status</th></tr>";
                $payroll_data = mysqli_query($conn, 
                    "SELECT p.*, u.name as employee_name
                     FROM payroll p
                     JOIN users u ON p.user_id = u.id
                     WHERE u.division_id = $hr_division
                     ORDER BY u.name, p.pay_date DESC"
                );
                while ($pay = mysqli_fetch_assoc($payroll_data)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($pay['employee_name']) . "</td>";
                    echo "<td>" . ($pay['pay_date'] ? date('M Y', strtotime($pay['pay_date'])) : '-') . "</td>";
                    echo "<td>Rp " . number_format($pay['base_salary'], 0, ',', '.') . "</td>";
                    echo "<td>Rp " . number_format($pay['allowance'] + $pay['transport_allowance'] + $pay['meal_allowance'] + $pay['position_allowance'], 0, ',', '.') . "</td>";
                    echo "<td>Rp " . number_format($pay['overtime_pay'], 0, ',', '.') . "</td>";
                    echo "<td>Rp " . number_format($pay['thr'], 0, ',', '.') . "</td>";
                    echo "<td>Rp " . number_format($pay['tax_pph21'], 0, ',', '.') . "</td>";
                    echo "<td><strong>Rp " . number_format($pay['total_salary'], 0, ',', '.') . "</strong></td>";
                    echo "<td>" . ($pay['payment_status'] ?? 'Unpaid') . "</td>";
                    echo "</tr>";
                }
                break;
                
            default:
                echo "<tr><td colspan='6'>Tidak ada data untuk diekspor.</td></tr>";
        }
        
        echo "</table>";
        exit;
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
if (isset($_SESSION['warning'])) {
    $warning = $_SESSION['warning'];
    unset($_SESSION['warning']);
}

// FILTER DATA
$leave_start = $_GET['leave_start'] ?? date('Y-m-01');
$leave_end = $_GET['leave_end'] ?? date('Y-m-t');
$leave_status = $_GET['leave_status'] ?? 'All';
$overtime_start = $_GET['overtime_start'] ?? date('Y-m-01');
$overtime_end = $_GET['overtime_end'] ?? date('Y-m-t');
$overtime_status = $_GET['overtime_status'] ?? 'All';

// Active tab
$active_tab = $_GET['tab'] ?? 'dashboard';

// QUERY DATA - PERBAIKAN: Ambil semua data dari divisi HR, bukan hanya yang leader_id = $hr_id
$pending_leaves = mysqli_query($conn, 
    "SELECT lr.*, u.name as employee_name, lt.type_name 
     FROM leave_requests lr 
     JOIN users u ON lr.user_id = u.id 
     JOIN leave_types lt ON lr.leave_type_id = lt.id 
     WHERE u.division_id = $hr_division 
     AND lr.status = 'Pending' 
     ORDER BY lr.start_date DESC"
);

$pending_overtime = mysqli_query($conn, 
    "SELECT ot.*, u.name as employee_name 
     FROM overtime ot 
     JOIN users u ON ot.user_id = u.id 
     WHERE u.division_id = $hr_division 
     AND ot.status = 'Pending' 
     ORDER BY ot.date DESC"
);

// Filter leaves - PERBAIKAN: Tambahkan kondisi divisi
$leave_filter_sql = " AND u.division_id = $hr_division AND lr.start_date BETWEEN '$leave_start' AND '$leave_end'";
if ($leave_status !== 'All') {
    $leave_filter_sql .= " AND lr.status = '$leave_status'";
}
$all_leaves = mysqli_query($conn, 
    "SELECT lr.*, u.name as employee_name, lt.type_name 
     FROM leave_requests lr 
     JOIN users u ON lr.user_id = u.id 
     JOIN leave_types lt ON lr.leave_type_id = lt.id 
     WHERE 1=1 $leave_filter_sql 
     ORDER BY lr.start_date DESC"
);

// Filter overtime - PERBAIKAN: Tambahkan kondisi divisi
$ot_filter_sql = " AND u.division_id = $hr_division AND ot.date BETWEEN '$overtime_start' AND '$overtime_end'";
if ($overtime_status !== 'All') {
    $ot_filter_sql .= " AND ot.status = '$overtime_status'";
}
$all_overtime = mysqli_query($conn, 
    "SELECT ot.*, u.name as employee_name 
     FROM overtime ot 
     JOIN users u ON ot.user_id = u.id 
     WHERE 1=1 $ot_filter_sql 
     ORDER BY ot.date DESC"
);

// Data karyawan divisi
$division_employees = mysqli_query($conn, 
    "SELECT * FROM users 
     WHERE division_id = $hr_division 
     ORDER BY role_id, name"
);

// Data absensi
$all_attendance = mysqli_query($conn, 
    "SELECT a.*, u.name as employee_name, u.photo 
     FROM attendance a 
     JOIN users u ON a.user_id = u.id 
     WHERE u.division_id = $hr_division 
     ORDER BY a.date DESC, u.name ASC"
);

// Stats - PERBAIKAN: Hitung berdasarkan divisi, bukan leader_id
$stats = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT
        (SELECT COUNT(*) FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE u.division_id = $hr_division AND lr.status = 'Pending') as pending_leaves,
        (SELECT COUNT(*) FROM overtime ot JOIN users u ON ot.user_id = u.id WHERE u.division_id = $hr_division AND ot.status = 'Pending') as pending_overtime"
));

// CHART DATA - PERBAIKAN: Sesuaikan dengan query berdasarkan divisi
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}
$chart_months = array_map(function ($m) {
    return date('M Y', strtotime($m));
}, $months);
$leave_data = [];
foreach ($months as $m) {
    $pending = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as c 
         FROM leave_requests lr 
         JOIN users u ON lr.user_id = u.id 
         WHERE u.division_id = $hr_division 
         AND DATE_FORMAT(lr.start_date, '%Y-%m') = '$m' 
         AND lr.status = 'Pending'"
    ))['c'];
    $approved = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as c 
         FROM leave_requests lr 
         JOIN users u ON lr.user_id = u.id 
         WHERE u.division_id = $hr_division 
         AND DATE_FORMAT(lr.start_date, '%Y-%m') = '$m' 
         AND lr.status = 'Approved'"
    ))['c'];
    $rejected = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as c 
         FROM leave_requests lr 
         JOIN users u ON lr.user_id = u.id 
         WHERE u.division_id = $hr_division 
         AND DATE_FORMAT(lr.start_date, '%Y-%m') = '$m' 
         AND lr.status = 'Rejected'"
    ))['c'];
    $leave_data[] = ['pending' => $pending, 'approved' => $approved, 'rejected' => $rejected];
}
$ot_data = [];
foreach ($months as $m) {
    $hours = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COALESCE(SUM(hours), 0) as h 
         FROM overtime ot 
         JOIN users u ON ot.user_id = u.id 
         WHERE u.division_id = $hr_division 
         AND DATE_FORMAT(ot.date, '%Y-%m') = '$m' 
         AND ot.status = 'Approved'"
    ))['h'];
    $ot_data[] = $hours;
}

// DATA PRIBADI HR
$my_attendance = mysqli_query($conn, 
    "SELECT * FROM attendance 
     WHERE user_id = $hr_id 
     ORDER BY date DESC"
);
$my_leaves = mysqli_query($conn, 
    "SELECT lr.*, lt.type_name 
     FROM leave_requests lr 
     JOIN leave_types lt ON lr.leave_type_id = lt.id 
     WHERE lr.user_id = $hr_id 
     ORDER BY lr.start_date DESC"
);
$my_overtime = mysqli_query($conn, 
    "SELECT * FROM overtime 
     WHERE user_id = $hr_id 
     ORDER BY date DESC"
);
$leave_types = mysqli_query($conn, "SELECT * FROM leave_types");

// DATA PAYROLL PRIBADI HR - QUERY YANG DIPERBAIKI
$my_payroll = mysqli_query($conn, 
    "SELECT *, 
            COALESCE(allowance, 0) as allowance,
            COALESCE(transport_allowance, 0) as transport_allowance, 
            COALESCE(meal_allowance, 0) as meal_allowance,
            COALESCE(position_allowance, 0) as position_allowance,
            COALESCE(overtime_pay, 0) as overtime_pay,
            COALESCE(thr, 0) as thr,
            COALESCE(tax_pph21, 0) as tax_pph21,
            COALESCE(total_salary, 0) as total_salary,
            COALESCE(payment_status, 'Unpaid') as payment_status
     FROM payroll 
     WHERE user_id = $hr_id 
     ORDER BY pay_date DESC"
);

// DATA GAJI KARYAWAN
$payroll_data = mysqli_query($conn, 
    "SELECT p.*, u.name as employee_name
     FROM payroll p
     JOIN users u ON p.user_id = u.id
     WHERE u.division_id = $hr_division
     ORDER BY u.name, p.pay_date DESC"
);

// Absensi hari ini
$current_attendance = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM attendance 
     WHERE user_id = $hr_id 
     AND date = '" . date('Y-m-d') . "'"
));

// Helper functions
function indonesianDate($date) {
    if (empty($date)) return '-';
    $d = strtotime($date);
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[date('w', $d)] . ', ' . date('d', $d) . ' ' . $months[date('n', $d) - 1] . ' ' . date('Y', $d);
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}
?>

<!-- HTML CODE -->
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard HR | Slippy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS TIDAK BERUBAH - SAMA DENGAN SEBELUMNYA */
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
            --sidebar-width: 250px;
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
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
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
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid var(--border-primary);
        }
        .logo {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            text-decoration: none;
            margin-bottom: 1rem;
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
            padding: 0.75rem 0;
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #d97706;
        }
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        canvas {
            max-height: 200px;
        }
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
        .file-preview {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-sm);
            background: var(--card-bg);
            display: none;
        }
        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--radius-sm);
        }
        .file-preview embed {
            width: 100%;
            max-height: 200px;
            border-radius: var(--radius-sm);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-secondary);
            margin: 0 auto 1rem;
            display: block;
        }
        .attendance-card {
            background: var(--glass);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .attendance-status {
            font-size: 1.2rem;
            margin: 1.5rem 0;
            color: var(--text-secondary);
        }
        .attendance-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        /* PERBAIKAN: Salary Slip Modal - FIXED */
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
        }
        .salary-modal-content {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-primary);
            box-shadow: var(--shadow-lg);
            color: var(--text-primary);
        }
        .salary-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-primary);
        }
        .salary-modal-header h2 {
            margin: 0;
            color: var(--text-primary);
        }
        .salary-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .salary-slip {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-primary);
        }
        .salary-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--accent-secondary);
            padding-bottom: 1rem;
        }
        .salary-header h2 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .salary-header p {
            color: var(--text-secondary);
            margin: 0.25rem 0;
        }
        .salary-details {
            display: grid;
            gap: 1rem;
        }
        .salary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-primary);
            color: var(--text-primary);
        }
        .salary-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent-secondary);
            border-top: 2px solid var(--border-secondary);
            margin-top: 1rem;
            padding-top: 1rem;
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
        .salary-section-title {
            font-weight: 600;
            color: var(--accent-secondary);
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-primary);
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
                    <img src="../uploads/<?= $current_hr['photo'] ?? 'default.png' ?>" alt="Profile" class="user-avatar" 
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_hr['name']) ?>&background=ff6b35&color=fff&size=40'">
                    <div class="user-details">
                        <h4><?= htmlspecialchars($current_hr['name']) ?></h4>
                        <p><?= htmlspecialchars($current_hr['division_name'] ?? 'No Division') ?></p>
                    </div>
                </div>
            </div>
            <nav class="nav-links">
                <a href="#" class="nav-item <?= $active_tab === 'dashboard' ? 'active' : '' ?>" onclick="switchTab('dashboard')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'pending-requests' ? 'active' : '' ?>" onclick="switchTab('pending-requests')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Permintaan Pending
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'leave-requests' ? 'active' : '' ?>" onclick="switchTab('leave-requests')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Cuti Karyawan
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'overtime-requests' ? 'active' : '' ?>" onclick="switchTab('overtime-requests')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Lembur Karyawan
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'my-attendance' ? 'active' : '' ?>" onclick="switchTab('my-attendance')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Absensi
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'my-leave-requests' ? 'active' : '' ?>" onclick="switchTab('my-leave-requests')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Cuti
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'my-overtime-requests' ? 'active' : '' ?>" onclick="switchTab('my-overtime-requests')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Lembur
                </a>
                <!-- TAB BARU: Gaji Saya -->
                <a href="#" class="nav-item <?= $active_tab === 'my-payroll' ? 'active' : '' ?>" onclick="switchTab('my-payroll')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Slip Gaji
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'attendance' ? 'active' : '' ?>" onclick="switchTab('attendance')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Absensi Karyawan
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'employees' ? 'active' : '' ?>" onclick="switchTab('employees')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Karyawan
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'payroll' ? 'active' : '' ?>" onclick="switchTab('payroll')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Data Gaji
                </a>
                <a href="#" class="nav-item <?= $active_tab === 'profile' ? 'active' : '' ?>" onclick="switchTab('profile')">
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
                <h1>Dashboard HR - <?= htmlspecialchars($current_hr['division_name']) ?></h1>
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
            <?php if(isset($warning)): ?>
                <div class="alert alert-warning fade-in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <?= $warning ?>
                </div>
            <?php endif; ?>
            <!-- DASHBOARD TAB -->
            <div class="tab-content <?= $active_tab === 'dashboard' ? 'active' : '' ?>" id="dashboard">
                <div class="stats-grid">
                    <div class="stat-card fade-in">
                        <canvas id="leaveChart" height="100"></canvas>
                    </div>
                    <div class="stat-card fade-in">
                        <canvas id="overtimeChart" height="100"></canvas>
                    </div>
                </div>
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Ringkasan</h2>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="stat-value"><?= $stats['pending_leaves'] ?></div>
                            <div class="stat-label">Pending Cuti</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="stat-value"><?= $stats['pending_overtime'] ?></div>
                            <div class="stat-label">Pending Lembur</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PENDING REQUESTS TAB -->
            <div class="tab-content <?= $active_tab === 'pending-requests' ? 'active' : '' ?>" id="pending-requests">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Permintaan Pending</h2>
                    </div>
                    <!-- PENDING CUTI -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Cuti Karyawan</h3>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Jenis</th>
                                        <th>Tanggal</th>
                                        <th>Alasan</th>
                                        <th>Surat Dokter</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($leave = mysqli_fetch_assoc($pending_leaves)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                                            <td><?= htmlspecialchars($leave['type_name']) ?></td>
                                            <td><?= indonesianDate($leave['start_date']) ?> - <?= indonesianDate($leave['end_date']) ?></td>
                                            <td><?= htmlspecialchars($leave['reason']) ?></td>
                                            <td>
                                                <?php if ($leave['medical_certificate']): ?>
                                                    <a href="../uploads/<?= $leave['medical_certificate'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                                <?php else: ?> - <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                                    <input type="hidden" name="action" value="approve_leave">
                                                    <button type="submit" class="btn btn-success btn-sm">Setujui</button>
                                                </form>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                                    <input type="hidden" name="action" value="reject_leave">
                                                    <button type="submit" class="btn btn-warning btn-sm">Tolak</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- PENDING LEMBUR -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Lembur Karyawan</h3>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Alasan</th>
                                        <th>Bukti</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ot = mysqli_fetch_assoc($pending_overtime)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ot['employee_name']) ?></td>
                                            <td><?= indonesianDate($ot['date']) ?></td>
                                            <td><?= $ot['hours'] ?> jam</td>
                                            <td><?= htmlspecialchars($ot['description']) ?></td>
                                            <td>
                                                <?php if ($ot['proof_file']): ?>
                                                    <a href="../uploads/<?= $ot['proof_file'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                                <?php else: ?> - <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="ot_id" value="<?= $ot['id'] ?>">
                                                    <input type="hidden" name="action" value="approve_overtime">
                                                    <button type="submit" class="btn btn-success btn-sm">Setujui</button>
                                                </form>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="ot_id" value="<?= $ot['id'] ?>">
                                                    <input type="hidden" name="action" value="reject_overtime">
                                                    <button type="submit" class="btn btn-warning btn-sm">Tolak</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- LEAVE REQUESTS TAB -->
            <div class="tab-content <?= $active_tab === 'leave-requests' ? 'active' : '' ?>" id="leave-requests">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Semua Pengajuan Cuti Karyawan</h2>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <form method="GET" class="filter-row">
                                <input type="hidden" name="tab" value="leave-requests">
                                <div class="filter-group">
                                    <label class="filter-label">Dari</label>
                                    <input type="date" name="leave_start" class="filter-input" value="<?= $leave_start ?>" required>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Sampai</label>
                                    <input type="date" name="leave_end" class="filter-input" value="<?= $leave_end ?>" required>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Status</label>
                                    <select name="leave_status" class="filter-select">
                                        <option value="All" <?= $leave_status === 'All' ? 'selected' : '' ?>>Semua</option>
                                        <option value="Pending" <?= $leave_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Approved" <?= $leave_status === 'Approved' ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="Rejected" <?= $leave_status === 'Rejected' ? 'selected' : '' ?>>Ditolak</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                            <form method="POST" style="margin-left: 1rem;">
                                <input type="hidden" name="export_tab" value="leave-requests">
                                <input type="hidden" name="export_excel" value="1">
                                <button type="submit" class="btn btn-outline">Export ke Excel</button>
                            </form>
                        </div>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Surat Dokter</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = mysqli_fetch_assoc($all_leaves)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                                        <td><?= htmlspecialchars($leave['type_name']) ?></td>
                                        <td><?= indonesianDate($leave['start_date']) ?> - <?= indonesianDate($leave['end_date']) ?></td>
                                        <td><?= htmlspecialchars($leave['reason']) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($leave['status']) ?>"><?= $leave['status'] ?></span></td>
                                        <td>
                                            <?php if ($leave['medical_certificate']): ?>
                                                <a href="../uploads/<?= $leave['medical_certificate'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- OVERTIME REQUESTS TAB -->
            <div class="tab-content <?= $active_tab === 'overtime-requests' ? 'active' : '' ?>" id="overtime-requests">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Semua Pengajuan Lembur Karyawan</h2>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <form method="GET" class="filter-row">
                                <input type="hidden" name="tab" value="overtime-requests">
                                <div class="filter-group">
                                    <label class="filter-label">Dari</label>
                                    <input type="date" name="overtime_start" class="filter-input" value="<?= $overtime_start ?>" required>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Sampai</label>
                                    <input type="date" name="overtime_end" class="filter-input" value="<?= $overtime_end ?>" required>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Status</label>
                                    <select name="overtime_status" class="filter-select">
                                        <option value="All" <?= $overtime_status === 'All' ? 'selected' : '' ?>>Semua</option>
                                        <option value="Pending" <?= $overtime_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Approved" <?= $overtime_status === 'Approved' ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="Rejected" <?= $overtime_status === 'Rejected' ? 'selected' : '' ?>>Ditolak</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                            <form method="POST" style="margin-left: 1rem;">
                                <input type="hidden" name="export_tab" value="overtime-requests">
                                <input type="hidden" name="export_excel" value="1">
                                <button type="submit" class="btn btn-outline">Export ke Excel</button>
                            </form>
                        </div>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Bukti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ot = mysqli_fetch_assoc($all_overtime)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ot['employee_name']) ?></td>
                                        <td><?= indonesianDate($ot['date']) ?></td>
                                        <td><?= $ot['hours'] ?> jam</td>
                                        <td><?= htmlspecialchars($ot['description']) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($ot['status']) ?>"><?= $ot['status'] ?></span></td>
                                        <td>
                                            <?php if ($ot['proof_file']): ?>
                                                <a href="../uploads/<?= $ot['proof_file'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- MY ATTENDANCE TAB -->
            <div class="tab-content <?= $active_tab === 'my-attendance' ? 'active' : '' ?>" id="my-attendance">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Absensi Saya</h2>
                    </div>
                    <!-- ABSENSI HARI INI -->
                    <div class="attendance-card">
                        <div class="attendance-header">
                            <h3>Absensi Hari Ini</h3>
                        </div>
                        <?php if (!isset($current_attendance) || (!$current_attendance['clock_in'] || $current_attendance['clock_out'])): ?>
                            <div class="attendance-status">
                                Belum melakukan clock in hari ini
                            </div>
                            <form method="POST" style="display: inline-block; margin: 1rem 0;">
                                <input type="hidden" name="action" value="clock_in">
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Mode Kerja</label>
                                    <select name="mode" class="form-select" style="width: auto; max-width: 300px; margin: 0 auto; display: block;">
                                        <option value="WFO">WFO (Work From Office)</option>
                                        <option value="WFH">WFH (Work From Home)</option>
                                        <option value="Hybrid">Hybrid</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Clock In Sekarang</button>
                            </form>
                        <?php else: ?>
                            <div class="attendance-status">
                                Anda telah clock in hari ini
                                <?php if (!$current_attendance['clock_out']): ?>
                                    <form method="POST" style="display: inline-block; margin: 1rem 0;">
                                        <input type="hidden" name="action" value="clock_out">
                                        <button type="submit" class="btn btn-warning">Clock Out Sekarang</button>
                                    </form>
                                <?php else: ?>
                                    <p style="color: var(--text-secondary); margin-top: 1rem;">Anda telah clock out pada <?= date('H:i', strtotime($current_attendance['clock_out'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- RIWAYAT ABSENSI -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($att = mysqli_fetch_assoc($my_attendance)): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($att['date'])) ?></td>
                                        <td><?= $att['clock_in'] ? date('H:i', strtotime($att['clock_in'])) : '-' ?></td>
                                        <td><?= $att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : '-' ?></td>
                                        <td><?= $att['mode'] ?? '-' ?></td>
                                        <td>
                                            <?php if ($att['clock_in']): ?>
                                                <span class="status-badge status-approved">Hadir</span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">Tidak Hadir</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- MY LEAVE REQUESTS TAB -->
            <div class="tab-content <?= $active_tab === 'my-leave-requests' ? 'active' : '' ?>" id="my-leave-requests">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Permintaan Cuti Saya</h2>
                        <button type="button" class="btn btn-primary" onclick="showForm('leave')">Ajukan Cuti</button>
                    </div>
                    <!-- FORM AJUKAN CUTI -->
                    <div id="leave-form" class="content-card" style="display: none;">
                        <h3 style="margin-bottom: 1.5rem;">Ajukan Cuti</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="request_leave">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" name="start_date" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" name="end_date" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Jenis Cuti</label>
                                    <select name="leave_type_id" class="form-select" required onchange="toggleMedicalCertificate(this)">
                                        <?php while ($type = mysqli_fetch_assoc($leave_types)): ?>
                                            <option value="<?= $type['id'] ?>"><?= $type['type_name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alasan</label>
                                    <textarea name="reason" class="form-textarea" rows="3" required></textarea>
                                </div>
                                <div class="form-group" id="medical-certificate-group" style="display: none;">
                                    <label class="form-label">Surat Dokter (Opsional)</label>
                                    <input type="file" name="medical_certificate" class="form-input" accept="image/*" onchange="previewFile(this)">
                                    <div id="medical-certificate-preview" class="file-preview"></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="button" class="btn btn-outline" onclick="hideForm('leave')">Batal</button>
                                <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                            </div>
                        </form>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Surat Dokter</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = mysqli_fetch_assoc($my_leaves)): ?>
                                    <tr>
                                        <td><?= indonesianDate($leave['start_date']) ?> - <?= indonesianDate($leave['end_date']) ?></td>
                                        <td><?= htmlspecialchars($leave['type_name']) ?></td>
                                        <td><?= htmlspecialchars($leave['reason']) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($leave['status']) ?>"><?= $leave['status'] ?></span></td>
                                        <td>
                                            <?php if ($leave['medical_certificate']): ?>
                                                <a href="../uploads/<?= $leave['medical_certificate'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- MY OVERTIME REQUESTS TAB -->
            <div class="tab-content <?= $active_tab === 'my-overtime-requests' ? 'active' : '' ?>" id="my-overtime-requests">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Permintaan Lembur Saya</h2>
                        <button type="button" class="btn btn-primary" onclick="showForm('overtime')">Ajukan Lembur</button>
                    </div>
                    <!-- FORM AJUKAN LEMBUR -->
                    <div id="overtime-form" class="content-card" style="display: none;">
                        <h3 style="margin-bottom: 1.5rem;">Ajukan Lembur</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="request_overtime">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="date" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Jam Lembur</label>
                                    <input type="number" name="hours" class="form-input" min="1" max="12" step="0.5" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alasan</label>
                                    <textarea name="description" class="form-textarea" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Bukti (Opsional)</label>
                                    <input type="file" name="proof_file" class="form-input" accept="image/*,application/pdf" onchange="previewFile(this)">
                                    <div id="proof-file-preview" class="file-preview"></div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="button" class="btn btn-outline" onclick="hideForm('overtime')">Batal</button>
                                <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                            </div>
                        </form>
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
                                <?php while ($ot = mysqli_fetch_assoc($my_overtime)): ?>
                                    <tr>
                                        <td><?= indonesianDate($ot['date']) ?></td>
                                        <td><?= $ot['hours'] ?> jam</td>
                                        <td><?= htmlspecialchars($ot['description']) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($ot['status']) ?>"><?= $ot['status'] ?></span></td>
                                        <td>
                                            <?php if ($ot['proof_file']): ?>
                                                <a href="../uploads/<?= $ot['proof_file'] ?>" target="_blank" class="btn btn-outline btn-sm">Lihat</a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- MY PAYROLL TAB -->
            <div class="tab-content <?= $active_tab === 'my-payroll' ? 'active' : '' ?>" id="my-payroll">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Riwayat Gaji Saya</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal Gaji</th>
                                    <th>Gaji Pokok</th>
                                    <th>Tunjangan</th>
                                    <th>Lembur</th>
                                    <th>THR</th>
                                    <th>PPH21</th>
                                    <th>Total Gaji</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($my_payroll) > 0) {
                                    while ($pay = mysqli_fetch_assoc($my_payroll)): 
                                        $total_allowance = $pay['allowance'] + $pay['transport_allowance'] + $pay['meal_allowance'] + $pay['position_allowance'];
                                ?>
                                        <tr>
                                            <td><?= $pay['pay_date'] ? date('d M Y', strtotime($pay['pay_date'])) : '-' ?></td>
                                            <td>Rp <?= number_format($pay['base_salary'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($total_allowance, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($pay['overtime_pay'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($pay['thr'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($pay['tax_pph21'], 0, ',', '.') ?></td>
                                            <td><strong>Rp <?= number_format($pay['total_salary'], 0, ',', '.') ?></strong></td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower($pay['payment_status'] ?? 'unpaid') ?>">
                                                    <?= $pay['payment_status'] ?? 'Unpaid' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline btn-sm"
                                                        onclick='viewSalarySlip(<?= json_encode($pay) ?>)'>Lihat Detail</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; 
                                } else { ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center;">Tidak ada data gaji.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- ATTENDANCE TAB -->
            <div class="tab-content <?= $active_tab === 'attendance' ? 'active' : '' ?>" id="attendance">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Absensi Karyawan</h2>
                        <form method="POST" style="margin-left: 1rem;">
                            <input type="hidden" name="export_tab" value="attendance">
                            <input type="hidden" name="export_excel" value="1">
                            <button type="submit" class="btn btn-outline">Export ke Excel</button>
                            </form>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Tanggal</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($att = mysqli_fetch_assoc($all_attendance)): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <img src="../uploads/<?= $att['photo'] ?? 'default.png' ?>" alt="Profile" 
                                                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($att['employee_name']) ?>&background=ff6b35&color=fff&size=32'">
                                                <?= htmlspecialchars($att['employee_name']) ?>
                                            </div>
                                        </td>
                                        <td><?= date('d M Y', strtotime($att['date'])) ?></td>
                                        <td><?= $att['clock_in'] ? date('H:i', strtotime($att['clock_in'])) : '-' ?></td>
                                        <td><?= $att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : '-' ?></td>
                                        <td><?= $att['mode'] ?? '-' ?></td>
                                        <td>
                                            <?php if ($att['clock_in']): ?>
                                                <span class="status-badge status-approved">Hadir</span>
                                            <?php else: ?>
                                                <span class="status-badge status-rejected">Tidak Hadir</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- EMPLOYEES TAB -->
            <div class="tab-content <?= $active_tab === 'employees' ? 'active' : '' ?>" id="employees">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Karyawan di Divisi Saya</h2>
                        <form method="POST" style="margin-left: 1rem;">
                            <input type="hidden" name="export_tab" value="employees">
                            <input type="hidden" name="export_excel" value="1">
                            <button type="submit" class="btn btn-outline">Export ke Excel</button>
                        </form>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php while ($emp = mysqli_fetch_assoc($division_employees)): 
                            $role_name = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT role_name FROM roles WHERE id = " . $emp['role_id']
                            ))['role_name'];
                        ?>
                            <div style="background: var(--card-bg); border: 1px solid var(--border-primary); border-radius: var(--radius-lg); padding: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <img src="../uploads/<?= $emp['photo'] ?? 'default.png' ?>" alt="Profile" 
                                         style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-secondary);"
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp['name']) ?>&background=ff6b35&color=fff&size=60'">
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($emp['name']) ?></div>
                                        <div style="color: var(--text-secondary); font-size: 0.9rem;"><?= $role_name ?></div>
                                    </div>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                    <div>Email: <?= htmlspecialchars($emp['email']) ?></div>
                                    <div>Status: 
                                        <span style="color: <?= $emp['status'] === 'active' ? '#16a34a' : '#ef4444' ?>;">
                                            <?= ucfirst($emp['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <!-- PAYROLL TAB -->
            <div class="tab-content <?= $active_tab === 'payroll' ? 'active' : '' ?>" id="payroll">
                <div class="content-card fade-in">
                    <div class="card-header">
                        <h2>Data Gaji Karyawan</h2>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <button type="button" class="btn btn-primary" onclick="showForm('payroll')">Tambah Data Gaji</button>
                            <form method="POST" style="margin-left: 1rem;">
                                <input type="hidden" name="export_tab" value="payroll">
                                <input type="hidden" name="export_excel" value="1">
                                <button type="submit" class="btn btn-outline">Export ke Excel</button>
                            </form>
                        </div>
                    </div>
                    <!-- FORM TAMBAH GAJI -->
                    <div id="payroll-form" class="content-card" style="display: none;">
                        <h3 style="margin-bottom: 1.5rem;">Tambah Data Gaji</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_payroll">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Karyawan</label>
                                    <select name="user_id" class="form-select" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        <?php
                                        mysqli_data_seek($division_employees, 0);
                                        while ($emp = mysqli_fetch_assoc($division_employees)):
                                            echo "<option value='{$emp['id']}'>{$emp['name']}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tanggal Gaji</label>
                                    <input type="date" name="pay_date" class="form-input" value="<?= date('Y-m-01') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gaji Pokok</label>
                                    <input type="number" name="base_salary" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tunjangan</label>
                                    <input type="number" name="allowance" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Potongan</label>
                                    <input type="number" name="deduction" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tunjangan Transport</label>
                                    <input type="number" name="transport_allowance" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tunjangan Makan</label>
                                    <input type="number" name="meal_allowance" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tunjangan Jabatan</label>
                                    <select name="position_allowance" class="form-select" required>
                                        <option value="50000">Junior (Rp 50,000)</option>
                                        <option value="100000">Senior (Rp 100,000)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">BPJS Kesehatan</label>
                                    <input type="number" name="bpjs_kes" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">BPJS Tenaga Kerja</label>
                                    <input type="number" name="bpjs_tk" class="form-input" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">THR</label>
                                    <input type="number" name="thr" class="form-input" value="0" required>
                                </div>
                                <!-- PERBAIKAN: Input persentase pajak untuk perhitungan saja -->
                                <div class="form-group">
                                    <label class="form-label">Persentase Pajak (%)</label>
                                    <input type="number" name="tax_percentage" class="form-input" value="5" step="0.01" min="0" max="100" required>
                                    <small style="color: var(--text-secondary);">Contoh: 16 untuk 16%</small>
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button type="button" class="btn btn-outline" onclick="hideForm('payroll')">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Data Gaji</button>
                            </div>
                        </form>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Tanggal Gaji</th>
                                    <th>Gaji Pokok</th>
                                    <th>Tunjangan</th>
                                    <th>Lembur</th>
                                    <th>THR</th>
                                    <th>PPH21</th>
                                    <th>Total Gaji</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pay = mysqli_fetch_assoc($payroll_data)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pay['employee_name']) ?></td>
                                        <td><?= $pay['pay_date'] ? date('M Y', strtotime($pay['pay_date'])) : '-' ?></td>
                                        <td>Rp <?= number_format($pay['base_salary'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($pay['allowance'] + $pay['transport_allowance'] + $pay['meal_allowance'] + $pay['position_allowance'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($pay['overtime_pay'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($pay['thr'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($pay['tax_pph21'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($pay['total_salary'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($pay['payment_status'] ?? 'unpaid') ?>">
                                                <?= $pay['payment_status'] ?? 'Unpaid' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- PROFILE TAB -->
            <div class="tab-content <?= $active_tab === 'profile' ? 'active' : '' ?>" id="profile">
                <div class="content-card fade-in">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <img src="../uploads/<?= $current_hr['photo'] ?? 'default.png' ?>" alt="Profile" class="profile-avatar"
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_hr['name']) ?>&background=ff6b35&color=fff&size=120'">
                        <h2 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($current_hr['name']) ?></h2>
                        <p style="color: var(--text-secondary);"><?= htmlspecialchars($current_hr['division_name'] ?? 'No Division') ?> • HR</p>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($current_hr['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($current_hr['username']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($current_hr['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah">
                                <small style="color: var(--text-secondary);">Minimal 6 karakter</small>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" name="photo" class="form-input" accept="image/*" onchange="previewFile(this)">
                            <div id="photo-preview" class="file-preview"></div>
                            <small style="color: var(--text-secondary);">Format: JPG, PNG. Maks: 5MB</small>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="reset" class="btn btn-outline">Reset</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Salary Slip Modal -->
    <div id="salaryModal" class="salary-modal">
        <div class="salary-modal-content">
            <div class="salary-modal-header">
                <h2>Slip Gaji</h2>
                <button class="salary-modal-close" onclick="closeSalaryModal()">&times;</button>
            </div>
            <div id="salaryModalContent">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </div>
    </div>
    <script>
        // TAB SYSTEM
        function switchTab(tabName) {
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            // Add active class to clicked nav item
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
            // Close sidebar on mobile
            if (window.innerWidth <= 1024) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        }
        // THEME TOGGLE
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', 
                document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            );
        }
        // Set theme from localStorage
        if (localStorage.getItem('theme') === 'dark' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        // FORM TOGGLE
        function showForm(type) {
            document.getElementById(type + '-form').style.display = 'block';
        }
        function hideForm(type) {
            document.getElementById(type + '-form').style.display = 'none';
        }
        // FILE PREVIEW FUNCTION
        function previewFile(input) {
            const previewContainer = input.parentNode.querySelector('.file-preview');
            previewContainer.style.display = 'none';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContainer.innerHTML = '';
                    
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewContainer.appendChild(img);
                    } else if (file.type === 'application/pdf') {
                        const embed = document.createElement('embed');
                        embed.src = e.target.result;
                        embed.type = 'application/pdf';
                        embed.width = '100%';
                        previewContainer.appendChild(embed);
                    }
                    
                    previewContainer.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            }
        }
        // TOGGLE MEDICAL CERTIFICATE FOR SICK LEAVE
        function toggleMedicalCertificate(select) {
            const medicalGroup = document.getElementById('medical-certificate-group');
            // Get the selected option text
            const selectedOption = select.options[select.selectedIndex];
            const leaveTypeName = selectedOption.text || selectedOption.textContent;
            
            if (leaveTypeName === 'Sakit') {
                medicalGroup.style.display = 'block';
            } else {
                medicalGroup.style.display = 'none';
                // Clear the file input if it's not sick leave
                document.querySelector('input[name="medical_certificate"]').value = '';
                document.getElementById('medical-certificate-preview').style.display = 'none';
            }
        }
        // CHARTS
        const leaveChart = new Chart(document.getElementById('leaveChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_months) ?>,
                datasets: [{
                    label: 'Pending',
                    data: <?= json_encode(array_column($leave_data, 'pending')) ?>,
                    backgroundColor: '#f59e0b'
                }, {
                    label: 'Approved',
                    data: <?= json_encode(array_column($leave_data, 'approved')) ?>,
                    backgroundColor: '#10b981'
                }, {
                    label: 'Rejected',
                    data: <?= json_encode(array_column($leave_data, 'rejected')) ?>,
                    backgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        const overtimeChart = new Chart(document.getElementById('overtimeChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_months) ?>,
                datasets: [{
                    label: 'Jam Lembur (Approved)',
                    data: <?= json_encode($ot_data) ?>,
                    borderColor: '#ff8e35',
                    backgroundColor: 'rgba(255, 142, 53, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        // Set active tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'dashboard';
            switchTab(tab);
            
            // Add event listener to theme toggle
            document.querySelector('.theme-toggle').addEventListener('click', toggleTheme);
            
            // Add event listener to salary modal close button
            document.querySelector('.salary-modal-close').addEventListener('click', closeSalaryModal);
        });
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        // Salary Slip Functions - FIXED VERSION
        function viewSalarySlip(payrollData) {
            console.log('Payroll data:', payrollData);
            
            const modalContent = document.getElementById('salaryModalContent');
            
            try {
                // Parse data jika berupa string JSON
                const payroll = typeof payrollData === 'string' ? JSON.parse(payrollData) : payrollData;
                
                // Format tanggal
                const payDate = payroll.pay_date ? new Date(payroll.pay_date) : new Date();
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const period = `${months[payDate.getMonth()]} ${payDate.getFullYear()}`;
                
                // Hitung total tunjangan dengan nilai default 0
                const totalAllowance = (parseFloat(payroll.allowance) || 0) + 
                                      (parseFloat(payroll.transport_allowance) || 0) + 
                                      (parseFloat(payroll.meal_allowance) || 0) + 
                                      (parseFloat(payroll.position_allowance) || 0);
                
                // Format Rupiah helper function
                function formatRupiah(amount) {
                    if (!amount) return '0';
                    return new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(amount)));
                }
                
                modalContent.innerHTML = `
                    <div class="salary-slip">
                        <div class="salary-header">
                            <h2>SLIP GAJI KARYAWAN</h2>
                            <p>PT. Slippy Indonesia</p>
                            <p>Periode: ${period}</p>
                        </div>
                        <div class="salary-details">
                            <div class="salary-row">
                                <span>Nama</span>
                                <span><?= htmlspecialchars($current_hr['name']) ?></span>
                            </div>
                            <div class="salary-row">
                                <span>Divisi</span>
                                <span><?= htmlspecialchars($current_hr['division_name'] ?? '-') ?></span>
                            </div>
                            <div class="salary-row">
                                <span>Jabatan</span>
                                <span>HR</span>
                            </div>
                            
                            <div class="salary-section-title">Pendapatan</div>
                            <div class="salary-row">
                                <span>Gaji Pokok</span>
                                <span>Rp ${formatRupiah(payroll.base_salary)}</span>
                            </div>
                            <div class="salary-row">
                                <span>Total Tunjangan</span>
                                <span>Rp ${formatRupiah(totalAllowance)}</span>
                            </div>
                            <div class="salary-row">
                                <span>Lembur</span>
                                <span>Rp ${formatRupiah(payroll.overtime_pay)}</span>
                            </div>
                            <div class="salary-row">
                                <span>THR</span>
                                <span>Rp ${formatRupiah(payroll.thr)}</span>
                            </div>
                            
                            <div class="salary-section-title">Rincian Tunjangan</div>
                            <div class="salary-row">
                                <span>Tunjangan Tetap</span>
                                <span>Rp ${formatRupiah(payroll.allowance)}</span>
                            </div>
                            <div class="salary-row">
                                <span>Tunjangan Transport</span>
                                <span>Rp ${formatRupiah(payroll.transport_allowance)}</span>
                            </div>
                            <div class="salary-row">
                                <span>Tunjangan Makan</span>
                                <span>Rp ${formatRupiah(payroll.meal_allowance)}</span>
                            </div>
                            <div class="salary-row">
                                <span>Tunjangan Jabatan</span>
                                <span>Rp ${formatRupiah(payroll.position_allowance)}</span>
                            </div>
                            
                            <div class="salary-section-title">Potongan</div>
                            <div class="salary-row">
                                <span>Pajak (PPH21)</span>
                                <span>Rp ${formatRupiah(payroll.tax_pph21)}</span>
                            </div>
                            
                            <div class="salary-row salary-total">
                                <span>Total Gaji Bersih</span>
                                <span>Rp ${formatRupiah(payroll.total_salary)}</span>
                            </div>
                            
                            <div class="salary-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-secondary);">
                                <span>Status Pembayaran</span>
                                <span style="font-weight: 600; color: ${(payroll.payment_status || 'Unpaid') === 'Paid' ? '#16a34a' : '#dc2626'}">
                                    ${(payroll.payment_status || 'Unpaid') === 'Paid' ? '✅ LUNAS' : '❌ BELUM BAYAR'}
                                </span>
                            </div>
                            
                            ${payroll.payment_proof ? `
                            <div class="payment-proof-section">
                                <h3>Bukti Pembayaran</h3>
                                <div style="margin-top: 0.5rem;">
                                    <a href="../uploads/${payroll.payment_proof}" target="_blank" class="btn btn-outline btn-sm">Lihat Bukti Pembayaran</a>
                                </div>
                                ${payroll.payment_proof.match(/\.(jpg|jpeg|png|gif)$/i) ? `
                                    <img src="../uploads/${payroll.payment_proof}" alt="Bukti Pembayaran" class="payment-proof-image" style="max-width: 100%; margin-top: 10px;">
                                ` : ''}
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                // Tampilkan modal
                document.getElementById('salaryModal').style.display = 'flex';
                
            } catch (error) {
                console.error('Error displaying salary slip:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-error">
                        <p>Terjadi kesalahan saat menampilkan slip gaji:</p>
                        <p>${error.message}</p>
                        <button onclick="closeSalaryModal()" class="btn btn-outline" style="margin-top: 10px;">Tutup</button>
                    </div>
                `;
                document.getElementById('salaryModal').style.display = 'flex';
            }
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
    </script>
</body>
</html>