<?php

class Driver extends BaseDriver
{

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function getCompareTables()
    {
        return $this->_getTableAndViewResult('BASE TABLE');
    }

    public function getAdditionalTableInfo()
    {
        $tableNames = array_map(function($tableName) { return "'" . trim($tableName) . "'";}, explode(',', COMPARE_TABLES));
        $filterTableNames = COMPARE_TABLES ? ' AND TABLE_NAME IN (' . implode(',', $tableNames) . ')' : '';
        $type = 'BASE TABLE';
        $query = "SELECT
                TABLE_NAME ARRAY_KEY_1,
                ENGINE engine,
                TABLE_COLLATION collation,
                Auto_increment auto_increment,
            FROM
                information_schema.TABLES
            WHERE
                TABLE_SCHEMA = '<<BASENAME>>' AND
                TABLE_TYPE = '{$type}'
                {$filterTableNames}
            ";
        return $this->_getCompareArray($query, false, true);

    }

    public function getCompareViews()
    {
        return $this->_getTableAndViewResult('VIEW');
    }

    public function getCompareProcedures()
    {
        return $this->_getRoutineResult('PROCEDURE');
    }

    public function getCompareFunctions()
    {
        return $this->_getRoutineResult('FUNCTION');
    }

    public function getCompareKeys()
    {
        $tableNames = array_map(function($tableName) { return "'" . trim($tableName) . "'";}, explode(',', COMPARE_TABLES));
        $filterTableNames = COMPARE_TABLES ? ' AND TABLE_NAME IN (' . implode(',', $tableNames) . ')' : '';
        $query = "SELECT
                    CONCAT(TABLE_NAME, ' [', INDEX_NAME, '] ') ARRAY_KEY_1,
                    COLUMN_NAME  ARRAY_KEY_2,
                    CONCAT('(' , SEQ_IN_INDEX, ')') dtype
                  FROM INFORMATION_SCHEMA.STATISTICS
                  WHERE
                    TABLE_SCHEMA = '<<BASENAME>>'
                    {$filterTableNames}
                  ORDER BY
                    TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX";
        return $this->_getCompareArray($query);
    }

    public function getCompareTriggers()
    {
        $query = "SELECT
                    CONCAT(EVENT_OBJECT_TABLE, '::' , TRIGGER_NAME, ' [', EVENT_MANIPULATION, '/',  ACTION_ORIENTATION, '/', ACTION_TIMING, '] - ', ACTION_ORDER) ARRAY_KEY_1,
                    ACTION_STATEMENT ARRAY_KEY_2,
                    '' dtype
                  FROM
                    information_schema.TRIGGERS
                  WHERE
                    TRIGGER_SCHEMA = '<<BASENAME>>'";
        return $this->_getCompareArray($query);
    }

    private function _getTableAndViewResult($type)
    {
        $tableNames = array_map(function($tableName) { return "'" . trim($tableName) . "'";}, explode(',', COMPARE_TABLES));
        $filterTableNames = COMPARE_TABLES ? ' AND cl.TABLE_NAME IN (' . implode(',', $tableNames) . ')' : '';
        $query = "SELECT
                    cl.TABLE_NAME ARRAY_KEY_1,
                    cl.COLUMN_NAME ARRAY_KEY_2,
                    cl.COLUMN_TYPE dtype
                  FROM information_schema.columns cl,  information_schema.TABLES ss
                  WHERE
                    cl.TABLE_NAME = ss.TABLE_NAME AND
                    cl.TABLE_SCHEMA = '<<BASENAME>>' AND
                    ss.TABLE_TYPE = '{$type}'
                    {$filterTableNames}
                  ORDER BY
                    cl.table_name ";

        return $this->_getCompareArray($query);
    }

    private function _getRoutineResult($type)
    {
        $query = "SELECT
                    ROUTINE_NAME ARRAY_KEY_1,
                    ROUTINE_DEFINITION ARRAY_KEY_2,
                    '' dtype
                  FROM
                    information_schema.ROUTINES
                  WHERE
                    ROUTINE_SCHEMA = '<<BASENAME>>' AND
                    ROUTINE_TYPE = '{$type}'
                  ORDER BY
                    ROUTINE_NAME";
        return $this->_getCompareArray($query, true);
    }

}