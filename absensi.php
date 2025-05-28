<?php
session_start();

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "absensi_db";
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: absensi.php");
    exit;
}

// === Proses Approve / Tolak Absen (Admin) ===
if (isset($_GET['approve']) && isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $id_absen = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE absen SET status='Disetujui' WHERE id=$id_absen");
    header("Location: absensi.php?lihat_absen=1");
    exit;
}
if (isset($_GET['reject']) && isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $id_absen = intval($_GET['reject']);
    mysqli_query($conn, "UPDATE absen SET status='Ditolak' WHERE id=$id_absen");
    header("Location: absensi.php?lihat_absen=1");
    exit;
}

// === Proses Kelola User (Admin) ===
// Tambah User
if (isset($_POST['tambah_user']) && $_SESSION['level'] == 'admin') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $level    = mysqli_real_escape_string($conn, $_POST['level']);

    // Cek username sudah ada atau belum
    $cek_user = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek_user) == 0) {
        mysqli_query($conn, "INSERT INTO users (username, password, level) VALUES ('$username', '$password', '$level')");
        $msg_user = "User berhasil ditambahkan.";
    } else {
        $error_user = "Username sudah terdaftar.";
    }
}

// Edit User
if (isset($_POST['edit_user']) && $_SESSION['level'] == 'admin') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $level    = mysqli_real_escape_string($conn, $_POST['level']);
    // Update user, password boleh dikosongkan jika tidak ingin ganti
    if (!empty($password)) {
        mysqli_query($conn, "UPDATE users SET password='$password', level='$level' WHERE username='$username'");
    } else {
        mysqli_query($conn, "UPDATE users SET level='$level' WHERE username='$username'");
    }
    $msg_user = "User berhasil diupdate.";
}

// Hapus User
if (isset($_GET['hapus_user']) && $_SESSION['level'] == 'admin') {
    $hapus_user = mysqli_real_escape_string($conn, $_GET['hapus_user']);
    mysqli_query($conn, "DELETE FROM users WHERE username='$hapus_user'");
    header("Location: absensi.php?kelola_user=1");
    exit;
}

// === Login ===
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $level    = mysqli_real_escape_string($conn, $_POST['level']);

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password' AND level='$level'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_array($result);

    if ($data) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['level'] = $data['level'];
    } else {
        $error = "Login gagal. Username atau password salah.";
    }
}

// === Proses Absen (Siswa) ===
if (isset($_POST['isi_absen']) && isset($_SESSION['level']) && $_SESSION['level'] == 'siswa') {
    $username = $_SESSION['username'];
    $tanggal = date('Y-m-d');
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Cek apakah sudah absen hari ini
    $cek = mysqli_query($conn, "SELECT * FROM absen WHERE username='$username' AND tanggal='$tanggal'");
    if ($cek && mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO absen (username, tanggal, keterangan, status) VALUES ('$username', '$tanggal', '$keterangan', 'Pending')");
        $msg = "Absen berhasil disimpan dan menunggu persetujuan admin.";
    } else {
        $msg = "Anda sudah absen hari ini.";
    }
}

?>
