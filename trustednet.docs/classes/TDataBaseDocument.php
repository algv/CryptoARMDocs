<?php

/**
 * DB interaction class
 */
class TDataBaseDocument
{

    /**
     * Return collection of all last documents.
     * Last documents in the chain have empty child_id field.
     * @global type $DB
     * @return \DocumentCollection
     */
    static function getDocuments()
    {
        global $DB;
        $sql = 'SELECT * FROM ' . TRUSTED_DB_TABLE_DOCUMENTS . ' WHERE CHILD_ID is null';
        $rows = $DB->Query($sql);
        $res = new DocumentCollection();
        while ($array = $rows->Fetch()) {
            $res->add(Document::fromArray($array));
        }
        return $res;
    }

    /**
     * Returns MySQL object with documents filtered by specified filter
     * @global type $DB
     * @param array $arOrder
     * @param type $filter Array
     * @return mysqli_result Object
     */
    static function getIdDocumentsByFilter($arOrder = array(), $filter)
    {
        $arFields = array(
            'DOC' => array(
                'FIELD_NAME' => 'TD.ID',
            ),
            'FILE_NAME' => array(
                'FIELD_NAME' => 'TD.ORIGINAL_NAME',
            ),
            'SIGN' => array(
                'FIELD_NAME' => 'TD.SIGNERS',
            ),
            'STATUS' => array(
                'FIELD_NAME' => 'TDS.STATUS',
            ),
        );

        $find_docId = $filter['DOC'];
        $find_fileName = $filter['FILE_NAME'];
        $find_signInfo = $filter['SIGN'];
        $find_status = $filter['STATUS'];

        global $DB;
        $sql = "
            SELECT
                TD.ID, TDS.STATUS
            FROM
                trn_documents TD LEFT JOIN
                trn_documents_status TDS
            ON
                TD.ID = TDS.DOCUMENT_ID
            WHERE
                isnull(TD.CHILD_ID)";
        if ($find_docId)
            $sql .= " AND TD.ID = " . $find_docId;
        if ($find_fileName)
            $sql .= " AND TD.ORIGINAL_NAME LIKE '%" . $find_fileName . "%'";
        if ($find_signInfo)
            $sql .= " AND TD.SIGNERS LIKE '%" . CDatabase::ForSql($find_signInfo) . "%'";
        if ($find_status != "")
            $sql .= " AND TDS.STATUS = " . $find_status;

        $sOrder = '';
        if (is_array($arOrder)) {
            foreach ($arOrder as $k => $v) {
                if (array_key_exists($k, $arFields)) {
                    $v = strtoupper($v);
                    if ($v != 'DESC') {
                        $v = 'ASC';
                    }
                    if (strlen($sOrder) > 0) {
                        $sOrder .= ', ';
                    }
                    $k = strtoupper($k);
                    $sOrder .= $arFields[$k]['FIELD_NAME'] . ' ' . $v;
                }
            }

            if (strlen($sOrder) > 0) {
                $sql .= ' ORDER BY ' . $sOrder . ';';
            }
        }
        $rows = $DB->Query($sql);
        return $rows;
    }

    /**
     *
     * @global type $DB
     * @param \DocumentStatus $status
     */
    static function saveStatus($status)
    {
        if (!TDataBaseDocument::getStatus($status->getDocument())) {
            TDataBaseDocument::insertStatus($status);
        } else {
            global $DB;
            $sql = 'UPDATE ' . TRUSTED_DB_TABLE_DOCUMENTS_STATUS . ' SET '
                . 'STATUS = ' . $status->getValue() . ', '
                . 'CREATED = CURRENT_TIMESTAMP '
                . 'WHERE DOCUMENT_ID = ' . $status->getDocumentId();
            $DB->Query($sql);
        }
    }

    /**
     *
     * @global type $DB
     * @param \Document $doc
     * @return \DocumentStatus
     */
    static function getStatus($doc)
    {
        return TDataBaseDocument::getStatusById($doc->getId());
    }

    /**
     * Returns document status by document id
     * @global type $DB
     * @param number $docId
     * @return \DocumentStatus
     */
    static function getStatusById($docId)
    {
        global $DB;
        $sql = 'SELECT * FROM ' . TRUSTED_DB_TABLE_DOCUMENTS_STATUS . ' WHERE DOCUMENT_ID = ' . $docId;
        $rows = $DB->Query($sql);
        $array = $rows->Fetch();
        $res = null;
        if ($array) {
            $res = DocumentStatus::fromArray($array);
            $res->setDocumentId($docId);
        }
        return $res;
    }

    /**
     *
     * @global type $DB
     * @param \DocumentStatus $status
     */
    static function insertStatus($status)
    {
        global $DB;
        $sql = 'INSERT INTO ' . TRUSTED_DB_TABLE_DOCUMENTS_STATUS . '  '
            . '(DOCUMENT_ID, STATUS) VALUES ('
            . $status->getDocumentId() . ', '
            . $status->getValue()
            . ')';
        $DB->Query($sql);
    }

    static function removeStatus($status)
    {
        global $DB;
        $sql = 'DELETE FROM ' . TRUSTED_DB_TABLE_DOCUMENTS_STATUS
            . ' WHERE DOCUMENT_ID = ' . $status->getDocumentId();
        $DB->Query($sql);
    }

    /**
     * Saves document in DB. If the document doesn't have an id
     * creates new record for it
     * @global type $DB
     * @param \Document $doc
     */
    static function saveDocument($doc)
    {
        if ($doc->getId() == null) {
            TDataBaseDocument::insertDocument($doc);
        } else {
            global $DB;
            $parentId = $doc->getParentId();
            $childId = $doc->getChildId();
            if (is_null($parentId)) {
                $parentId = 'NULL';
            }
            if (is_null($childId)) {
                $childId = 'NULL';
            }
            $sql = 'UPDATE ' . TRUSTED_DB_TABLE_DOCUMENTS . ' SET '
                . 'ORIGINAL_NAME = "' . CDatabase::ForSql($doc->getName()) . '", '
                . 'SYS_NAME = "' . CDatabase::ForSql($doc->getSysName()) . '", '
                . 'PATH = "' . $doc->getPath() . '", '
                . 'TYPE = ' . $doc->getType() . ', '
                . "SIGNERS = '" . CDatabase::ForSql($doc->getSigners()) . "', "
                . 'PARENT_ID = ' . $parentId . ', '
                . 'CHILD_ID = ' . $childId . ' '
                . 'WHERE ID = ' . $doc->getId();
            $DB->Query($sql);
            TDataBaseDocument::saveDocumentParent($doc, $doc->getId());
        }
    }

    /**
     * Adds new document in DB
     * @global type $DB
     * @param \Document $doc
     */
    static function insertDocument($doc)
    {
        global $DB;
        $parentId = $doc->getParentId();
        $childId = $doc->getChildId();
        if (is_null($parentId)) {
            $parentId = 'NULL';
        }
        if (is_null($childId)) {
            $childId = 'NULL';
        }
        $sql = 'INSERT INTO ' . TRUSTED_DB_TABLE_DOCUMENTS . '  '
            . '(ORIGINAL_NAME, SYS_NAME, PATH, SIGNERS, TYPE, PARENT_ID, CHILD_ID)'
            . 'VALUES ('
            . '"' . CDatabase::ForSql($doc->getName()) . '", '
            . '"' . CDatabase::ForSql($doc->getSysName()) . '", '
            . '"' . $doc->getPath() . '", '
            . "'" . CDatabase::ForSql($doc->getSigners()) . "', "
            . $doc->getType() . ', '
            . $parentId . ', '
            . $childId
            . ')';
        $DB->Query($sql);
        $doc->setId($DB->LastID());
        TDataBaseDocument::saveDocumentParent($doc, $doc->getId());
    }

    /**
     * Updates document parent with child id
     * @param \Document $doc Parent document
     * @param number $id Document id. Default NULL
     */
    protected static function saveDocumentParent($doc, $id = null)
    {
        if ($doc->getParent()) {
            $parent = $doc->getParent();
            $parent->setChildId($id);
            $parent->save();
        }
    }

    /**
     * Removes document from DB
     * @global type $DB
     * @param \Document $doc
     */
    static function removeDocument(&$doc)
    {
        global $DB;
        $sql = 'DELETE FROM ' . TRUSTED_DB_TABLE_DOCUMENTS . '  '
            . 'WHERE ID = ' . $doc->getId();
        $DB->Query($sql);
        $sql = 'DELETE FROM ' . TRUSTED_DB_TABLE_DOCUMENTS_PROPERTY . ' '
            . 'WHERE PARENT_ID = ' . $doc->getId();
        $DB->Query($sql);
        $sql = 'DELETE FROM ' . TRUSTED_DB_TABLE_DOCUMENTS_STATUS . ' '
            . 'WHERE DOCUMENT_ID = ' . $doc->getId();
        $DB->Query($sql);
        // Removes childId from parent document
        TDataBaseDocument::saveDocumentParent($doc);
    }

    /**
     * Removes document and all of its parents from DB
     * @global type $DB
     * @param \Document $doc
     */
    static function removeDocumentRecursively(&$doc)
    {
        global $DB;
        if ($doc->getParent()) {
            $parent = $doc->getParent();
        }
        TDataBaseDocument::removeDocument($doc);
        if ($parent) {
            TDataBaseDocument::removeDocumentRecursively($parent);
        }
    }

    /**
     * Get document from DB by id
     * @global type $DB
     * @param number $id Document id
     * @return \Document
     */
    static function getDocumentById($id)
    {
        global $DB;
        $sql = 'SELECT * FROM ' . TRUSTED_DB_TABLE_DOCUMENTS . ' WHERE ID = ' . $id;
        /*
                if(!is_object($DB)) {
                $DB = new TDataBase();
                $DB->Connect(TRUSTED_DB_HOST, TRUSTED_DB_NAME, TRUSTED_DB_LOGIN, TRUSTED_DB_PASSWORD);
            }
        */
        $rows = $DB->Query($sql);
        $array = $rows->Fetch();
        $res = Document::fromArray($array);
        return $res;
    }

    /**
     * Returns collection of last document by name
     * @global type $DB
     * @param string $name
     * @return \DocumentCollection
     */
    static function getDocumentsByName($name)
    {
        global $DB;
        $sql = 'SELECT * FROM ' . TRUSTED_DB_TABLE_DOCUMENTS
            . ' WHERE ORIGINAL_NAME = "' . CDatabase::ForSql($name) . '"'
            . ' AND CHILD_ID is null';
        $rows = $DB->Query($sql);
        $docs = new DocumentCollection();
        while ($array = $rows->Fetch()) {
            $doc = Document::fromArray($array);
            $docs->add($doc);
        }
        return $docs;
    }

    /**
     * Saves property in DB.
     * If property id is null creates new record
     * @global type $DB
     * @param \Property $property
     * @param string $tableName
     */
    static function saveProperty($property, $tableName)
    {
        if ($property->getId() == null) {
            TDataBaseDocument::insertProperty($property, $tableName);
        } else {
            global $DB;
            $sql = 'UPDATE ' . $tableName . ' SET PARENT_ID = ' . $property->getParentId() . ', TYPE="' . CDatabase::ForSql($property->getType()) . '", VALUE="' . CDatabase::ForSql($property->getValue()) . '" WHERE ID = ' . $property->getId();
            $DB->Query($sql);
        }
    }

    /**
     * Adds property to DB
     * @global type $DB
     * @param \Property $property
     * @param string $tableName
     */
    static function insertProperty($property, $tableName)
    {
        global $DB;
        $sql = 'INSERT INTO ' . $tableName . ' (PARENT_ID, TYPE, VALUE) VALUES (' . $property->getParentId() . ', "' . CDatabase::ForSql($property->getType()) . '", "' . CDatabase::ForSql($property->getValue()) . '")';
        $DB->Query($sql);
        $property->setId($DB->LastID());
    }

    /**
     * Gets property collection from DB by specified type and value fields
     * @global type $DB
     * @param string $tableName
     * @param string $type TYPE field
     * @param string $value VALUE field
     * @return \PropertyCollection
     */
    static function getPropertiesByType($tableName, $type, $value)
    {
        global $DB;
        $sql = 'SELECT * FROM ' . $tableName . ' WHERE TYPE="' . CDatabase::ForSql($type) . '" AND VALUE = "' . CDatabase::ForSql($value) . '"';
        $rows = $DB->Query($sql);
        $res = new PropertyCollection();
        while ($array = $rows->Fetch()) {
            $res->add(Property::fromArray($array));
        }
        return $res;
    }

    /**
     * Get property from DB by value
     * @global type $DB
     * @param string $tableName
     * @param string $fldName
     * @param string $value
     * @return \Property
     */
    static function getPropertyBy($tableName, $fldName, $value)
    {
        $props = TDataBaseDocument::getPropertiesBy($tableName, $fldName, $value);
        $res = null;
        if ($props->count()) {
            $res = $props->items(0);
        }
        return $res;
    }

    /**
     * Gets property collection from DB by value
     * @global type $DB
     * @param string $tableName
     * @param string $fldName
     * @param string $value
     * @return \PropertyCollection
     */
    static function getPropertiesBy($tableName, $fldName, $value)
    {
        global $DB;
        $sql = 'SELECT * FROM ' . $tableName . ' WHERE  ' . $fldName . ' = "' . CDatabase::ForSql($value) . '"';
        $rows = $DB->Query($sql);
        $res = new PropertyCollection();
        while ($array = $rows->Fetch()) {
            $res->add(Property::fromArray($array));
        }
        return $res;
    }

    /**
     * @param string $tableName
     * @param number $parentId
     * @return PropertyCollection
     */
    static function getPropertiesByParentId($tableName, $parentId)
    {
        return TDataBaseDocument::getPropertiesBy($tableName, 'PARENT_ID', $parentId);
    }

}

