<?php
require_once "proxmox.php";
require_once "../connect.php";
header("Content-Type: application/json");

$vmid = $_GET['vmid'] ?? die(json_encode(["error" => "VMID manquant"]));

// 1. Chercher l'IP dans la base de données (votre méthode)
$stmt = $pdo->prepare("SELECT ip_address FROM virtualMachine WHERE vmid = ?");
$stmt->execute([$vmid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && !empty($row['ip_address'])) {
    $ip = $row['ip_address'];

    echo json_encode([
        "success" => true,
        "ip" => $ip,
        "port" => 7681,
        "vmid" => $vmid
    ]);
    exit;
}
?>