<?php
require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

function menuPrepare($connection, $sql)
{
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        sendResponse(false, "Gagal menyiapkan query", null, 500);
    }
    return $stmt;
}

function menuExecute($stmt)
{
    if (!mysqli_stmt_execute($stmt)) {
        sendResponse(false, "Gagal menjalankan query", null, 500);
    }
}

function menuStatementResult($stmt)
{
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        sendResponse(false, "Gagal membaca hasil query", null, 500);
    }
    return $result;
}

function menuReadJson()
{
    $payload = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
        sendResponse(false, "Payload JSON tidak valid", null, 400);
    }
    return $payload;
}

function menuValidateFields($payload, $fields)
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $payload) || $payload[$field] === "") {
            sendResponse(false, "Field " . $field . " wajib diisi", null, 400);
        }
    }

    if (!is_string($payload["nama_produk"]) || trim($payload["nama_produk"]) === "") {
        sendResponse(false, "nama_produk harus berupa teks dan tidak boleh kosong", null, 400);
    }
    if (!is_numeric($payload["harga"]) || (float) $payload["harga"] < 0) {
        sendResponse(false, "harga harus berupa angka yang valid", null, 400);
    }
    if (filter_var($payload["kategori_id"], FILTER_VALIDATE_INT) === false || (int) $payload["kategori_id"] < 1) {
        sendResponse(false, "kategori_id harus berupa bilangan bulat positif", null, 400);
    }
}

function menuValidateId($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        sendResponse(false, "ID menu tidak valid", null, 400);
    }
    return $id;
}

function menuCheckCategory($connection, $categoryId)
{
    $stmt = menuPrepare($connection, "SELECT id FROM kategori WHERE id = ?");
    if (!mysqli_stmt_bind_param($stmt, "i", $categoryId)) {
        sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
    }
    menuExecute($stmt);
    $result = menuStatementResult($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$category) {
        sendResponse(false, "Kategori tidak ditemukan", null, 400);
    }
}

function menuCheckExists($connection, $id)
{
    $stmt = menuPrepare($connection, "SELECT id FROM produk WHERE id = ?");
    if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
        sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
    }
    menuExecute($stmt);
    $result = menuStatementResult($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$product) {
        sendResponse(false, "Menu tidak ditemukan", null, 404);
    }
}

switch ($method) {
    case "GET":
        if (isset($_GET["id"])) {
            $id = menuValidateId($_GET["id"]);
            $stmt = menuPrepare(
                $koneksi,
                "SELECT p.id, p.nama_produk, p.harga, p.gambar, p.kategori_id,
                        p.created_at, k.nama_kategori
                 FROM produk p
                 JOIN kategori k ON p.kategori_id = k.id
                 WHERE p.id = ?"
            );
            if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
            menuExecute($stmt);
            $result = menuStatementResult($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$data) {
                sendResponse(false, "Menu tidak ditemukan", null, 404);
            }
            sendResponse(true, "Data menu berhasil diambil", $data, 200);
        }

        $hasSearch = isset($_GET["search"]);
        $hasPagination = isset($_GET["page"]) || isset($_GET["limit"]);
        $sql = "SELECT p.id, p.nama_produk, p.harga, p.gambar, p.kategori_id,
                       p.created_at, k.nama_kategori
                FROM produk p
                JOIN kategori k ON p.kategori_id = k.id";
        $types = "";
        $params = array();

        if ($hasSearch) {
            if (!is_string($_GET["search"]) && !is_numeric($_GET["search"])) {
                sendResponse(false, "Parameter search tidak valid", null, 400);
            }
            $sql .= " WHERE p.nama_produk LIKE ?";
            $types .= "s";
            $params[] = "%" . $_GET["search"] . "%";
        }

        if ($hasPagination) {
            $page = isset($_GET["page"]) ? filter_var($_GET["page"], FILTER_VALIDATE_INT) : 1;
            $limit = isset($_GET["limit"]) ? filter_var($_GET["limit"], FILTER_VALIDATE_INT) : 10;
            if ($page === false || $page < 1 || $limit === false || $limit < 1) {
                sendResponse(false, "Parameter page dan limit harus berupa bilangan bulat positif", null, 400);
            }
            if (($page - 1) > intdiv(PHP_INT_MAX, $limit)) {
                sendResponse(false, "Kombinasi page dan limit terlalu besar", null, 400);
            }

            $offset = ($page - 1) * $limit;
            $sql .= " ORDER BY p.id LIMIT ? OFFSET ?";
            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;
        } else {
            $sql .= " ORDER BY p.id";
        }

        $stmt = menuPrepare($koneksi, $sql);
        if ($types !== "") {
            $bindParams = array($stmt, $types);
            foreach ($params as $index => $value) {
                $params[$index] = $value;
                $bindParams[] = &$params[$index];
            }
            if (!call_user_func_array("mysqli_stmt_bind_param", $bindParams)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
        }
        menuExecute($stmt);
        $result = menuStatementResult($stmt);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        mysqli_stmt_close($stmt);
        sendResponse(true, "Data menu berhasil diambil", $data, 200);
        break;

    case "POST":
        $payload = menuReadJson();
        menuValidateFields($payload, array("nama_produk", "harga", "kategori_id"));
        $namaProduk = trim($payload["nama_produk"]);
        $harga = (int) $payload["harga"];
        $kategoriId = (int) $payload["kategori_id"];
        $gambar = isset($payload["gambar"]) && $payload["gambar"] !== ""
            ? $payload["gambar"]
            : "default.jpg";

        if (!is_string($gambar)) {
            sendResponse(false, "gambar harus berupa teks", null, 400);
        }
        menuCheckCategory($koneksi, $kategoriId);

        $stmt = menuPrepare(
            $koneksi,
            "INSERT INTO produk (nama_produk, harga, gambar, kategori_id)
             VALUES (?, ?, ?, ?)"
        );
        if (!mysqli_stmt_bind_param($stmt, "sisi", $namaProduk, $harga, $gambar, $kategoriId)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        menuExecute($stmt);
        $id = mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmt);
        sendResponse(true, "Menu berhasil ditambahkan", array("id" => $id), 201);
        break;

    case "PUT":
        if (!isset($_GET["id"])) {
            sendResponse(false, "Parameter id wajib diisi", null, 400);
        }
        $id = menuValidateId($_GET["id"]);
        menuCheckExists($koneksi, $id);

        $payload = menuReadJson();
        menuValidateFields($payload, array("nama_produk", "harga", "kategori_id"));
        $namaProduk = trim($payload["nama_produk"]);
        $harga = (int) $payload["harga"];
        $kategoriId = (int) $payload["kategori_id"];
        menuCheckCategory($koneksi, $kategoriId);

        $stmt = menuPrepare(
            $koneksi,
            "UPDATE produk
             SET nama_produk = ?, harga = ?, kategori_id = ?
             WHERE id = ?"
        );
        if (!mysqli_stmt_bind_param($stmt, "siii", $namaProduk, $harga, $kategoriId, $id)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        menuExecute($stmt);
        mysqli_stmt_close($stmt);
        sendResponse(true, "Menu berhasil diperbarui", array("id" => $id), 200);
        break;

    case "DELETE":
        if (!isset($_GET["id"])) {
            sendResponse(false, "Parameter id wajib diisi", null, 400);
        }
        $id = menuValidateId($_GET["id"]);
        menuCheckExists($koneksi, $id);

        $stmt = menuPrepare($koneksi, "DELETE FROM produk WHERE id = ?");
        if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
            sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
        }
        menuExecute($stmt);
        mysqli_stmt_close($stmt);
        sendResponse(true, "Menu berhasil dihapus", array("id" => $id), 200);
        break;

    default:
        sendResponse(false, "Method Not Allowed", null, 405);
        break;
}