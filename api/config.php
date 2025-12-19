<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration Proxmox avec API Token
define("PVE_HOST", "192.168.11.37");
define("PVE_TOKEN_ID", "apiuser@pve!mitToken");
define("PVE_TOKEN_SECRET", "25cb6fc7-c570-479f-a349-ca4c790b540a");
define("PVE_PORT", 8006);
define("PVE_VERIFY_SSL", false);
define("PVE_NODE", "pve");
?>