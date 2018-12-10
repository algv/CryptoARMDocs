<?php

use Trusted\CryptoARM\Docs;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule("trusted.cryptoarmdocs");

define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);

header('Content-Type: application/json; charset=' . LANG_CHARSET);

// AJAX Controller

$command = $_GET['command'];
if (isset($command)) {
    $params = $_POST;
    switch ($command) {
        case "activateJwtToken":
            $res = Docs\AjaxCommand::activateJwtToken($params);
            break;
        case "registerAccountNumber":
            $res = Docs\AjaxCommand::registerAccountNumber();
            break;
        case "checkAccountBalance":
            $res = Docs\AjaxCommand::checkAccountBalance($params);
            break;
        case "getAccountHistory":
            $res = Docs\AjaxCommand::getAccountHistory($params);
            break;
        case "sign":
            $res = Docs\AjaxCommand::sign($params);
            break;
        case "upload":
            $res = Docs\AjaxCommand::upload($params);
            break;
        case "verify":
            $res = Docs\AjaxCommand::verify($params);
            break;
        case "block":
            $res = Docs\AjaxCommand::block($params);
            break;
        case "unblock":
            $res = Docs\AjaxCommand::unblock($params);
            break;
        case "remove":
            $res = Docs\AjaxCommand::remove($params);
            break;
        case "view":
            $res = Docs\AjaxCommand::view($params);
            break;
        case "download":
            $res = Docs\AjaxCommand::download($params);
            break;
        case "content":
            $res = Docs\AjaxCommand::content($_GET);
            return $res;
            break;
        // case "token":
        //     $res = Docs\AjaxCommand::token($_GET);
        //     break;
        default:
            $res = array("success" => false, "message" => "Unknown command '" . $command . "'");
    }
}
echo json_encode($res);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");

