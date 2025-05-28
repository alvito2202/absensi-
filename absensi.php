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
<!DOCTYPE html>
<html>
<head>
    <title>Sistem Absensi</title>
    <style>
        /* CSS tetap sama seperti sebelumnya */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7f9fc;
            margin: 0;
            padding-bottom: 50px;
            color: #333;
        }
        .navbar {
            background-color: #4a90e2;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .navbar .brand {
            font-weight: 700;
            font-size: 22px;
        }
        .navbar a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-weight: 600;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h2, h3 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        form, .box {
            margin-top: 20px;
        }
        input, select, button {
            margin: 10px 0;
            padding: 12px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input:focus, select:focus {
            border-color: #4a90e2;
            outline: none;
        }
        button {
            background-color: #4a90e2;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border: none;
            border-radius: 8px;
        }
        button:hover {
            background-color: #357ABD;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #4a90e2;
            color: white;
            font-weight: 600;
        }
        td {
            background-color: #f9f9f9;
        }
        .msg {
            color: #27ae60;
            font-weight: 600;
            margin-top: 12px;
        }
        .error {
            color: #e74c3c;
            font-weight: 600;
            margin-top: 12px;
        }
        .btn-approve {
            background-color: #27ae60;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            margin: 0 5px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-approve:hover {
            background-color: #1e8449;
        }
        .btn-reject {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            margin: 0 5px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-reject:hover {
            background-color: #c0392b;
        }
        .status-pending { color: #f39c12; font-weight: 600; }
        .status-approved { color: #27ae60; font-weight: 600; }
        .status-rejected { color: #e74c3c; font-weight: 600; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['username'])): ?>
    <div class="navbar">
        <div class="brand">Dashboard - <?= ucfirst(htmlspecialchars($_SESSION['level'])) ?></div>
        <div class="menu">
            <a href="absensi.php">Home</a>
            <?php if ($_SESSION['level'] == 'admin'): ?>
                <a href="?lihat_absen=1">Lihat Absen</a>
                <a href="?kelola_user=1">Kelola User</a>
            <?php endif; ?>
            <a href="?logout=1">Logout</a>
        </div>
    </div>
<?php endif; ?>

<div class="container">
    <?php if (!isset($_SESSION['username'])): ?>
        <!-- Form Login -->
        <h2>Login Sistem Absensi</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <select name="level" required>
                <option value="">-- Pilih Level --</option>
                <option value="admin">Admin/Guru</option>
                <option value="siswa">Siswa</option>
            </select>
            <button type="submit" name="login">Login</button>
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </form>

    <?php elseif ($_SESSION['level'] == 'admin'): ?>

        <?php if (isset($_GET['lihat_absen'])): ?>
            <h3>Data Absen Hari Ini</h3>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php
                $today = date('Y-m-d');
                $absen = mysqli_query($conn, "SELECT * FROM absen WHERE tanggal='$today' ORDER BY username ASC");
                if ($absen && mysqli_num_rows($absen) > 0) {
                    while ($row = mysqli_fetch_assoc($absen)) {
                        $status = $row['status'];
                        $status_class = '';
                        if ($status == 'Pending') $status_class = "status-pending";
                        else if ($status == 'Disetujui') $status_class = "status-approved";
                        else if ($status == 'Ditolak') $status_class = "status-rejected";

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                        echo "<td class='$status_class'>" . htmlspecialchars($status) . "</td>";
                        echo "<td>";
                        if ($status == 'Pending') {
                            echo "<a class='btn-approve' href='?approve=" . $row['id'] . "' onclick='return confirm(\"Setujui absen ini?\")'>Setujui</a>";
                            echo "<a class='btn-reject' href='?reject=" . $row['id'] . "' onclick='return confirm(\"Tolak absen ini?\")'>Tolak</a>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Belum ada data absen hari ini.</td></tr>";
                }
                ?>
            </table>

        <?php elseif (isset($_GET['kelola_user'])): ?>
            <h3>Kelola User</h3>

            <!-- Form Tambah User -->
            <form method="POST" action="">
                <h4>Tambah User Baru</h4>
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="password" placeholder="Password" required>
                <select name="level" required>
                    <option value="">-- Pilih Level --</option>
                    <option value="admin">Admin</option>
                    <option value="siswa">Siswa</option>
                </select>
                <button type="submit" name="tambah_user">Tambah User</button>
                <?php if (!empty($msg_user)) echo "<p class='msg'>$msg_user</p>"; ?>
                <?php if (!empty($error_user)) echo "<p class='error'>$error_user</p>"; ?>
            </form>

            <!-- Daftar User -->
            <h4>Daftar User</h4>
            <table>
                <tr><th>Username</th><th>Level</th><th>Aksi</th></tr>
                <?php
                $users = mysqli_query($conn, "SELECT * FROM users ORDER BY username ASC");
                while ($row = mysqli_fetch_assoc($users)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['level']) . "</td>";
                    echo "<td>";
                    echo "<a href='?edit_user=" . urlencode($row['username']) . "'>Edit</a> | ";
                    echo "<a href='?hapus_user=" . urlencode($row['username']) . "' onclick='return confirm(\"Hapus user ini?\")'>Hapus</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </table>

            <?php
            // Form Edit User
            if (isset($_GET['edit_user'])):
                $edit_username = mysqli_real_escape_string($conn, $_GET['edit_user']);
                $user_edit = mysqli_query($conn, "SELECT * FROM users WHERE username='$edit_username'");
                if ($user_edit && mysqli_num_rows($user_edit) == 1):
                    $ue = mysqli_fetch_assoc($user_edit);
            ?>
                <h4>Edit User: <?= htmlspecialchars($ue['username']) ?></h4>
                <form method="POST" action="">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($ue['username']) ?>">
                    <input type="text" name="password" placeholder="Password baru (kosongkan jika tidak ganti)">
                    <select name="level" required>
                        <option value="admin" <?= $ue['level'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="siswa" <?= $ue['level'] == 'siswa' ? 'selected' : '' ?>>Siswa</option>
                    </select>
                    <button type="submit" name="edit_user">Update User</button>
                </form>
            <?php
                endif;
            endif;
            ?>

        <?php else: ?>
            <h3>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
            <p>Gunakan menu di atas untuk mengelola data.</p>
        <?php endif; ?>

    <?php elseif ($_SESSION['level'] == 'siswa'): ?>
        <h3>Isi Absensi Hari Ini</h3>
        <form method="POST" action="">
            <select name="keterangan" required>
                <option value="">-- Pilih Keterangan --</option>
                <option value="Hadir">Hadir</option>
                <option value="Izin">Izin</option>
                <option value="Sakit">Sakit</option>
                <option value="Alpha">Alpha</option>
            </select>
            <button type="submit" name="isi_absen">Isi Absensi</button>
        </form>
        <?php if (!empty($msg)): ?>
            <p class="msg"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>

        <h3>Status Absensi Hari Ini</h3>
        <table>
            <tr><th>Tanggal</th><th>Keterangan</th><th>Status</th></tr>
            <?php
            $username = $_SESSION['username'];
            $today = date('Y-m-d');
            $absen_siswa = mysqli_query($conn, "SELECT * FROM absen WHERE username='$username' AND tanggal='$today'");
            if ($absen_siswa && mysqli_num_rows($absen_siswa) > 0) {
                $row = mysqli_fetch_assoc($absen_siswa);
                $status = $row['status'];
                $status_class = "";
                if ($status == 'Pending') $status_class = "status-pending";
                else if ($status == 'Disetujui') $status_class = "status-approved";
                else if ($status == 'Ditolak') $status_class = "status-rejected";

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";
                echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                echo "<td class='$status_class'>" . htmlspecialchars($status) . "</td>";
                echo "</tr>";
            } else {
                echo "<tr><td colspan='3'>Anda belum mengisi absensi hari ini.</td></tr>";
            }
            ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
