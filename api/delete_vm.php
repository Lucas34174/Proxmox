<?php
session_start();
require_once "proxmox.php";
require_once "../connect.php";
$userId = $_SESSION["user_id"];
function waitForTask(PVE2_API $pve, string $node, string $upid): bool
{
    while (true) {
        $status = $pve->get("/nodes/$node/tasks/" . urlencode($upid) . "/status");
        if ($status === false) {
            return false;
        }
        if ($status['status'] === 'stopped') {
            return ($status['exitstatus'] === 'OK');
        }
        sleep(5);
    }
}

header("Content-Type: application/json");
$vmid = json_decode(file_get_contents("php://input"), true)['vmid'] ?? null;
$pve = pve();
$result = $pve->delete("/nodes/" . PVE_NODE . "/qemu/" . $vmid);
// $status = waitForTask($pve, PVE_NODE, $result);
//Supprimer dans la base ensuite
if ($result) {
    $stmt = $pdo->prepare("
    DELETE
    FROM virtualMachine vm
    WHERE vm.user_id = :user_id AND vm.vmid = :vmid
");
    $stmt->execute([':user_id' => $userId, ':vmid' => $vmid]);
    echo json_encode(["success" => true]);
}
?>