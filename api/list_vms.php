<?php
require_once "proxmox.php";
header("Content-Type: application/json");
$pve = pve();
$vms = $pve->get("/nodes/" . PVE_NODE . "/qemu");
echo json_encode($vms);
?>