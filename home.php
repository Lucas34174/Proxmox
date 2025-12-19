<?php
session_start();
require_once("connect.php");
require_once("api/proxmox.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Charger les VMs allouées à cet utilisateur
$stmt = $pdo->prepare("
    SELECT vm.*, t.os_name, t.os_version 
    FROM virtualMachine vm
    LEFT JOIN templates t ON vm.template_id = t.vmid
    WHERE vm.user_id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$vmList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>OS Allocation Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- xterm.js pour le terminal -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css">

    <link rel="stylesheet" href="./css/style1.css">
    <link rel="stylesheet" href="./css/terminal.css">
</head>

<body>

    <div class="dashboard">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <h2 class="logo">OS-ALLOC</h2>

            <nav>
                <a href="#"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <!-- <a href="#"><i class="fa-solid fa-gear"></i> Settings</a> -->
                <!-- <a href="#"><i class="fa-solid fa-microchip"></i> Resources</a> -->
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="content">

            <div class="content-header">
                <h1>Allocated Virtual Machines</h1>
                <a href="vmAllocation.php" class="add-resource"><i class="fa-solid fa-plus"></i> Allocate Resource</a>
            </div>

            <div class="os-grid">

                <?php if (!empty($vmList)): ?>
                    <?php foreach ($vmList as $vm): ?>
                        <?php
                        $pve = pve();
                        ///nodes/{node}/qemu/{vmid}/status/current
                        $current = $pve->get("/nodes/" . PVE_NODE . "/qemu/" . $vm['vmid'] . "/status/current");
                        ?>
                        <div class="os-card">
                            <h3><i class="fa-solid fa-server"></i> <?= htmlspecialchars($vm['hostname']) ?></h3>
                            <p><strong>OS:</strong> <?= htmlspecialchars($vm['os_name'] ?? 'N/A') ?>
                                <?= htmlspecialchars($vm['os_version'] ?? '') ?>
                            </p>
                            <p><strong>IP:</strong> <?= htmlspecialchars($vm['ip_address']) ?></p>
                            <p><strong>CPU:</strong> <?= htmlspecialchars($vm['cpu']) ?> Cores</p>
                            <p><strong>RAM:</strong> <?= htmlspecialchars($vm['ram']) ?> GB</p>
                            <p><strong>Disk:</strong> <?= htmlspecialchars($vm['disk']) ?> GB</p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($current['status']) ?></p>
                            <div class="actions">

                                <button class="start" onclick="startVM(<?= $vm['vmid'] ?>)" <?= $current['status'] === 'running' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                    <i class="fa-solid fa-play"></i> Start
                                </button>

                                <button class="terminal" onclick="openTerminal(<?= $vm['vmid'] ?>)"
                                    data-vmid="<?= $vm['vmid'] ?>" <?= $current['status'] !== 'running' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                    <i class="fa-solid fa-terminal"></i> CLI
                                </button>
                                <button class="stop" onclick="stopVM(<?= $vm['vmid'] ?>)" <?= $current['status'] !== 'running' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                    <i class="fa-solid fa-stop"></i> Stop
                                </button>
                                <button class="delete" onclick="deleteVM(<?= $vm['vmid'] ?>)" <?= $current['status'] === 'running' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-cloud"></i>
                        <p style="color:#94a3b8;">Aucune VM allouée pour le moment.</p>
                        <a href="vmAllocation.php" class="add-first-vm">Allouer votre première VM</a>
                    </div>
                <?php endif; ?>

            </div>
        </main>

    </div>

    <!-- Modal pour le Terminal -->
    <div id="terminalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-terminal"></i> Terminal - VM <span id="terminal-vmid"></span></h3>
                <button class="modal-close" onclick="closeTerminal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="terminal-status">
                    <div class="loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Connexion au terminal en cours...</p>
                        <p class="hint">Le service ttyd doit être installé sur la VM (port 7681)</p>
                    </div>
                </div>
                <div id="terminal-container" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button onclick="reconnectTerminal()" class="btn-reconnect">
                    <i class="fa-solid fa-rotate"></i> Reconnect
                </button>
                <button onclick="closeTerminal()" class="btn-close">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-web-links@0.9.0/lib/xterm-addon-web-links.min.js"></script>
    <script src="./js/terminal.js"></script>
    <script>
        // Fonctions pour démarrer/arrêter les VMs
        async function startVM(vmid) {
            try {
                const response = await fetch('./api/start_vm.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ "vmid": vmid })
                });
                const data = await response.json();
                if (data.success) {
                    alert('VM ' + vmid + ' en cours de démarrage');
                    setTimeout(() => {
                    }, 30000);
                    alert('VM ' + vmid + ' démarrée avec succès');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de démarrer la VM'));
                }
            } catch (error) {
                alert('Erreur réseau: ' + error.message);
            }
        }
        async function stopVM(vmid) {
            if (!confirm('Eteindre la VM ' + vmid + ' ?')) return;
            try {
                const response = await fetch('./api/stop_vm.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 'vmid': vmid })
                });
                const data = await response.json();
                if (data.success) {
                    alert('VM ' + vmid + ' arrêtée avec succès');
                    location.reload();
                } else {
                    alert('Erreur lors de l\'arrêt');
                }
            } catch (error) {
                alert('Erreur réseau: ' + error.message);
            }
        }

        function deleteVM(vmid) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette VM ?')) {
                console.log('VM à supprimer :', vmid);
                fetch('./api/delete_vm.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 'vmid': vmid })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('VM supprimée !');
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    });
            }
        }
    </script>
</body>

</html>