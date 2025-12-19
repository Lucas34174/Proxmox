<?php
require_once "pve2_api.class.php";
require_once "config.php";

function pve()
{
    static $pve = null;
    if ($pve === null) {
        $pve = new PVE2_API(
            PVE_HOST,
            PVE_TOKEN_ID,
            PVE_TOKEN_SECRET,
            PVE_PORT,
            PVE_VERIFY_SSL
        );
        if (!$pve->check_token()) {
            http_response_code(500);
            die(json_encode(["error" => 'Impossible de se connecter']));
        }
    }
    return $pve;
}
?>