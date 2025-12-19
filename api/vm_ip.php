<?php
require_once "proxmox.php";
header("Content-Type: application/json");
$vmid = $_GET['vmid'];
$pve = pve();
$res = $pve->get("/nodes/" . PVE_NODE . "/qemu/$vmid/agent/network-get-interfaces");
echo json_encode($res);
?>