<?php
require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

function kategoriPrepare($connection, $sql)
{
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        sendResponse(false, "Gagal menyiapkan query", null, 500);
    }
    return $stmt;
}

function kategoriExecute($stmt)
{
    if (!mysqli_stmt_execute($stmt)) {
        sendResponse(false, "Gagal menjalankan query", null, 500);
    }
}

function kategoriStatementResult($stmt)
{
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        sendResponse(false, "Gagal membaca hasil query", null, 500);
    }
    return $result;
}

function kategoriReadJson()
{
    $payload = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
        sendResponse(false, "Payload JSON tidak valid", null, 400);
    }
    return $payload;
}

function kategoriValidateId($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        sendResponse(false, "ID kategori tidak valid", null, 400);
    }
    return $id;
}

function kategoriCheckExists($connection, $id)
{
    $stmt = kategoriPrepare($connection, "SELECT id FROM kategori WHERE id = ?");
    if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
        sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
    }
    kategoriExecute($stmt);
    $result = kategoriStatementResult($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$category) {
        sendResponse(false, "Kategori tidak ditemukan", null, 404);
    }
}

switch ($method) {
    case "GET":
        if (isset($_GET["id"])) {
            $id = kategoriValidateId($_GET["id"]);
            $stmt = kategoriPrepare($koneksi, "SELECT id, nama_kategori, created_at FROM kategori WHERE id = ?");
            if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
            kategoriExecute($stmt);
            $result = kategoriStatementResult($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$data) {
                sendResponse(false, "Kategori tidak ditemukan", null, 404);
            }
            sendResponse(true, "Data kategori berhasil diambil", $data, 200);
        }

        $sql = "SELECT id, nama_kategori, created_at FROM kategori ORDER BY id ASC";
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            sendResponse(false, "Gagal mengambil data kategori", null, 500);
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        sendResponse(true, "Data kategori berhasil diambil", $data, 200);
        break;

    case "POST":
        $payload = kategoriReadJson();
        if (!isset($payload["nama_kategori"]) || trim($payload["nama_kategori"]) === "") {
            sendResponse(false, "nama_kategori wajib diisi dan tidak boleh kosong", null, 400);
        }
        $namaKategori = trim($payload["nama_kategori"]);

        $stmt = kategoriPrepare($koneksi, "INSERT INTO kategori (nama_kategori) VALUES (?)");
        if (!mysqli_stmt_bind_param($stmt, "s", $namaKategori)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        kategoriExecute($stmt);
        $id = mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);

        sendResponse(true, "Kategori berhasil ditambahkan", array("id" => $id), 201);
        break;

    case "PUT":
        if (!isset($_GET["id"])) {
            sendResponse(false, "Parameter id wajib diisi", null, 400);
        }
        $id = kategoriValidateId($_GET["id"]);
        kategoriCheckExists($koneksi, $id);

        $payload = kategoriReadJson();
        if (!isset($payload["nama_kategori"]) || trim($payload["nama_kategori"]) === "") {
            sendResponse(false, "nama_kategori wajib diisi dan tidak boleh kosong", null, 400);
        }
        $namaKategori = trim($payload["nama_kategori"]);

        $stmt = kategoriPrepare($koneksi, "UPDATE kategori SET nama_kategori = ? WHERE id = ?");
        if (!mysqli_stmt_bind_param($stmt, "si", $namaKategori, $id)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        kategoriExecute($stmt);
        mysqli_stmt_close($stmt);

        sendResponse(true, "Kategori berhasil diperbarui", array("id" => $id), 200);
        break;

    case "DELETE":
        if (!isset($_GET["id"])) {
            sendResponse(false, "Parameter id wajib diisi", null, 400);
        }
        $id = kategoriValidateId($_GET["id"]);
        kategoriCheckExists($koneksi, $id);

        // Cek relasi: Jangan hapus jika kategori masih dipakai oleh produk
        $stmtCheck = kategoriPrepare($koneksi, "SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?");
        if (!mysqli_stmt_bind_param($stmtCheck, "i", $id)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        kategoriExecute($stmtCheck);
        $resCheck = kategoriStatementResult($stmtCheck);
        $rowCheck = mysqli_fetch_assoc($resCheck);
        mysqli_stmt_close($stmtCheck);

        if ($rowCheck['total'] > 0) {
            sendResponse(false, "Kategori tidak dapat dihapus karena masih digunakan oleh produk", null, 400);
        }

        $stmt = kategoriPrepare($koneksi, "DELETE FROM kategori WHERE id = ?");
        if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        kategoriExecute($stmt);
        mysqli_stmt_close($stmt);

        sendResponse(true, "Kategori berhasil dihapus", array("id" => $id), 200);
        break;

    default:
        sendResponse(false, "Method Not Allowed", null, 405);
        break;
}