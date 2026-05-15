<?php
include 'connection.php';

$total_accounts = 200;
$default_password = 'password123';
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

$first_names = ['Agus','Budi','Citra','Dewi','Eka','Farhan','Gita','Hadi','Indah','Joko',
                'Kartika','Lina','Maman','Nadia','Oscar','Putri','Qori','Rudi','Sari','Teguh',
                'Umi','Vina','Wawan','Xena','Yudi','Zahra','Andi','Bella','Cahyo','Dina'];
$last_names = ['Santoso','Putri','Wijaya','Kusuma','Pratiwi','Hidayat','Nugroho','Wulandari',
               'Saputra','Lestari','Gunawan','Utami','Purnomo','Mulyani','Setiawan','Hartono'];
$divisions = [1,2,3,4,5,6,7];

$success = 0;
$failed = 0;
$csv_data = [];

for ($i = 1; $i <= $total_accounts; $i++) {
    $first = $first_names[array_rand($first_names)];
    $last = $last_names[array_rand($last_names)];
    $name = $first . ' ' . $last . ' ' . $i;
    
    // Generate username unik (base)
    $base_username = strtolower($first . $i);
    $username = $base_username;
    $username_counter = 0;
    
    // Generate email unik
    $email = strtolower($first . '.' . $last . $i . '@dummy.com');
    $division = $divisions[array_rand($divisions)];
    
    // Cek duplikat username dan email, jika ada, modifikasi username
    while (true) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($check) == 0) {
            break; // Tidak ada duplikat, lanjut insert
        }
        // Jika duplikat, tambahkan suffix angka pada username
        $username_counter++;
        $username = $base_username . '_' . $username_counter;
        // Email juga perlu diubah? Tidak, email sudah unik berdasarkan i, jadi aman.
        // Tapi jika email bentrok (karena i bisa sama dengan yang sudah ada), tambahkan counter juga ke email
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            // Email bentrok, buat email baru dengan tambahan angka
            $email = strtolower($first . '.' . $last . $i . '_' . $username_counter . '@dummy.com');
        }
    }
    
    $sql = "INSERT INTO users (name, username, email, password, role_id, division_id, status, annual_leave_stock) 
            VALUES ('$name', '$username', '$email', '$hashed_password', 4, $division, 'active', 12)";
    
    if (mysqli_query($conn, $sql)) {
        $success++;
        $csv_data[] = [$email, $default_password];
        echo "✅ $success. $email (username: $username)<br>";
    } else {
        $failed++;
        echo "❌ Gagal: $email - " . mysqli_error($conn) . "<br>";
    }
    
    if ($i % 50 == 0) {
        ob_flush();
        flush();
    }
}

$csv_file = 'employees_200.csv';
$fp = fopen($csv_file, 'w');
fputcsv($fp, ['email', 'password']);
foreach ($csv_data as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo "<hr>";
echo "📊 Selesai! Berhasil: $success, Gagal: $failed<br>";
echo "📁 File CSV untuk JMeter: <strong>$csv_file</strong><br>";
echo "🔐 Password semua akun: <strong>$default_password</strong><br>";
echo "📍 Lokasi file: " . __DIR__ . "/$csv_file";
?>