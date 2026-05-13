<?php
$conn = new mysqli("sql207.infinityfree.com", "if0_39864294", "ddatra2025", "if0_39864294_dda_tra");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!";
?>
