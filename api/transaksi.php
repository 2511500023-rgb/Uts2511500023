<?php
require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

function transaksiPrepare($connection, $sql)
{
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        sendResponse(false, "Gagal menyiapkan query", null, 500);
    }
    return $stmt;
}

function transaksiExecute($stmt)
{
    if (!mysqli_stmt_execute($stmt)) {
        sendResponse(false, "Gagal menjalankan query", null, 500);
    }
}

function transaksiStatementResult($stmt)
{
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        sendResponse(false, "Gagal membaca hasil query", null, 500);
    }
    return $result;
}

function transaksiReadJson()
{
    $payload = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
        sendResponse(false, "Payload JSON tidak valid", null, 400);
    }
    return $payload;
}

switch ($method) {
    case "GET":
        // 1. Ambil 1 Detail Transaksi Lengkap (via ?id=)
        if (isset($_GET["id"])) {
            $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                sendResponse(false, "ID transaksi tidak valid", null, 400);
            }

            // Ambil Header Transaksi
            $stmtHeader = transaksiPrepare(
                $koneksi,
                "SELECT id, no_faktur, subtotal, pajak, total_bayar, created_at 
                 FROM transaksi WHERE id = ?"
            );
            if (!mysqli_stmt_bind_param($stmtHeader, "i", $id)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
            transaksiExecute($stmtHeader);
            $resHeader = transaksiStatementResult($stmtHeader);
            $header = mysqli_fetch_assoc($resHeader);
            mysqli_stmt_close($stmtHeader);

            if (!$header) {
                sendResponse(false, "Transaksi tidak ditemukan", null, 404);
            }

            // Ambil Rincian Item (JOIN dengan tabel produk)
            $stmtDetail = transaksiPrepare(
                $koneksi,
                "SELECT d.id, d.produk_id, p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
                 FROM detail_transaksi d
                 JOIN produk p ON d.produk_id = p.id
                 WHERE d.transaksi_id = ?"
            );
            if (!mysqli_stmt_bind_param($stmtDetail, "i", $id)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
            transaksiExecute($stmtDetail);
            $resDetail = transaksiStatementResult($stmtDetail);
            $items = [];
            while ($row = mysqli_fetch_assoc($resDetail)) {
                $items[] = $row;
            }
            mysqli_stmt_close($stmtDetail);

            $header["items"] = $items;
            sendResponse(true, "Data detail transaksi berhasil diambil", $header, 200);
        }

        // 2. Tampilkan Seluruh Riwayat Transaksi (Default GET)
        $sql = "SELECT id, no_faktur, subtotal, pajak, total_bayar, created_at FROM transaksi ORDER BY id DESC";
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            sendResponse(false, "Gagal mengambil data transaksi", null, 500);
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        sendResponse(true, "Data riwayat transaksi berhasil diambil", $data, 200);
        break;

    case "POST":
        $payload = transaksiReadJson();

        if (!isset($payload["items"]) || !is_array($payload["items"]) || count($payload["items"]) === 0) {
            sendResponse(false, "Keranjang belanja (items) wajib diisi dan tidak boleh kosong", null, 400);
        }

        $rawItems = $payload["items"];
        $processedItems = [];
        $subtotal = 0;

        // Validasi & Hitung Harga Server-side (Aman dari manipulasi harga frontend)
        foreach ($rawItems as $item) {
            if (!isset($item["produk_id"]) || !isset($item["jumlah"])) {
                sendResponse(false, "Setiap item wajib memiliki produk_id dan jumlah", null, 400);
            }

            $produkId = filter_var($item["produk_id"], FILTER_VALIDATE_INT);
            $jumlah = filter_var($item["jumlah"], FILTER_VALIDATE_INT);

            if ($produkId === false || $produkId < 1) {
                sendResponse(false, "produk_id tidak valid", null, 400);
            }
            if ($jumlah === false || $jumlah < 1) {
                sendResponse(false, "jumlah item harus minimal 1", null, 400);
            }

            // Ambil harga resmi produk dari Database
            $stmtProd = transaksiPrepare($koneksi, "SELECT nama_produk, harga FROM produk WHERE id = ?");
            if (!mysqli_stmt_bind_param($stmtProd, "i", $produkId)) {
                sendResponse(false, "Gagal menyiapkan parameter query", null, 500);
            }
            transaksiExecute($stmtProd);
            $resProd = transaksiStatementResult($stmtProd);
            $prod = mysqli_fetch_assoc($resProd);
            mysqli_stmt_close($stmtProd);

            if (!$prod) {
                sendResponse(false, "Produk dengan ID " . $produkId . " tidak ditemukan", null, 400);
            }

            $hargaSatuan = (int) $prod["harga"];
            $itemSubtotal = $hargaSatuan * $jumlah;
            $subtotal += $itemSubtotal;

            $processedItems[] = [
                "produk_id" => $produkId,
                "jumlah" => $jumlah,
                "harga_satuan" => $hargaSatuan,
                "subtotal" => $itemSubtotal
            ];
        }

        $pajak = (int) round($subtotal * 0.10); // Pajak Kafe 10%
        $totalBayar = $subtotal + $pajak;
        $noFaktur = "TRX-" . date("YmdHis") . "-" . str_pad(mt_rand(1, 999), 3, "0", STR_PAD_LEFT);

        // Mulai Database Transaction (ACID Compliance)
        mysqli_begin_transaction($koneksi);

        try {
            // Step 1: Simpan Header ke tabel transaksi
            $stmtTx = mysqli_prepare(
                $koneksi,
                "INSERT INTO transaksi (no_faktur, subtotal, pajak, total_bayar) VALUES (?, ?, ?, ?)"
            );
            if (!$stmtTx) {
                throw new Exception("Gagal menyiapkan query header transaksi");
            }

            if (!mysqli_stmt_bind_param($stmtTx, "siii", $noFaktur, $subtotal, $pajak, $totalBayar)) {
                throw new Exception("Gagal mengikat parameter header transaksi");
            }

            if (!mysqli_stmt_execute($stmtTx)) {
                throw new Exception("Gagal menyimpan header transaksi");
            }

            $transaksiId = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmtTx);

            // Step 2: Simpan Rincian Item ke tabel detail_transaksi
            $stmtDetail = mysqli_prepare(
                $koneksi,
                "INSERT INTO detail_transaksi (transaksi_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)"
            );
            if (!$stmtDetail) {
                throw new Exception("Gagal menyiapkan query detail transaksi");
            }

            foreach ($processedItems as $item) {
                if (!mysqli_stmt_bind_param(
                    $stmtDetail,
                    "iiiii",
                    $transaksiId,
                    $item["produk_id"],
                    $item["jumlah"],
                    $item["harga_satuan"],
                    $item["subtotal"]
                )) {
                    throw new Exception("Gagal mengikat parameter detail transaksi");
                }

                if (!mysqli_stmt_execute($stmtDetail)) {
                    throw new Exception("Gagal menyimpan rincian item transaksi");
                }
            }
            mysqli_stmt_close($stmtDetail);

            // Commit transaksi jika semua operasi insert sukses
            mysqli_commit($koneksi);

            sendResponse(
                true,
                "Transaksi checkout berhasil disimpan",
                [
                    "id" => $transaksiId,
                    "no_faktur" => $noFaktur,
                    "subtotal" => $subtotal,
                    "pajak" => $pajak,
                    "total_bayar" => $totalBayar,
                    "jumlah_item" => count($processedItems)
                ],
                201
            );
        } catch (Exception $e) {
            // Rollback jika terjadi kegagalan di salah satu proses
            mysqli_rollback($koneksi);
            sendResponse(false, "Gagal memproses checkout: " . $e->getMessage(), null, 500);
        }
        break;

    default:
        sendResponse(false, "Method Not Allowed", null, 405);
        break;
}