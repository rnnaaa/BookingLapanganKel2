<?php
// php/logout.php
session_start();
session_unset();
session_destroy();
header('Location: ../../BookingPengguna/booking.php');
exit;
