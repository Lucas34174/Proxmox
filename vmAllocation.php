<?php
require_once "connect.php";
/* Récupérer OS + version */
$stmt = $pdo->prepare("
    SELECT DISTINCT os_name, os_version, vmid
    FROM templates 
    WHERE os_name IS NOT NULL
    ORDER BY os_name, os_version
");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Regrouper par OS */
$oses = [];
foreach ($data as $row) {
    $oses[$row['os_name']][] = $row['os_version'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Allocate Resource</title>
    <link rel="stylesheet" href="css/allocate.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div class="container">
        <h1>Allocate New Resource</h1>

        <form action="./api/clone_vm.php" method="POST" id="vmForm">

            <!-- VMID -->
            <div class="input-group">
                <input type="hidden" id="vmid" name="vmid" placeholder="VM ID">
            </div>
            <!-- OS + VERSION -->
            <div class="input-group">
                <i class="fa-solid fa-server"></i>

                <select id="os" name="os_name" required>
                    <option value="">Select OS</option>
                    <?php foreach ($oses as $os => $v): ?>
                        <option value="<?= htmlspecialchars($os) ?>">
                            <?= htmlspecialchars($os) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="version" name="os_version" required>
                    <option value="">Select Version</option>
                </select>
            </div>

            <!-- CPU -->
            <div class="input-group">
                <i class="fa-solid fa-microchip"></i>
                <select id="vcpu" name="vcpu" required>
                    <option value="">VCPU</option>
                    <option value="2">2</option>
                    <option value="4">4</option>
                    <option value="8">8</option>
                </select>
                <i class="fa-solid fa-memory"></i>
                <select id="ram" name="ram" required>
                    <option value="">Memory</option>
                    <option value="2">2</option>
                    <option value="4">4</option>
                    <option value="8">8</option>
                    <option value="16">16</option>
                    <option value="32">32</option>
                </select>
            </div>
            <!-- DISK -->
            <h4><i class="fa-solid fa-hdd"></i> Disk</h4>
            <div class="input-group">
                <input type="number" name="disk" min="20" max="60" required placeholder="System">
            </div>
            <h4>Login</h4><br>
            root password:
            <div class="input-group">
                <input type="password" name="rootpass">
            </div>
            confirm:
            <div class="input-group">
                <input type="password" name="confirm">
            </div>
            <h4>ECS Name</h4>
            <div class="input-group">
                <input type="text" name="ecsname" id="ecsname" pattern="^[A-Za-z0-9]+(-[A-Za-z0-9]+)*$"
                    title="Le nom ne peut contenir que des lettres, chiffres et tirets. Le tiret ne peut pas être au début ou à la fin."
                    required>
            </div>
            <div class="buttons">
                <button type="submit" class="submit">
                    <i class="fa-solid fa-check"></i> Next
                </button>
                <a href="home.php" class="cancel">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- JS -->
    <script>
        const osVersions = <?= json_encode($oses) ?>;

        const osSelect = document.getElementById("os");
        const versionSelect = document.getElementById("version");
        const vmidInput = document.getElementById("vmid");

        /* Charger les versions */
        osSelect.addEventListener("change", () => {
            versionSelect.innerHTML = '<option value="">Select Version</option>';
            vmidInput.value = "";

            if (!osSelect.value) return;

            osVersions[osSelect.value].forEach(v => {
                versionSelect.innerHTML += `<option value="${v}">${v}</option>`;
            });
        });

        /* Charger VMID automatiquement */
        versionSelect.addEventListener("change", () => {
            vmidInput.value = "";

            if (!osSelect.value || !versionSelect.value) return;

            fetch(`get_vmid.php?os=${encodeURIComponent(osSelect.value)}&version=${encodeURIComponent(versionSelect.value)}`)
                .then(res => res.text())
                .then(vmid => {
                    vmidInput.value = vmid ? vmid : "N/A";
                });
        });

        document.getElementById("vmForm").addEventListener("submit", function (event) {
            const input = document.getElementById("ecsname");
            // Si le champ n'est pas valide selon le pattern HTML
            if (!input.checkValidity()) {
                event.preventDefault(); //empêche le submit
                alert("Nom invalide !");
                return;
            }
        });
    </script>

</body>

</html>