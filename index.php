<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "123456", "db_resep_restoran");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]));
}

if (!file_exists('uploads/foto')) mkdir('uploads/foto', 0777, true);
if (!file_exists('uploads/dokumen')) mkdir('uploads/dokumen', 0777, true);

function proses_upload_base64($file_array, $folder_tujuan, $prefix_nama) {
    if (isset($file_array) && $file_array['error'] == 0) {
        $file_data = file_get_contents($file_array['tmp_name']);
        $base64_string = base64_encode($file_data);
        $ext = pathinfo($file_array['name'], PATHINFO_EXTENSION);

        $file_decoded = base64_decode($base64_string);
        
        if ($file_decoded) {
            $filename = $prefix_nama . "_" . time() . "_" . rand(100,999) . "." . $ext;
            file_put_contents($folder_tujuan . "/" . $filename, $file_decoded);
            
            return [
                'filename' => $filename,
                'bukti_base64' => substr($base64_string, 0, 500) . "..."
            ];
        }
    }
    return ['filename' => null, 'bukti_base64' => null];
}

function parseMultipartPut() {
    $put_data = [];
    $put_files = [];
    
    $raw_data = file_get_contents('php://input');
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
        $boundary = $matches[1];
        $parts = explode("--" . $boundary, $raw_data);
        
        foreach ($parts as $part) {
            if (trim($part) == "" || trim($part) == "--") continue;
            
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);
            $body = substr($body, 0, strlen($body) - 2); 
            
            if (preg_match('/name="([^"]+)"/', $raw_headers, $name_matches)) {
                $name = $name_matches[1];
                
                if (preg_match('/filename="([^"]+)"/', $raw_headers, $filename_matches)) {
                    $filename = $filename_matches[1];
                    if (!empty($filename)) {
                        $tmp_path = sys_get_temp_dir() . '/put_file_' . uniqid();
                        file_put_contents($tmp_path, $body);
                        $put_files[$name] = [
                            'name' => $filename,
                            'tmp_name' => $tmp_path,
                            'size' => filesize($tmp_path),
                            'error' => 0
                        ];
                    }
                } else {
                    $put_data[$name] = $body;
                }
            }
        }
    }
    return [$put_data, $put_files];
}

$method = $_SERVER['REQUEST_METHOD'];

$_PUT = [];
$_PUT_FILES = [];

if ($method === 'PUT') {
    list($_PUT, $_PUT_FILES) = parseMultipartPut();
}

switch ($method) {

    case 'GET':
        $id = $_GET['id_resep'] ?? null;
        $sql = $id ? "SELECT * FROM resep_masakan WHERE id_resep = '$id'" : "SELECT * FROM resep_masakan";
        $result = $conn->query($sql);
        $data = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['url_foto'] = "http://localhost/api/uploads/foto/" . $row['foto_masakan'];
                $row['url_dokumen'] = "http://localhost/api/uploads/dokumen/" . $row['dokumen_resep'];
                $data[] = $row;
            }
        }
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST':
        $nama = $_POST['nama_masakan'] ?? '';
        $asal = $_POST['asal_masakan'] ?? '';
        
        if (!isset($_FILES['foto_masakan']) || !isset($_FILES['dokumen_resep'])) {
            die(json_encode(["status" => "error", "message" => "Foto dan dokumen resep wajib diupload!"]));
        }

        $upload_foto = proses_upload_base64($_FILES['foto_masakan'], 'uploads/foto', 'foto');
        $upload_dokumen = proses_upload_base64($_FILES['dokumen_resep'], 'uploads/dokumen', 'dok');

        $sql = "INSERT INTO resep_masakan (nama_masakan, asal_masakan, foto_masakan, dokumen_resep) 
                VALUES ('$nama', '$asal', '{$upload_foto['filename']}', '{$upload_dokumen['filename']}')";
        
        if ($conn->query($sql)) {
            echo json_encode([
                "status" => "success",
                "message" => "Resep berhasil ditambahkan!",
                "bukti_base64" => [
                    "foto" => $upload_foto['bukti_base64'],
                    "dokumen" => $upload_dokumen['bukti_base64']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    case 'PUT':
        $id = $_GET['id_resep'] ?? '';
        if (empty($id)) {
            die(json_encode(["status" => "error", "message" => "id_resep wajib ada!"]));
        }

        $nama = $_PUT['nama_masakan'] ?? '';
        $asal = $_PUT['asal_masakan'] ?? '';

        $sql = "UPDATE resep_masakan 
                SET nama_masakan='$nama', asal_masakan='$asal' 
                WHERE id_resep='$id'";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Data resep berhasil diupdate"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $del_data);
        $id = $del_data['id_resep'] ?? '';

        if (empty($id)) {
            die(json_encode(["status" => "error", "message" => "id_resep wajib diisi!"]));
        }

        $sql = "DELETE FROM resep_masakan WHERE id_resep='$id'";
        
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Data resep berhasil dihapus"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
        break;
}

$conn->close();
?>