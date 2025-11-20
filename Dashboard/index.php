<?php
// Koneksi ke database
$host = 'localhost';  // Ganti dengan host Anda
$dbname = 'psytech';  // Ganti dengan nama database Anda
$username = 'root';  // Ganti dengan username database Anda
$password = '';  // Ganti dengan password database Anda

session_start();

if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
	echo '<script>
    Swal.fire({
      title: "Login Berhasil",
      text: "Selamat datang di Dashboard!",
      icon: "success",
      confirmButtonText: "OK"
    }).then(function() {
      // Redirect setelah tombol OK ditekan
      window.location.href = "../Dashboard/index.php"; // Ganti URL sesuai dengan dashboard
    });
  </script>';
	// Hapus session login success setelah ditampilkan agar tidak tampil lagi setelah refresh
	unset($_SESSION['login_success']);
}

// Menangani error alert login jika ada
if (isset($_SESSION['login_error'])) {
	if ($_SESSION['login_error'] == 'wrong_password') {
		echo '<script>
      Swal.fire({
        title: "Password Salah",
        text: "Password yang Anda masukkan salah. Coba lagi.",
        icon: "error",
        confirmButtonText: "OK"
      });
    </script>';
	} elseif ($_SESSION['login_error'] == 'user_not_found') {
		echo '<script>
      Swal.fire({
        title: "User Tidak Ditemukan",
        text: "Username tidak ditemukan. Pastikan Anda memasukkan username yang benar.",
        icon: "error",
        confirmButtonText: "OK"
      });
    </script>';
	} elseif ($_SESSION['login_error'] == 'empty_fields') {
		echo '<script>
      Swal.fire({
        title: "Field Tidak Boleh Kosong",
        text: "Username dan Password tidak boleh kosong. Isi kedua field tersebut.",
        icon: "error",
        confirmButtonText: "OK"
      });
    </script>';
	}
	// Hapus session error setelah ditampilkan agar tidak tampil lagi setelah refresh
	unset($_SESSION['login_error']);
}

// Mengambil data diagnosa dan nama pasien
try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt = $pdo->prepare("
        SELECT diagnosa.*, pasien.nama 
        FROM diagnosa 
        JOIN pasien ON diagnosa.id_pasien = pasien.id_pasien
    ");
	$stmt->execute();
	$diagnosaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	echo "Error: " . $e->getMessage();
}

try {
	// Membuat koneksi ke database
	$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Query untuk mengambil data pasien dan hasil diagnosa
	$stmt = $pdo->prepare("SELECT pasien.nama, pasien.prodi, pasien.email, pasien.nomor_wa, diagnosa.hasil_diagnosa 
                           FROM pasien 
                           LEFT JOIN diagnosa ON pasien.id_pasien = diagnosa.id_pasien");
	$stmt->execute();
	$pasienData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	echo "Koneksi gagal: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard</title>
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<!-- SweetAlert2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.5/dist/sweetalert2.min.css" rel="stylesheet">
	<!-- SweetAlert2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.5/dist/sweetalert2.all.min.js"></script>

	<link rel="stylesheet" href="style.css">
	<style>
		.table-container {
			width: 100%;
			margin: 30px auto;
		}

		.table-header {
			display: flex;
			justify-content: center;
			align-items: center;
			gap: 20px;
			margin-bottom: 16px;
			position: relative;
		}

		.table-title {
			font-size: 24px;
			font-weight: 600;
			text-align: center;
			font-family: 'Segoe UI', sans-serif;
			color: #333;
			margin: 0 auto;
		}

		.excel-button {
			position: absolute;
			right: 0;
			background-color: #2196f3;
			color: white;
			border: none;
			padding: 8px 16px;
			border-radius: 6px;
			cursor: pointer;
			font-size: 14px;
			font-family: 'Segoe UI', sans-serif;
			transition: background-color 0.3s ease, transform 0.2s ease;
		}

		.excel-button:hover {
			background-color: #1976d2;
			transform: scale(1.05);
		}

		.table-container {
			margin-top: 30px;
			width: 100%;
		}

		.table-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 10px;
		}

		.table-header h2 {
			font-size: 22px;
			color: #333;
			font-weight: 600;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			margin: 0;
		}

		.excel-button {
			background-color: #1976d2;
			color: white;
			border: none;
			padding: 8px 14px;
			border-radius: 6px;
			cursor: pointer;
			font-size: 14px;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			transition: background-color 0.3s ease;
		}

		.excel-button i {
			margin-right: 6px;
			vertical-align: middle;
			font-size: 18px;
		}

		.excel-button:hover {
			background-color: #1565c0;
		}

		.table-pasien {
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0;
			margin-top: 20px;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
		}

		.table-pasien th,
		.table-pasien td {
			padding: 14px 16px;
			text-align: left;
			border-bottom: 1px solid #e0e0e0;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			font-size: 16px;
			color: #333;
			word-wrap: break-word;
			white-space: normal;
		}

		.table-pasien th {
			background-color: #1976d2;
			/* Biru */
			color: white;
			font-weight: 600;
			font-size: 16px;
		}

		.table-pasien tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		.table-pasien tr:hover {
			background-color: #f1f1f1;
		}

		.table-pasien td {
			font-size: 14px;
			color: #333;
			word-wrap: break-word;
			max-width: 180px;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			word-wrap: break-word;
			white-space: normal;
			border-bottom: 1px solid #e0e0e0;
		}

		.table-pasien td:last-child,
		.table-pasien td:nth-last-child(2) {
			white-space: pre-line;
		}


		.menu-content {
			display: none;
		}

		.menu-content.active {
			display: block;
		}

		.logout-link {
			color: red !important;
		}

		.logout-link i {
			color: red !important;
		}

		.logout-link .text {
			color: red !important;
		}

		/* Add dark mode styles */
		.dark {
			background-color: #121212;
			color: white;
		}

		/* Toggle dark mode in sidebar */
		.dark #sidebar {
			background-color: #333;
		}

		.dark .side-menu li a {
			color: white;
		}

		.dark nav {
			background-color: #333;
		}
	</style>
</head>

<body>

	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-dashboard'></i>
			<span class="text">PsyTech Admin</span>
		</a>
		<ul class="side-menu top">
			<li class="active">
				<a href="#dashboard" class="menu-link">
					<i class='bx bxs-dashboard'></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href="#Pasien" class="menu-link">
					<i class='bx bxs-shopping-bag-alt'></i>
					<span class="text">Pasien</span>
				</a>
			</li>
			<li>
				<a href="#Diagnosa" class="menu-link">
					<i class='bx bxs-doughnut-chart'></i>
					<span class="text">Diagnosa</span>
				</a>
			</li>
			<li>
				<a href="#message" class="menu-link">
					<i class='bx bxs-message-dots'></i>
					<span class="text">Message</span>
				</a>
			</li>
			<li>
				<a href="#team" class="menu-link">
					<i class='bx bxs-group'></i>
					<span class="text">Team</span>
				</a>
			</li>
		</ul>
		<ul class="side-menu">
			<li>
				<a href="../LoginRegis/index.php" class="menu-link logout-link">
					<i class='bx bxs-log-out'></i>
					<span class="text">Logout</span>
				</a>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->

	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu'></i>
			<a href="#" class="nav-link">Menu</a>
			<!-- <a href="#" class="profile">
				<img src="img/profile.png" alt="profile">
			</a> -->
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		<main>
			<div id="dashboard" class="menu-content active">
				<h2>Ini adalah menu Dashboard</h2>
			</div>
			<div id="Pasien" class="menu-content">
				<div class="table-header">
					<h2 class="table-title">Data Pasien</h2>
					<button onclick="exportToExcel('pasienTable')" class="excel-button">
						<i class='bx bx-download'></i> Unduh Excel
					</button>
				</div>

				<table class="table-pasien" id="pasienTable">
					<thead>
						<tr>
							<th>No.</th>
							<th>Nama</th>
							<th>Prodi</th>
							<th>Email</th>
							<th>Nomor WA</th>
							<th>Hasil Diagnosa</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1; // Inisialisasi nomor urut
						foreach ($pasienData as $pasien): ?>
							<tr>
								<td><?php echo $no++; ?></td> <!-- Menampilkan nomor urut -->
								<td><?php echo htmlspecialchars($pasien['nama']); ?></td>
								<td><?php echo htmlspecialchars($pasien['prodi']); ?></td>
								<td><?php echo htmlspecialchars($pasien['email']); ?></td>
								<td><?php echo htmlspecialchars($pasien['nomor_wa']); ?></td>
								<td><?php echo htmlspecialchars($pasien['hasil_diagnosa']); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>


			<div id="Diagnosa" class="menu-content">
				<div class="table-header">
					<h2 class="table-title">Data Diagnosa</h2>
					<button onclick="exportToExcel('diagnosaTable')" class="excel-button">
						<i class='bx bx-download'></i> Unduh Excel
					</button>
				</div>

				<table class="table-pasien" id="diagnosaTable">
					<thead>
						<tr>
							<th>No.</th>
							<th>Nama Pasien</th>
							<?php for ($i = 1; $i <= 20; $i++): ?>
								<th>Q<?= $i ?></th>
							<?php endfor; ?>
							<th>Keluhan</th>
							<th>Hasil Diagnosa</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						foreach ($diagnosaData as $row): ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= htmlspecialchars($row['nama']) ?></td>
								<?php for ($i = 1; $i <= 20; $i++):
									$val = $row["q$i"];
									if ($val == 0) {
										$text = "Tidak Pernah";
									} elseif ($val == 0.5) {
										$text = "Jarang";
									} elseif ($val == 1) {
										$text = "Sering";
									} else {
										$text = "Tidak Diketahui";
									}
									?>
									<td><?= $text ?></td>
								<?php endfor; ?>
								<td><?= htmlspecialchars($row['keluhan']) ?></td>
								<td><?= htmlspecialchars($row['hasil_diagnosa']) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div id="message" class="menu-content">
				<h2>Ini adalah menu Message</h2>
			</div>
			<div id="team" class="menu-content">
				<h2>Ini adalah menu Team</h2>
			</div>
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->

	<!-- Skrip JavaScript -->
	<script>
		// Handle navigation menu interactions
		const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

		allSideMenu.forEach(item => {
			const li = item.parentElement;

			item.addEventListener('click', function (e) {
				e.preventDefault();

				// Perpindahan konten menu
				const targetId = this.getAttribute('href').substring(1);
				document.querySelectorAll('.menu-content').forEach(content => {
					content.classList.remove('active');
				});
				document.getElementById(targetId).classList.add('active');

				// Aktifkan menu yang sedang diklik
				allSideMenu.forEach(i => {
					i.parentElement.classList.remove('active');
				});
				li.classList.add('active');
			});
		});

		// Konfirmasi sebelum logout
		const logoutLink = document.querySelector('.logout-link');
		if (logoutLink) {
			logoutLink.addEventListener('click', function (e) {
				e.preventDefault(); // hentikan aksi default
				const confirmed = confirm("Apakah Anda yakin ingin logout?");
				if (confirmed) {
					window.location.href = this.getAttribute('href'); // lanjut logout
				}
			});
		}

		// TOGGLE SIDEBAR
		const menuBar = document.querySelector('#content nav .bx.bx-menu');
		const sidebar = document.getElementById('sidebar');

		menuBar.addEventListener('click', function () {
			sidebar.classList.toggle('hide');
		});

		// Handle Search Button (Responsive)
		const searchButton = document.querySelector('#content nav form .form-input button');
		const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
		const searchForm = document.querySelector('#content nav form');

		searchButton.addEventListener('click', function (e) {
			if (window.innerWidth < 576) {
				e.preventDefault();
				searchForm.classList.toggle('show');
				if (searchForm.classList.contains('show')) {
					searchButtonIcon.classList.replace('bx-search', 'bx-x');
				} else {
					searchButtonIcon.classList.replace('bx-x', 'bx-search');
				}
			}
		});

		// Responsive behavior based on window size
		if (window.innerWidth < 768) {
			sidebar.classList.add('hide');
		} else if (window.innerWidth > 576) {
			searchButtonIcon.classList.replace('bx-x', 'bx-search');
			searchForm.classList.remove('show');
		}

		window.addEventListener('resize', function () {
			if (this.innerWidth > 576) {
				searchButtonIcon.classList.replace('bx-x', 'bx-search');
				searchForm.classList.remove('show');
			}
		});

		// Dark Mode Toggle
		const switchMode = document.getElementById('switch-mode');

		switchMode.addEventListener('change', function () {
			if (this.checked) {
				document.body.classList.add('dark');
			} else {
				document.body.classList.remove('dark');
			}
		});
		function exportToExcel(tableId) {
			const table = document.getElementById(tableId);
			if (!table) {
				alert('Tabel tidak ditemukan!');
				return;
			}

			// Menambahkan border CSS untuk setiap cell dengan memastikan seluruh tabel dibungkus dalam border
			let tableHTML = table.outerHTML;
			tableHTML = tableHTML.replace(/<td>/g, '<td style="border: 1px solid black; padding: 8px; text-align: left;">');
			tableHTML = tableHTML.replace(/<th>/g, '<th style="border: 1px solid black; padding: 8px; text-align: left; background-color: #f2f2f2;">');
			tableHTML = tableHTML.replace(/ /g, '%20'); // Ganti spasi dengan %20

			// Nama file unduhan
			const filename = tableId + '_data.xls';

			// Membuat link unduhan
			const downloadLink = document.createElement('a');
			downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
			downloadLink.download = filename;

			// Trigger download
			document.body.appendChild(downloadLink);
			downloadLink.click();
			document.body.removeChild(downloadLink);
		}

	</script>
</body>

</html>