<#
    E-Arsip PNM - Skrip Backup Otomatis
    Mencadangkan database dan dokumen uploads ke folder per area dan unit.

    Struktur hasil backup (D:\backup-earsip):
        database\              -> dump database bertanggal (.sql)
        _lengkap\              -> arsip ZIP seluruh folder uploads bertanggal
        _tanpa-unit\           -> file yang belum punya unit / tidak terdaftar
        MEKAAR BANGKA 1\       -> <Unit Subfolder>
        MEKAAR BANGKA 2\       -> <Unit Subfolder>
        MEKAAR BANGKA 3\       -> <Unit Subfolder>
        MEKAAR BELITUNG\       -> <Unit Subfolder>
        <Unit Lainnya>\        -> <Unit Subfolder>
#>

$ErrorActionPreference = 'Continue'

# ------------------------- KONFIGURASI -------------------------
$MysqlDump   = 'C:\xampp\mysql\bin\mysqldump.exe'
$Mysql       = 'C:\xampp\mysql\bin\mysql.exe'
$ProjectRoot = 'C:\xampp\htdocs\inventory\inventory'
$UploadsDir  = Join-Path $ProjectRoot 'uploads'

# Tujuan penyimpanan backup (drive eksternal / flashdisk).
$BackupRoot  = 'D:\backup-earsip'

$DbHost = '127.0.0.1'
$DbPort = '3306'
$DbName = 'db_earsip_pnm'
$DbUser = 'root'
$DbPass = ''          # kosongkan bila tidak ada password

$KeepDays = 30        # simpan dump database & ZIP selama 30 hari

# Pemetaan Unit ke Area
$AreaMap = @{
    # Area Bangka 1
    'M.ARGS- Air Gegas'   = 'MEKAAR BANGKA 1';
    'M.KBAA- Koba'        = 'MEKAAR BANGKA 1';
    'M.LBSR- Lubuk Besar' = 'MEKAAR BANGKA 1';
    'M.PYNG- Payung'      = 'MEKAAR BANGKA 1';
    'M.TBAL- Toboali'     = 'MEKAAR BANGKA 1';
    'M.TKSD- Tukak Sadai' = 'MEKAAR BANGKA 1';
    
    # Area Bangka 2
    'M.BLNY- Belinyu'      = 'MEKAAR BANGKA 2';
    'M.GMYA- Grimaya'      = 'MEKAAR BANGKA 2';
    'M.MRWG- Merawang'     = 'MEKAAR BANGKA 2';
    'M.SGLT- Sungai Liat'  = 'MEKAAR BANGKA 2';
    'M.SPKS- Simpang Katis' = 'MEKAAR BANGKA 2';
    'M.SPRB- Simpang Rimba' = 'MEKAAR BANGKA 2';
    
    # Area Bangka 3
    'M.GRGA- Gerunggang'  = 'MEKAAR BANGKA 3';
    'M.JBUS- Jebus'       = 'MEKAAR BANGKA 3';
    'M.KLPA- Kelapa'      = 'MEKAAR BANGKA 3';
    'M.MNTO- Mentok'      = 'MEKAAR BANGKA 3';
    'M.PDBS- Puding Besar' = 'MEKAAR BANGKA 3';
    
    # Area Belitung
    'M.BDAU- Badau'        = 'MEKAAR BELITUNG';
    'M.KPKT- Kelapa Kampit' = 'MEKAAR BELITUNG';
    'M.MGGR- Manggar'      = 'MEKAAR BELITUNG';
    'M.SJUK- Sijuk'       = 'MEKAAR BELITUNG';
    'M.TJPD- Tanjung Pandan' = 'MEKAAR BELITUNG'
}
# ---------------------------------------------------------------

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'

# Pastikan lokasi backup dapat diakses (bila drive eksternal belum terpasang, hentikan)
$driveRoot = [System.IO.Path]::GetPathRoot($BackupRoot)
if (-not (Test-Path $driveRoot)) {
    Write-Output "GAGAL: lokasi backup tidak ditemukan -> $BackupRoot (drive $driveRoot belum terpasang?)"
    exit 1
}

$DbDir    = Join-Path $BackupRoot 'database'
$ZipDir   = Join-Path $BackupRoot '_lengkap'
$OrphanDir = Join-Path $BackupRoot '_tanpa-unit'
$LogDir   = Join-Path $BackupRoot 'logs'
$LogFile  = Join-Path $LogDir 'backup.log'

foreach ($d in @($DbDir, $ZipDir, $OrphanDir, $LogDir)) {
    if (-not (Test-Path $d)) { New-Item -ItemType Directory -Path $d -Force | Out-Null }
}

function Write-Log($msg) {
    $line = '[{0}] {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg
    Add-Content -Path $LogFile -Value $line -Encoding UTF8
}

$mysqlArgs = @("--host=$DbHost", "--port=$DbPort", "--user=$DbUser", '--default-character-set=utf8mb4')
$dumpArgs  = @("--host=$DbHost", "--port=$DbPort", "--user=$DbUser")
if ($DbPass -ne '') {
    $mysqlArgs = @("--password=$DbPass") + $mysqlArgs
    $dumpArgs  = @("--password=$DbPass") + $dumpArgs
}

# Menjalankan query dan mengembalikan hasil (tanpa header, pemisah TAB)
function Invoke-Query([string]$sql) {
    return @(& $Mysql @mysqlArgs -N -B -e $sql 2>&1)
}

$ok = $true

# =============== 1. Backup database ===============
try {
    $sqlFile = Join-Path $DbDir ("{0}_{1}.sql" -f $DbName, $stamp)
    & $MysqlDump @dumpArgs `
        '--single-transaction' '--routines' '--events' '--default-character-set=utf8mb4' `
        "--result-file=$sqlFile" $DbName | Out-Null

    if (-not (Test-Path $sqlFile)) { throw 'file dump tidak terbentuk' }

    $mb = [math]::Round((Get-Item $sqlFile).Length / 1MB, 2)
    Write-Log "SUKSES database -> $sqlFile ($mb MB)"
} catch {
    $ok = $false
    Write-Log "GAGAL database -> $($_.Exception.Message)"
}

# =============== 2. Arsip ZIP seluruh uploads ===============
try {
    $zipFile = Join-Path $ZipDir ("uploads_{0}.zip" -f $stamp)
    $files   = Get-ChildItem -Path $UploadsDir -Force | Where-Object { $_.Name -ne '.htaccess' }

    if ($files.Count -gt 0) {
        Compress-Archive -Path $files.FullName -DestinationPath $zipFile -Force
        $mb = [math]::Round((Get-Item $zipFile).Length / 1MB, 2)
        Write-Log "SUKSES arsip ZIP -> $zipFile ($mb MB)"
    } else {
        Write-Log 'DILEWATI arsip ZIP -> belum ada file untuk dicadangkan'
    }
} catch {
    $ok = $false
    Write-Log "GAGAL arsip ZIP -> $($_.Exception.Message)"
}

# =============== 3. Salin dokumen ke folder masing-masing area & unit ===============
try {
    $built = 0; $copied = 0; $missing = 0

    # Daftar seluruh file yang tercatat di database
    $allStored = @{}
    foreach ($s in (Invoke-Query "SELECT stored_name FROM $DbName.files;")) {
        if ($s.Trim() -ne '') { $allStored[$s.Trim()] = $true }
    }

    $units = @(Invoke-Query "SELECT DISTINCT unit FROM $DbName.users WHERE unit IS NOT NULL AND unit <> '' ORDER BY unit;")

    foreach ($unit in $units) {
        $unit = $unit.Trim()
        if ($unit -eq '') { continue }

        # Tentukan folder Area
        $areaName = $AreaMap[$unit]
        if ($null -eq $areaName) {
            $basePath = $BackupRoot
        } else {
            $basePath = Join-Path $BackupRoot $areaName
        }

        # Pastikan folder Area ada
        if (-not (Test-Path $basePath)) { New-Item -ItemType Directory -Path $basePath -Force | Out-Null }

        # Folder Unit di dalam Area
        $unitDir = Join-Path $basePath $unit
        if (-not (Test-Path $unitDir)) { New-Item -ItemType Directory -Path $unitDir -Force | Out-Null }
        $built++

        $escaped = $unit -replace "'", "''"
        $rows = Invoke-Query ("SELECT f.stored_name, f.nama_file, c.nama, DATE_FORMAT(f.uploaded_at,'%Y-%m-%d %H:%i'), f.ukuran, IFNULL(f.keterangan,'') " +
                              "FROM $DbName.files f JOIN $DbName.categories c ON c.id = f.category_id " +
                              "WHERE f.unit = '$escaped' ORDER BY f.uploaded_at;")

        $csv = @('"Nama File","Kategori","Tanggal Upload","Ukuran (KB)","Keterangan","Stored Name"')

        foreach ($row in $rows) {
            if ([string]::IsNullOrWhiteSpace($row)) { continue }
            $col = $row -split "`t"
            if ($col.Count -lt 6) { continue }

            $storedName = $col[0].Trim()
            $namaFile   = $col[1].Trim()
            $kategori   = $col[2].Trim()
            $tanggal    = $col[3].Trim()
            $ukuran     = [int]$col[4]
            $keterangan = $col[5].Trim()

            $src = Join-Path $UploadsDir $storedName
            if (Test-Path $src) {
                $dest = Join-Path $unitDir $namaFile
                # Bila nama file sama, tambahkan penanda unik agar tidak saling menimpa
                if (Test-Path $dest) {
                    $dest = Join-Path $unitDir ('{0}_{1}' -f [IO.Path]::GetFileNameWithoutExtension($namaFile), $storedName)
                }
                Copy-Item -Path $src -Destination $dest -Force
                $copied++
            } else {
                $missing++
            }

            $csv += ('"{0}","{1}","{2}","{3}","{4}","{5}"' -f `
                ($namaFile -replace '"', '""'), ($kategori -replace '"', '""'), $tanggal,
                [math]::Round($ukuran / 1KB, 1), ($keterangan -replace '"', '""'), $storedName)
        }

        $csv | Out-File -FilePath (Join-Path $unitDir 'daftar-file.csv') -Encoding UTF8
    }

    Write-Log "SUKSES unit -> $built folder unit dibuat, $copied file disalin, $missing file hilang di folder uploads"

    # File yang tidak punya unit (unit kosong) atau tidak tercatat di database
    $orphan = @()
    foreach ($s in (Invoke-Query "SELECT stored_name FROM $DbName.files WHERE unit IS NULL OR unit = '';")) {
        if ($s.Trim() -ne '') { $orphan += $s.Trim() }
    }
    foreach ($f in (Get-ChildItem -Path $UploadsDir -File -Force | Where-Object { $_.Name -ne '.htaccess' })) {
        if (-not $allStored.ContainsKey($f.Name) -and $orphan -notcontains $f.Name) { $orphan += $f.Name }
    }
    foreach ($name in $orphan) {
        $src = Join-Path $UploadsDir $name
        if (Test-Path $src) { Copy-Item -Path $src -Destination (Join-Path $OrphanDir $name) -Force }
    }
    Write-Log "SUKSES tanpa-unit -> $($orphan.Count) file disalin ke $OrphanDir"
} catch {
    $ok = $false
    Write-Log "GAGAL unit -> $($_.Exception.Message)"
}

# =============== 4. Hapus backup yang sudah kedaluwarsa ===============
$batas = (Get-Date).AddDays(-$KeepDays)
foreach ($d in @($DbDir, $ZipDir)) {
    Get-ChildItem -Path $d -File | Where-Object { $_.LastWriteTime -lt $batas } | ForEach-Object {
        Remove-Item $_.FullName -Force
        Write-Log "HAPUS backup lama -> $($_.Name)"
    }
}

exit $(if ($ok) { 0 } else { 1 })
