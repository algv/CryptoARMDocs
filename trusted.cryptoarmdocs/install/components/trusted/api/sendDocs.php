<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/trusted/api/getUser.php";

use Trusted\CryptoARM\Docs;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

$coreIds = [
    'trusted.cryptoarmdocscrp',
    'trusted.cryptoarmdocsbusiness',
    'trusted.cryptoarmdocsstart',
];
$module_id = 'not found';
foreach ($coreIds as $coreId) {
    $corePathDir = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $coreId . "/";
    if (file_exists($corePathDir)) {
        $module_id = $coreId;
        break;
    }
}

Loader::includeModule($module_id);

define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);

header('Content-Type: application/json; charset=' . LANG_CHARSET);

function echoAndDie($answer) {
    echo json_encode($answer);
    die();
}

$userId = getUserIdByToken($_REQUEST["token"]);
$docsId = json_decode($_REQUEST["ids"]);
$emailAddress = $_REQUEST["email"];

if ($userId["code"]) {
    echoAndDie($userId);
}

if (!$docsId) {
    $answer = [
        "code" => 908,
        "message" => "ids is not find",
        "data" => []
    ];
    echoAndDie($answer);
}

if (!$emailAddress) {
    $answer = [
        "code" => 907,
        "message" => "email is not find",
        "data" => []
    ];
    echoAndDie($answer);
}

if (!Docs\Utils::validateEmailAddress($emailAddress)) {
    $answer = [
        "code" => 950,
        "message" => "email is not valid",
        "data" => []
    ];
    echoAndDie($answer);
}

global $USER;
$USER->Authorize($userId);

$response = Docs\Utils::checkDocuments($docsId, DOC_SHARE_READ, false);
$response["docsFileNotFound"] = $response["docsFileNotFound"]->toArray();
$docsCannotSend = array_merge($response["docsNotFound"], $response["docsFileNotFound"], $response["docsNoAccess"]);
$docsOk = $response["docsOk"]->toArray();

$ids = [];

foreach ($response["docsOk"]->toArray() as $doc) {
    $ids[] = $doc["id"];
}

$data = [];
$params = [
    "event" => "MAIL_EVENT_ID_TO",
    "messageId" => "MAIL_TEMPLATE_ID_TO",
    "arEventFields" => [
        "EMAIL" => $emailAddress,
    ],
    "ids" => $ids,
];

if ($ids) {
    $response = Docs\AjaxCommand::sendEmail($params);
}

$USER->Logout();

if (!$response["success"]) {
    $answer = [
        "code" => 905,
        "message" => "documents not send",
        "date" => $docsId
    ];
    echoAndDie($answer);
}

if (count($docsOk) != count($docsId)) {
    $answer = [
        "code" => 904,
        "message" => "some documents not sent",
        "date" => $docsCannotSend
    ];
    echoAndDie($answer);
}

$answer = [
    "code" => 200,
    "message" => "ok",
];

echoAndDie($answer);
