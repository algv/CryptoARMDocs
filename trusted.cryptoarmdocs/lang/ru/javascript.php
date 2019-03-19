<?php
use Trusted\CryptoARM\Docs;

require_once __DIR__ . "/../../classes/Utils.php";

if (Docs\Utils::isSecure()) {
    $MESS["TR_CA_DOCS_AJAX_CONTROLLER"] = "https://" . $_SERVER["HTTP_HOST"] . "/bitrix/components/trusted/docs/ajax.php";
} else {
    $MESS["TR_CA_DOCS_AJAX_CONTROLLER"] = "http://" . $_SERVER["HTTP_HOST"]. "/bitrix/components/trusted/docs/ajax.php";
}

$MESS["TR_CA_DOCS_ERROR_FILE_NOT_FOUND"] = "Не найдены файлы соответствующие следующим документам:";
$MESS["TR_CA_DOCS_ERROR_DOC_NOT_FOUND"] = "Нет найдены документы со следующими идентификаторами: ";
$MESS["TR_CA_DOCS_ERROR_DOC_BLOCKED"] = "Некоторые документы заблокированы с связи с отправкой на подпись:";
$MESS["TR_CA_DOCS_ERROR_DOC_ROLE_SIGNED"] = "Некоторые документы уже подписаны:";
$MESS["TR_CA_DOCS_ERROR_DOC_NO_ACCESS"] = "Нет доступа к некоторым документам: ";

$MESS["TR_CA_DOCS_ALERT_NO_CLIENT"] = "Для подписи документов установите и запустите КриптоАРМ ГОСТ. Скачать КриптоАРМ ГОСТ можно в нашем интернет-магазине https://cryptoarm.ru/cryptoarm-gost";
$MESS["TR_CA_DOCS_ALERT_HTTP_WARNING"] = "Подпись документа невозможна на незащищенном соединении (\"HTTP\" протокол).";
$MESS["TR_CA_DOCS_ALERT_DOC_NOT_FOUND"] = "Документы со следующими индентификаторами не были обнаружены в базе данных";
$MESS["TR_CA_DOCS_ALERT_DOC_BLOCKED"] = "Некоторые документы заблокированы и не могут быть отправлены на подпись";
$MESS["TR_CA_DOCS_ALERT_REMOVE_ACTION_CONFIRM"] = "Вы действительно хотите удалить документ? Эту операцию невозможно отменить.";
$MESS["TR_CA_DOCS_ALERT_LOST_DOC_REMOVE_CONFIRM_PRE"] = "Некоторые файлы не были найдены в хранилище:";
$MESS["TR_CA_DOCS_ALERT_LOST_DOC_REMOVE_CONFIRM_POST"] = "Удалить эти записи о файлах из базы данных?";
$MESS["TR_CA_DOCS_ALERT_LOST_DOC"] = "Некоторые файлы не были обнаружены в хранилище:";

$MESS["TR_CA_DOCS_ACT_SEND_MAIL_TO_PROMPT"] = "Укажите e-mail, на который вы хотите отправить документы:";
$MESS["TR_CA_DOCS_ACT_SEND_MAIL_SUCCESS"] = "Письмо отправлено";
$MESS["TR_CA_DOCS_ACT_SEND_MAIL_FAILURE"] = "Ошибка, письмо не отправлено";

$MESS["TR_CA_DOCS_ACT_SHARE"] = "Укажите e-mail пользователя которому вы хотите дать доступ к данному документу:";
