<?php
/**
 * CRON JOB - AUTO REJECT PEMBATALAN AFTER 24 HOURS
 * File: cron_auto_reject.php
 * 
 * Cara setup cron job:
 * 0 * * * * /usr/bin/php /path/to/your/website/cron_auto_reject.php
 * (Jalankan setiap jam)
 */

// Logging function
function logCron($message) {
    $logFile = __DIR__ . '/cron_auto_reject.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Juga output ke console untuk debugging
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

try {
    logCron("=== STARTING CRON AUTO REJECT ===");
    
    // Include config database - PASTIKAN PATH BENAR
    $config_path = __DIR__ . '/config/database.php';
    if (!file_exists($config_path)) {
        throw new Exception("Database config not found: $config_path");
    }
    
    require_once $config_path;
    
    // Create database connection manually untuk avoid class issues
    // GANTI DENGAN DATABASE CREDENTIALS KAMU:
    $host = 'localhost'; // Ganti dengan host kamu
    $dbname = 'bookinglapanganb2'; // Ganti dengan nama database kamu
    $username = 'root'; // Ganti dengan username database kamu
    $password = ''; // Ganti dengan password database kamu
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    logCron("Database connected successfully");
    
    // Calculate 24 hours ago - FIX VARIABLE NAME
    $twentyfour_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    logCron("Looking for cancellation requests older than: $twentyfour_hours_ago");
    
    // Find pending cancellation requests older than 24 hours
    $sql = "
        SELECT 
            pb.id,
            pb.id_detail_booking,
            pb.id_user,
            pb.nama_pengaju,
            pb.requested_at
        FROM pembatalan_booking pb
        WHERE pb.status = 'pending' 
        AND pb.requested_at <= ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $twentyfour_hours_ago);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pending_requests = $result->fetch_all(MYSQLI_ASSOC);
    $total_processed = count($pending_requests);
    
    logCron("Found $total_processed pending cancellation requests to process");
    
    $success_count = 0;
    $error_count = 0;
    
    // Process each pending request
    foreach ($pending_requests as $request) {
        $conn->begin_transaction();
        
        try {
            $request_id = $request['id'];
            $id_detail_booking = $request['id_detail_booking'];
            $id_user = $request['id_user'];
            
            logCron("Processing request ID: $request_id for user: $id_user");
            
            // Update pembatalan_booking status to rejected
            $update_sql = "
                UPDATE pembatalan_booking 
                SET status = 'rejected', 
                    processed_at = NOW(),
                    keterangan = 'Ditolak otomatis: Tidak direspon dalam 24 jam'
                WHERE id = ?
            ";
            
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Prepare update failed: " . $conn->error);
            }
            
            $update_stmt->bind_param("i", $request_id);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows > 0) {
                logCron("Successfully auto-rejected cancellation request ID: $request_id");
                $success_count++;
            } else {
                throw new Exception("Failed to update pembatalan_booking for ID: $request_id");
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            logCron("ERROR processing request ID {$request['id']}: " . $e->getMessage());
            $error_count++;
        }
    }
    
    // Log summary
    logCron("=== CRON COMPLETED ===");
    logCron("Total processed: $total_processed");
    logCron("Success: $success_count");
    logCron("Errors: $error_count");
    logCron("=====================");
    
} catch (Exception $e) {
    logCron("CRITICAL ERROR: " . $e->getMessage());
}

// Close connection
if (isset($conn)) {
    $conn->close();
    logCron("Database connection closed");
}

// Untuk web access - berikan response clean
if (php_sapi_name() !== 'cli') {
    echo "Cron job executed successfully. Check cron_auto_reject.log for details.";
}
?>