<?php
session_start();
/*require_once("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer les champs du formulaire
    $os = trim($_POST['os']);
    $cpu  = $_POST['cpu'];
    $ram = $_POST['ram'];
    $disk = $_POST['disk'];
    $userId = $_SESSION['user_id'];
    $name = $_POST['vm_name'];

    // Insertion dans la table resources
    try {
        $stmt = $pdo->prepare('INSERT INTO resources (os_name, cpu, ram, disk, user_id) VALUES (:os_name, :cpu, :ram, :disk, :user_id)');
        $stmt->execute([
            ':os_name' => $os,
            ':cpu'=> $cpu,
            ':ram'=> $ram,
            ':disk'=> $disk,
            ':user_id' => $userId, 
        ]);
        ?>
        <form id="vmForm" action="./Cloud_Proxmox/api/create_vm.php" method="POST" style="display:none;">
            <input type="hidden" name="iso" value="<?= htmlspecialchars($os) ?>">
            <input type="hidden" name="cores" value="<?= htmlspecialchars($cpu) ?>">
            <input type="hidden" name="memory" value="<?= htmlspecialchars($ram) ?>">
            <input type="hidden" name="disk" value="<?= htmlspecialchars($disk) ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
            <input type="hidden" name="ip" value="1.1.1.1">
            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
        </form>
        <script>
            document.getElementById('vmForm').submit();
        </script>
        <?php
        exit();

    } catch (PDOException $e) {
        die("Erreur lors de l'ajout : " . $e->getMessage());
    }

} else {
    header("Location: allocate.php");
    exit();
}*/
?>
