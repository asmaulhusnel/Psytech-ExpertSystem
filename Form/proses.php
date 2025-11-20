<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Proses</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- Bootstrap (opsional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "psytech";
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    $nama = $_POST['nama'];
    $prodi = $_POST['prodi'];
    $email = $_POST['email'];
    $nomor = $_POST['nomor'];
    $keluhan = $_POST['keluhan'];

    $q = [];
    $total = 0;
    for ($i = 1; $i <= 20; $i++) {
        $q[$i] = isset($_POST["q$i"]) ? floatval($_POST["q$i"]) : 0;
        $total += $q[$i];
    }

    // Simpan ke pasien
    $sql_pasien = "INSERT INTO pasien (nama, prodi, email, nomor_wa) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_pasien);
    $stmt->bind_param("ssss", $nama, $prodi, $email, $nomor);
    $stmt->execute();
    $id_pasien = $conn->insert_id;

    // Tentukan hasil fuzzy
    if ($total <= 5.9) {
        $hasil = "Rendah";
    } elseif ($total <= 10.9) {
        $hasil = "Sedang";
    } else {
        $hasil = "Tinggi";
    }

    // Simpan ke diagnosa
    $sql_diag = "INSERT INTO diagnosa (
  id_pasien, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10,
  q11, q12, q13, q14, q15, q16, q17, q18, q19, q20, keluhan, hasil_diagnosa
) VALUES (
  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";
    $stmt2 = $conn->prepare($sql_diag);
    $stmt2->bind_param(
        "idddddddddddddddddddsss",
        $id_pasien,
        $q[1],
        $q[2],
        $q[3],
        $q[4],
        $q[5],
        $q[6],
        $q[7],
        $q[8],
        $q[9],
        $q[10],
        $q[11],
        $q[12],
        $q[13],
        $q[14],
        $q[15],
        $q[16],
        $q[17],
        $q[18],
        $q[19],
        $q[20],
        $keluhan,
        $hasil
    );
    $stmt2->execute();

    // Pertanyaan
    $pertanyaan = [
        "Apakah anda sering merasa sakit kepala?",
        "Apakah anda kehilangan nafsu makan?",
        "Apakah tidur anda nyenyak?",
        "Apakah anda mudah merasa takut?",
        "Apakah anda merasa cemas, tegang, atau khawatir?",
        "Apakah tangan anda gemetar?",
        "Apakah anda mengalami gangguan pencernaan?",
        "Apakah anda merasa sulit berpikir jernih?",
        "Apakah anda merasa tidak Bahagia?",
        "Apakah anda lebih sering menangis?",
        "Apakah anda merasa sulit untuk menikmati aktivitas sehari-hari?",
        "Apakah anda mengalami kesulitan untuk mengambil keputusan?",
        "Apakah aktivitas/tugas sehari-hari anda terbengkalai?",
        "Apakah anda merasa tidak mampu berperan dalam kehidupan ini?",
        "Apakah anda kehilangan minat terhadap banyak hal?",
        "Apakah anda merasa tidak berharga?",
        "Apakah anda mempunyai pemikiran mengakhiri hidup anda?",
        "Apakah anda merasa lelah sepanjang waktu?",
        "Apakah anda merasa tidak enak di perut?",
        "Apakah anda mudah lelah?"
    ];

    // Konversi ke teks
    function nilai_teks($n)
    {
        return $n == 1 ? "Sering" : ($n == 0.5 ? "Kadang-kadang" : "Tidak Pernah");
    }

    // Jika berhasil disimpan, teruskan ke JS
    if ($stmt2->affected_rows > 0) {
        $data = [
            "nama" => $nama,
            "prodi" => $prodi,
            "email" => $email,
            "nomor" => $nomor,
            "hasil" => $hasil,
            "pertanyaan" => []
        ];
        foreach ($pertanyaan as $i => $t) {
            $data["pertanyaan"][] = [
                "no" => $i + 1,
                "teks" => $t,
                "jawaban" => nilai_teks($q[$i + 1])
            ];
        }

        echo "<script>const dataUser = " . json_encode($data) . ";</script>";
    } else {
        echo "
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Gagal Menyimpan Data!',
      text: 'Terjadi kesalahan. Silakan coba lagi atau periksa koneksi Anda.',
      confirmButtonText: 'Kembali'
    }).then(() => {
      window.history.back();
    });
  </script>
  ";
    }

    $conn->close();
    ?>

    <!-- Jika sukses, tampilkan SweetAlert dan buat PDF -->
    <script>
        if (typeof dataUser !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Mengirim Formulir',
                text: 'Data disimpan. Ingin unduh hasil diagnosa?',
                showDenyButton: true,
                confirmButtonText: 'Download PDF',
                denyButtonText: 'Lihat di Tab Baru'
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    generatePDF(result.isDenied); // true = tab baru, false = download
                    setTimeout(() => {
                        window.location.href = "form.html";
                    }, 1000);
                }
            });
        }

        function generatePDF(openInTab) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const primaryColor = '#2c3e50';
            const accentColor = '#3498db';

            let y = 20;
            const lineGap = 6;

            // === HEADER UTAMA ===
            doc.setFillColor(primaryColor);
            doc.rect(0, 0, 210, 30, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text("LAPORAN DIAGNOSA PSIKOLOGI", 105, 18, { align: "center" });

            // === IDENTITAS PASIEN ===
            y = 40;
            doc.setTextColor(primaryColor);
            doc.setFontSize(13);
            doc.setFont('helvetica', 'bold');
            doc.text("IDENTITAS PASIEN", 105, y, { align: "center" });
            y += lineGap;
            doc.setDrawColor(accentColor);
            doc.line(25, y, 185, y); y += lineGap;

            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const col1 = 30, col2 = 115;

            doc.text("Nama:", col1, y);
            doc.text(dataUser.nama, col1 + 25, y);
            doc.text("Prodi:", col2, y);
            doc.text(dataUser.prodi, col2 + 25, y);
            y += lineGap;

            doc.text("Email:", col1, y);
            doc.text(dataUser.email, col1 + 25, y);
            doc.text("No. WA:", col2, y);
            doc.text(dataUser.nomor, col2 + 25, y);
            y += 2 * lineGap;

            // === HASIL DIAGNOSA ===
            doc.setFontSize(13);
            doc.setFont('helvetica', 'bold');
            doc.text("HASIL DIAGNOSA", 105, y, { align: "center" });
            y += lineGap;

            doc.line(25, y, 185, y);
            y += lineGap;

            const boxWidth = 100;
            const boxHeight = 25;
            const boxX = 55;
            const boxY = y;

            doc.setFillColor(accentColor);
            doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 4, 4, 'F');

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');

            // Hitung posisi vertikal teks agar pas di tengah kotak
            const textY = boxY + boxHeight / 2 + 4; // angka "4" ini bisa disesuaikan jika font size-nya berubah
            doc.text(dataUser.hasil.toUpperCase(), boxX + boxWidth / 2, textY, { align: "center" });

            y += boxHeight + 10;

            // === REKOMENDASI ===
            doc.setTextColor(primaryColor);
            doc.setFontSize(13);
            doc.setFont('helvetica', 'bold');
            // doc.text("REKOMENDASI", 105, y, { align: "center" });
            // y += lineGap;
            // // doc.line(25, y, 185, y); // garis langsung setelah judul
            // y += lineGap;

            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const pesan = getRecommendationMessage(dataUser.hasil);
            const wrapped = doc.splitTextToSize(pesan, 170);
            doc.text(wrapped, 20, y);
            y += wrapped.length * 6 + 12;

            // === REKAP KUESIONER ===
            if (y > 220) { doc.addPage(); y = 20; }
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(13);
            doc.setTextColor(primaryColor);
            doc.text("REKAP KUESIONER", 105, y, { align: "center" });
            y += lineGap;
            doc.line(25, y, 185, y); y += lineGap;

            // Tabel sudah bagus dari sebelumnya
            doc.setFillColor(accentColor);
            doc.rect(20, y, 170, 10, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(11);
            doc.text("PERTANYAAN", 25, y + 7);
            doc.text("JAWABAN", 185, y + 7, { align: "right" });
            y += 10;

            const lineHeight = 6.5;
            doc.setFontSize(10);
            doc.setTextColor(primaryColor);

            dataUser.pertanyaan.forEach((p, i) => {
                const teks = `${p.no}. ${p.teks}`;
                const lines = doc.splitTextToSize(teks, 125);
                const rowHeight = lines.length * lineHeight + 3;

                if (y + rowHeight > 280) {
                    doc.addPage();
                    y = 20;
                }

                // Baris alternatif
                doc.setFillColor(i % 2 === 0 ? 250 : 245);
                doc.rect(20, y, 170, rowHeight, 'F');

                doc.setFont('helvetica', 'normal');
                doc.text(lines, 25, y + lineHeight);

                const middleY = y + rowHeight / 2 + 2;
                doc.setFont('helvetica', 'bold');
                doc.text(p.jawaban.toString(), 185, middleY, { align: "right" });

                y += rowHeight;
            });

            // === FOOTER ===
            doc.setFontSize(9);
            doc.setTextColor(150);
            // doc.text("Dokumen resmi PsycTech - Valid tanpa tanda tangan", 105, 290, { align: "center" });

            if (openInTab) {
                window.open(doc.output('bloburl'), '_blank');
            } else {
                const namaFile = `Laporan hasil diagnosa ${dataUser.nama.replace(/\s+/g, "_")}.pdf`;
                doc.save(namaFile);
            }
        }

        // Fungsi untuk mendapatkan pesan rekomendasi
        function getRecommendationMessage(hasil) {
            const messages = {
                rendah: "Kemungkinan kecil adanya gangguan Kesehatan mental, Tetap semangat! Kondisimu saat ini tergolong baik. Pertahankan pola hidup sehat dan terus lakukan aktivitas positif.",
                sedang: "Ada indikasi gejala Psikologis yang perlu dievaluasi lebih lanjut. Disarankan untuk menghubungi konselor atau psikolog untuk konsultasi lebih lanjut. Layanan konseling tersedia di kampus kami.",
                tinggi: "Perlu segera dirujuk ke tenaga Kesehatan mental, seperti psikolog atau spikiater, untuk penilaian dan intervensi lebih lanjut. Tenang! Anda akan segera dihubungi oleh spikiater kami melalui email atau whatsapp untuk penanganan lebih lanjut."
            };
            return messages[hasil.toLowerCase()] || "Silakan hubungi administrator untuk informasi lebih lanjut.";
        }
    </script>

</body>

</html>