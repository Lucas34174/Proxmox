<?php
session_start();
// echo $_SESSION['user_id'];
require_once "proxmox.php";
require_once "../connect.php";

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

function ipExists($ip, $pdo)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM virtualMachine WHERE ip_address = :ip");
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

function get_next_ip($pdo)
{
    $ip = "";
    for ($i = 151; $i < 255; $i++) {
        $ip = "192.168.11." . $i;
        if (!ipExists($ip, $pdo)) {
            // echo "ip is : " . $ip . "<br>";
            break;
        }
    }
    return $ip;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vmid = $_POST['vmid'];
    $os_name = $_POST['os_name'];
    $os_version = $_POST['os_version'];
    $vcpu = $_POST['vcpu'];
    $ram = $_POST['ram'];
    $disk = $_POST['disk'];
    $rootpass = $_POST['rootpass'];
    $confirm = $_POST['confirm'];
    $ecsname = $_POST['ecsname'];
    $pve = pve();
    $newid = $pve->get_next_vmid();
    $userId = $_SESSION['user_id'];
    $config = [
        "vmid" => $vmid,
        "newid" => $newid,
        "node" => PVE_NODE
    ];
    $result = $pve->post("/nodes/" . PVE_NODE . "/qemu/" . $vmid . "/clone", $config);
    if (!waitForTask($pve, PVE_NODE, $result)) {
        die("Le clonage a échoué");
    }
    // echo $result;
    if ($result) {
        // echo "✓ VM clonée avec succès (ID: $newid)<br>";

        // echo "\nConfiguration de la nouvelle VM...<br>";

        $config_params = [
            'node' => PVE_NODE,
            'vmid' => $newid,
            'name' => $ecsname,
            'memory' => $ram * 1024,
            'cores' => $vcpu,
            'sockets' => 1,
        ];
        // echo "vmid:" . $vmid . "<br>";
        $config_result = $pve->put("/nodes/" . PVE_NODE . "/qemu/" . $newid . "/config", $config_params);
        // if ($config_result) {
        //     echo "Configuration de base appliquée: " . $config_result . "<br>";
        // }
        // echo "<br>Redimensionnement du disque...<br>";
        // var_dump($disk);
        $resize_params = [
            'disk' => 'scsi0',
            'node' => PVE_NODE,
            'size' => $disk . "G",
            'vmid' => $newid,

        ];

        $resize_result = $pve->put("/nodes/" . PVE_NODE . "/qemu/" . $newid . "/resize", $resize_params);
        if ($resize_result) {
            // echo "✓ Disque redimensionné\n";
        }

        // === ÉTAPE 4: CONFIGURER CLOUD-INIT ===
        // echo "\nConfiguration Cloud-Init...<br>";
        $ip = get_next_ip($pdo);
        $cloudinit_params = [
            'ciuser' => 'root',                          // Utilisateur
            'cipassword' => $rootpass,  // Mot de passe
            'searchdomain' => 'google.com',              // Domaine de recherche
            'nameserver' => '8.8.8.8',                    // DNS
            'ipconfig0' => 'ip=' . $ip . '/24,gw=192.168.11.1', // IP statique
            // Pour DHCP, utiliser: 'ipconfig0' => 'ip=dhcp'
        ];

        $cloudinit_result = $pve->put("/nodes/" . PVE_NODE . "/qemu/" . $newid . "/config", $cloudinit_params);

        if ($cloudinit_result) {
            // echo "Cloud-Init configuré<br>";
        }
        $config = $pve->get("/nodes/" . PVE_NODE . "/qemu/" . $newid . "/agent/network-get-interfaces");
        print_r($config);
        try {
            $stmt = $pdo->prepare('INSERT INTO virtualMachine (vmid, user_id, hostname,cpu, ram, disk, ip_address,template_id, root_passwd) VALUES (:vmid, :user_id, :hostname,:cpu, :ram, :disk,:ip_address ,:template_id, :root_password)');
            $stmt->execute([
                ':vmid' => $newid,
                ':user_id' => $userId,
                ':hostname' => $ecsname,
                ':cpu' => $vcpu,
                ':ram' => $ram,
                ':disk' => $disk,
                ':ip_address' => $ip,
                ':template_id' => $vmid,
                ':root_password' => $rootpass
            ]);
            // echo "mety" . $newid;
        } catch (PDOException $e) {
            die("Erreur lors de l'ajout : " . $e->getMessage());
        }
    } else {
        die("tsy mety" . $newid);
    }
    if ($cloudinit_result && $resize_result && $config_result && $result) {
        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>cloneVM</title>
            <style>
                body {
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    background: radial-gradient(circle at top, #0f2027, #020617);
                    color: #e5e7eb;
                }

                h1 {
                    color: #22c55e;
                    text-align: center;
                    margin-bottom: 24px;
                }

                a {
                    flex: 1;
                    padding: 12px;
                    border: none;
                    border-radius: 10px;
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    color: #020617;
                    font-weight: bold;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    transition: 0.25s;
                }

                .success {
                    /* display: flex; */
                    align-items: center;
                    background: #020617;
                    border: 1px solid #1e293b;
                    border-radius: 8px;
                    padding: 0 12px;
                    margin-bottom: 16px;
                    transition: all 0.25s ease;
                }
            </style>
        </head>

        <body>
            <div class="success">
                <h3>OS successfully allocated</h3>
                <a href="../home.php">Back</a>
            </div>
        </body>

        </html>
        <?php
    }
} else {
    die("Accès direct non autorisé");
}
?>