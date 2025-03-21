<?php
// No whitespace or output before this
$conn = new mysqli('localhost', 'root', '', 'house_rental_db');

// Check connection without outputting directly
if ($conn->connect_error) {
    // Log the error instead of echoing it
    error_log("Database connection failed: " . $conn->connect_error);
    // Return null or throw an exception to let the main script handle it
    $conn = null;
}
?>