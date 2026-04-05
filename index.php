<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

$conn = new mysqli("localhost", "root", "123456", "db_resep_restoran");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi gagal"]));
}

if (!file_exists('uploads/foto')) mkdir('uploads/foto', 0777, true);
if (!file_exists('uploads/dokumen')) mkdir('uploads/dokumen', 0777, true);

// ================= HELPER NAMA FILE =================
function generate_nama_file($nama, $asal, $ext, $prefix) {
    $nama = strtolower(str_replace(' ', '_', $nama));
    $asal = strtolower(str_replace(' ', '_', $asal));
    return $prefix . "_" . $nama . "_" . $asal . "." . $ext;
}

// ================= UPLOAD + BASE64 =================
function upload_file_base64($file, $folder, $nama, $asal, $prefix) {
    if (!isset($file) || $file['error'] != 0) return ["filename" => null, "base64" => null];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generate_nama_file($nama, $asal, $ext, $prefix);

    move_uploaded_file($file['tmp_name'], "$folder/$filename");

    // generate base64 dari file yg sudah disimpan
    $file_data = file_get_contents("$folder/$filename");
    $base64 = base64_encode($file_data);

    return [
        "filename" => $filename,
        "base64" => "data:application/octet-stream;base64," . substr($base64,0,200) . "..."
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ================= GET =================
    case 'GET':
        $res = $conn->query("SELECT * FROM resep_masakan");
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $row['url_foto'] = "http://localhost/uploads/foto/".$row['foto_masakan'];
            $row['url_dokumen'] = "http://localhost/uploads/dokumen/".$row['dokumen_resep'];
            $data[] = $row;
        }
        echo json_encode(["status"=>"success","data"=>$data]);
        break;

    // ================= POST =================
    case 'POST':
        $nama = $_POST['nama_masakan'] ?? '';
        $asal = $_POST['asal_masakan'] ?? '';

        $foto = upload_file_base64($_FILES['foto_masakan'], 'uploads/foto', $nama, $asal, 'foto');
        $dok = upload_file_base64($_FILES['dokumen_resep'], 'uploads/dokumen', $nama, $asal, 'dok');

        $sql = "INSERT INTO resep_masakan (nama_masakan, asal_masakan, foto_masakan, dokumen_resep)
                VALUES ('$nama','$asal','{$foto['filename']}','{$dok['filename']}')";

        if ($conn->query($sql)) {
            echo json_encode([
                "status"=>"success",
                "message"=>"Data ditambahkan",
                "bukti_base64"=>[
                    "foto"=>$foto['base64'],
                    "dokumen"=>$dok['base64']
                ]
            ]);
        }
        break;

    // ================= PUT =================
    case 'PUT':
        $id = $_GET['id_resep'] ?? '';
        if (!$id) die(json_encode(["status"=>"error","message"=>"id wajib"]));

        parse_str(file_get_contents("php://input"), $put);

        $res = $conn->query("SELECT * FROM resep_masakan WHERE id_resep='$id'");
        $old = $res->fetch_assoc();

        $nama = $put['nama_masakan'] ?? $old['nama_masakan'];
        $asal = $put['asal_masakan'] ?? $old['asal_masakan'];

        $sql = "UPDATE resep_masakan SET nama_masakan='$nama', asal_masakan='$asal' WHERE id_resep='$id'";

        if ($conn->query($sql)) {
            echo json_encode(["status"=>"success","message"=>"Update berhasil"]);
        }
        break;

    // ================= DELETE =================
    case 'DELETE':
        $id = $_GET['id_resep'] ?? '';
        if (!$id) die(json_encode(["status"=>"error","message"=>"id wajib"]));

        $res = $conn->query("SELECT * FROM resep_masakan WHERE id_resep='$id'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();

            @unlink("uploads/foto/".$row['foto_masakan']);
            @unlink("uploads/dokumen/".$row['dokumen_resep']);
        }

        $conn->query("DELETE FROM resep_masakan WHERE id_resep='$id'");

        echo json_encode(["status"=>"success","message"=>"Data & file terhapus"]);
        break;
}

$conn->close();
?>
