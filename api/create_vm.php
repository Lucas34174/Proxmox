<?php
require_once "proxmox.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vmid = $_POST["user_id"] ?? '';
    $name = $_POST["name"] ?? "ecs-1";
    $memory = $_POST["memory"] ?? 2048;
    $cores = $_POST["cores"] ?? 4;
    $disk = $_POST["disk"] ?? 40;
    $iso = $_POST["iso"] ?? 'archlinux-2025.05.01-x86_64.iso';
    $ip = $_POST["ip"] ?? '1.1.1.1';
    $iso = 'archlinux-2025.05.01-x86_64.iso';

    $pve = pve();
    $vmid = 121;
    $name = "MIT";
    $memory = 2048;
    $cores = 4;
    $disk = "40";
    $iso = "archlinux-2025.05.01-x86_64.iso";
    $pve = pve();
    $config = [
        "vmid" => $vmid,
        "name" => $name,
        "memory" => $memory,
        "cores" => $cores,
        "sockets" => 1,
        "scsihw" => "virtio-scsi-pci",
        "scsi0" => "local-lvm:$disk",
        "net0" => "virtio,bridge=vmbr0",
        "ide2" => "local:iso/$iso,media=cdrom",
        "ostype" => "l26",
        "boot" => "order=ide2;scsi0"
    ];
    $result = $pve->post("/nodes/" . PVE_NODE . "/qemu", $config);

    // echo "<pre>";
    // var_dump($result);
    // echo "</pre>";
} else {
    echo "Accès direct non autorisé";
}

?>