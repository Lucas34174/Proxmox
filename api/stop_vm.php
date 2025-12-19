<?php
require_once "proxmox.php";
require_once "../connect.php";
header("Content-Type: application/json");
$vmid = json_decode(file_get_contents("php://input"), true)['vmid'] ?? null;
// $vmid = $body["vmid"];

$stmt = $pdo->prepare("SELECT status FROM virtualMachine WHERE vmid = :vmid");
$stmt->execute(['vmid' => $vmid]);
$currentStatus = $stmt->fetchColumn();
if ($currentStatus === 'stop') {
    echo json_encode(["success" => false, "message" => "La VM est déjà en cours d'exécution"]);
    exit;
}

$pve = pve();
$pve->post("/nodes/" . PVE_NODE . "/qemu/" . $vmid . "/status/stop", []);

$stmt = $pdo->prepare("UPDATE virtualMachine SET status = 'stop' WHERE vmid = :vmid");
$stmt->execute(['vmid' => $vmid]);

echo json_encode(["success" => true]);
?>