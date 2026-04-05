<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

$conn = new mysqli("localhost", "root", "123456", "db_resep_restoran");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]));
}

// Pastikan folder ada
if (!file_exists('uploads/foto')) mkdir('uploads/foto', 0777, true);
if (!file_exists('uploads/dokumen')) mkdir('uploads/dokumen', 0777, true);

// ================== FUNCTION UPLOAD ==================
function upload_file($file, $folder, $prefix) {
    if (isset($file) && $file['error'] == 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . "_" . time() . "_" . rand(100,999) . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], "$folder/$filename")) {
            return $filename;
        }
    }
    return null;
}

// ================== METHOD DETECTION ==================
$method = $_SERVER['REQUEST_METHOD'];

// Logika Spoofing: Jika POST membawa _method=PUT, anggap ini PUT
if ($method == 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) == 'PUT') {
    $method = 'PUT';
}

switch ($method) {
    case 'GET':
        $id = $_GET['id_resep'] ?? null;
        $sql = $id ? "SELECT * FROM resep_masakan WHERE id_resep='$id'" : "SELECT * FROM resep_masakan";
        $result = $conn->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $row['url_foto'] = "http://localhost/uploads/foto/" . $row['foto_masakan'];
            $row['url_dokumen'] = "http://localhost/uploads/dokumen/" . $row['dokumen_resep'];
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST':
        $nama = $_POST['nama_masakan'] ?? '';
        $asal = $_POST['asal_masakan'] ?? '';

        if (!isset($_FILES['foto_masakan']) || !isset($_FILES['dokumen_resep'])) {
            die(json_encode(["status" => "error", "message" => "Foto & dokumen wajib!"]));
        }

        $foto = upload_file($_FILES['foto_masakan'], 'uploads/foto', 'foto');
        $dokumen = upload_file($_FILES['dokumen_resep'], 'uploads/dokumen', 'dok');

        $sql = "INSERT INTO resep_masakan (nama_masakan, asal_masakan, foto_masakan, dokumen_resep) 
                VALUES ('$nama', '$asal', '$foto', '$dokumen')";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Data berhasil ditambahkan"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;
case 'PUT':
        $id = $_GET['id_resep'] ?? '';
        if (!$id) {
            die(json_encode(["status" => "error", "message" => "id_resep tidak ada di URL"]));
        }

        $res = $conn->query("SELECT * FROM resep_masakan WHERE id_resep='$id'");
        if ($res->num_rows == 0) {
            die(json_encode(["status" => "error", "message" => "Data ID $id tidak ada di DB"]));
        }
        $old = $res->fetch_assoc();

        // Perbaikan pengambilan data: utamakan POST, kalau kosong pakai yang lama
        $nama = (isset($_POST['nama_masakan']) && $_POST['nama_masakan'] !== '') ? $_POST['nama_masakan'] : $old['nama_masakan'];
        $asal = (isset($_POST['asal_masakan']) && $_POST['asal_masakan'] !== '') ? $_POST['asal_masakan'] : $old['asal_masakan'];
        
        $foto = $old['foto_masakan'];
        $dokumen = $old['dokumen_resep'];

        if (isset($_FILES['foto_masakan']) && $_FILES['foto_masakan']['error'] == 0) {
            if ($foto && file_exists("uploads/foto/$foto")) @unlink("uploads/foto/$foto");
            $foto = upload_file($_FILES['foto_masakan'], 'uploads/foto', 'foto');
        }

        if (isset($_FILES['dokumen_resep']) && $_FILES['dokumen_resep']['error'] == 0) {
            if ($dokumen && file_exists("uploads/dokumen/$dokumen")) @unlink("uploads/dokumen/$dokumen");
            $dokumen = upload_file($_FILES['dokumen_resep'], 'uploads/dokumen', 'dok');
        }

        // Jalankan Update
        $sql = "UPDATE resep_masakan SET 
                nama_masakan='$nama', 
                asal_masakan='$asal', 
                foto_masakan='$foto', 
                dokumen_resep='$dokumen' 
                WHERE id_resep='$id'";

        if ($conn->query($sql)) {
            // Kita cek apakah benar-benar ada baris yang berubah di MySQL
            if ($conn->affected_rows > 0) {
                echo json_encode(["status" => "success", "message" => "Update ID $id BERHASIL diubah"]);
            } else {
                echo json_encode(["status" => "success", "message" => "Query jalan, tapi data sama dengan yang lama (tidak ada perubahan)"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    case 'DELETE':
        // Untuk DELETE murni tanpa file, kita bisa pakai php://input
        parse_str(file_get_contents("php://input"), $del);
        $id = $del['id_resep'] ?? ($_GET['id_resep'] ?? '');

        if (!$id) {
            die(json_encode(["status" => "error", "message" => "id_resep wajib!"]));
        }

        $sql = "DELETE FROM resep_masakan WHERE id_resep='$id'";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Data $id dihapus"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;
}

$conn->close();
?>
