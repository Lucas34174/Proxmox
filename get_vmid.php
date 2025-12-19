<?php
require_once "connect.php";

if (!isset($_GET['os'], $_GET['version'])) {
    exit;
}

$stmt = $pdo->prepare("
    SELECT vmid
    FROM templates
    WHERE os_name = ? AND os_version = ?
    LIMIT 1
");
$stmt->execute([$_GET['os'], $_GET['version']]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo $row ? $row['vmid'] : "";
