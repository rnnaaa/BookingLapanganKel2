<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Booking Badmintoon | Dashboard</title>

  <!-- ================= CUSTOM STYLE ================= -->
  <style>
    /* === Sidebar utama === */
    .main-sidebar {
      background: linear-gradient(180deg, #0e5c91, #1874ad) !important;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
    }

    .nav-sidebar .nav-link,
    .brand-text {
      color: #fff !important;
    }

    .nav-sidebar .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.1) !important;
    }

    .nav-sidebar .nav-link.active {
      background-color: rgba(0, 0, 0, 0.25) !important;
      color: #fff !important;
      border-left: 4px solid #00c4ff;
    }

    /* === PRELOADER STYLING (gradasi biru indoor elegan) === */
    .preloader {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: linear-gradient(145deg, #002b55 0%, #0e5c91 40%, #1a9af0 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      transition: opacity 0.8s ease, visibility 0.8s ease;
    }

    .preloader.hidden {
      opacity: 0;
      visibility: hidden;
    }

    .preloader img {
      width: 110px;
      height: 110px;
      object-fit: contain;
      border-radius: 50%;
      background: radial-gradient(circle at center, #ffffff 0%, #cfe8ff 100%);
      padding: 8px;
      box-shadow: 0 0 25px rgba(255, 255, 255, 0.6);
      animation: bounce 1.6s infinite ease-in-out;
    }

    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-15px);
      }
    }

    .preloader h3 {
      margin-top: 15px;
      font-size: 20px;
      color: #e3f2fd;
      font-weight: 600;
      letter-spacing: 1px;
      text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
      font-family: 'Poppins', sans-serif;
      animation: fadeInText 1.5s ease-in-out infinite alternate;
    }

    @keyframes fadeInText {
      from { opacity: 0.7; }
      to { opacity: 1; }
    }

    /* Spinner warna gradasi biru elegan */
    .spinner-border {
      margin-top: 18px;
      width: 2.8rem;
      height: 2.8rem;
      border-width: 0.3rem;
      color: transparent;
      border-top-color: #e1f5fe;
      border-right-color: #81d4fa;
      border-bottom-color: #29b6f6;
      border-left-color: #01579b;
      animation: spin 1s linear infinite, glow 1.5s ease-in-out infinite alternate;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @keyframes glow {
      from { box-shadow: 0 0 8px rgba(255,255,255,0.3); }
      to { box-shadow: 0 0 20px rgba(33,150,243,0.8); }
    }

    /* === BODY TRANSITION === */
    body {
      background: #f4f7fb;
      animation: fadeIn 0.8s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.98);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
  </style>

  <!-- ================= GOOGLE FONT ================= -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- ================= PLUGINS & CSS ================= -->
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/jqvmap/jqvmap.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/plugins/summernote/summernote-bs4.min.css">
  <link rel="stylesheet" href="../public/asseth/tampilan_admin/dist/css/adminlte.min.css">


<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.0/dist/select2-bootstrap4.min.css">

  <!-- ================= ANIMATE CSS ================= -->
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>

<body class="hold-transition sidebar-mini layout-fixed">

  <!-- ================= PRELOADER ================= -->
  <div class="preloader">
    <!-- Ganti logo ini sesuai dengan foldermu -->
    <img src="../public/img/logo-badmintoon.png" alt="Logo Booking Badmintoon">
    <h3>Booking Badmintoon</h3>
    <div class="spinner-border" role="status">
      <span class="sr-only">Loading...</span>
    </div>
  </div>

  <!-- ================= WRAPPER ================= -->
  <div class="wrapper">
