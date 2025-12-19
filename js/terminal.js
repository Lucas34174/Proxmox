let currentVmid = null;
let currentIp = null;

// Ouvrir le terminal pour une VM
async function openTerminal(vmid) {
  currentVmid = vmid;
  const modal = document.getElementById("terminalModal");
  const statusDiv = document.getElementById("terminal-status");
  const containerDiv = document.getElementById("terminal-container");

  modal.style.display = "block";
  document.getElementById("terminal-vmid").textContent = vmid;

  statusDiv.innerHTML = `
    <div class="loading">
      <i class="fa-solid fa-spinner fa-spin"></i>
      <p>Récupération de l'IP de la VM ${vmid}...</p>
    </div>
  `;
  statusDiv.style.display = "block";
  containerDiv.style.display = "none";

  // Récupérer l'IP
  currentIp = await getVmIp(vmid);

  if (!currentIp) {
    showTerminalError("Impossible de récupérer l'IP de la VM");
    return;
  }

  // Afficher le terminal ttyd
  openTtydIframe(currentIp);
}

// Récupérer l'IP de la VM
async function getVmIp(vmid) {
  try {
    const res = await fetch(`./api/get_vm_ip.php?vmid=${vmid}`);
    const data = await res.json();

    if (data.success && data.ip) {
      return data.ip;
    }
  } catch (e) {
    console.error(e);
  }

  return null;
}

// Ouvrir ttyd dans une iframe
function openTtydIframe(ip) {
  const statusDiv = document.getElementById("terminal-status");
  const containerDiv = document.getElementById("terminal-container");

  statusDiv.style.display = "none";
  containerDiv.style.display = "block";

  containerDiv.innerHTML = `
    <iframe
      src="http://${ip}:7681"
      style="
        width: 100%;
        height: 100%;
        border: none;
        background: #020617;
      "
      allow="clipboard-read; clipboard-write"
    ></iframe>
  `;
}

// Fermer le terminal
function closeTerminal() {
  document.getElementById("terminal-container").innerHTML = "";
  document.getElementById("terminalModal").style.display = "none";

  currentVmid = null;
  currentIp = null;
}

// Erreur
function showTerminalError(message) {
  const statusDiv = document.getElementById("terminal-status");

  statusDiv.innerHTML = `
    <div style="text-align:center;padding:40px;">
      <i class="fa-solid fa-circle-exclamation"
         style="font-size:48px;color:#f87171"></i>
      <p style="color:#f87171;margin-top:20px"><strong>Erreur</strong></p>
      <p style="color:#94a3b8">${message}</p>
      <button onclick="closeTerminal()"
        style="margin-top:20px;padding:8px 16px;
               background:#1e293b;color:#22d3ee;
               border:none;border-radius:6px;cursor:pointer">
        Fermer
      </button>
    </div>
  `;
}

// Fermer la modal en cliquant à l'extérieur
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("terminalModal");

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      closeTerminal();
    }
  });
});
