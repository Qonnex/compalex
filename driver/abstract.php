<?php

abstract class BaseDriver
{
    protected $_dsn = array();

    protected static $_instance = null;

    protected function _getFirstConnect()
    {
        return $this->_getConnect(FIRST_DSN, FIRST_BASE_NAME, FIRST_DSN_OPTIONS);
    }


    protected function _getSecondConnect()
    {
        return $this->_getConnect(SECOND_DSN, SECOND_BASE_NAME, SECOND_DSN_OPTIONS);
    }

    protected function _getConnect($dsn, $baseName, $dsnOptions = [])
    {
        if (!isset($this->_dsn[$dsn])) {
            $pdsn = parse_url($dsn);

            if (in_array(DRIVER, array('sqlserv', 'dblib', 'mssql'))) {
                $dsn = DRIVER . ':host=' . $pdsn['host'] . ':' . $pdsn['port'] . ';dbname=' . substr($pdsn['path'], 1, 1000) . ';charset=' . DATABASE_ENCODING;
            } elseif (in_array(DRIVER, array('oci', 'oci8'))) {
                $dsn = 'oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=' . $pdsn['host'] . ')(PORT=' . $pdsn['port'] . '))(CONNECT_DATA=(SERVICE_NAME=' . substr($pdsn['path'], 1, 1000) . ')));charset=' . DATABASE_ENCODING;
            } else {
                $dsn = DRIVER . ':host=' . $pdsn['host'] . ';port=' . $pdsn['port'] . ';dbname=' . substr($pdsn['path'], 1, 1000) . (DRIVER !== 'pgsql' ? ';charset=' . DATABASE_ENCODING : '');
            }

            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );
            if($dsnOptions['DATABASE_SSL'] == 'true') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = true;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            $this->_dsn[$dsn] = new PDO($dsn, $pdsn['user'], isset($pdsn['pass']) ? $pdsn['pass'] : '', $options);
        }
        return $this->_dsn[$dsn];
    }

    protected function _select($query, $connect, $baseName)
    {
        $out = array();

        $query = str_replace('<<BASENAME>>', $baseName, $query);

        $stmt = $connect->prepare($query);
        $stmt->execute();

        while ($row = @$stmt->fetch()) {
            if (!isset($row['dtype']) && isset($row['DTYPE'])) {
                $row['dtype'] = $row['DTYPE'];
            }
            $out[] = $row;
        }
        return $out;
    }


    protected function _getCompareArray($query, $diffMode = false, $ifOneLevelDiff = false)
    {

        $out = array();
        $fArray = $this->_prepareOutArray($this->_select($query, $this->_getFirstConnect(), FIRST_BASE_NAME), $diffMode, $ifOneLevelDiff);
        $sArray = $this->_prepareOutArray($this->_select($query, $this->_getSecondConnect(), SECOND_BASE_NAME), $diffMode, $ifOneLevelDiff);

        $firstLowercaseArray = array_change_key_case($fArray, CASE_LOWER);
        $secondLowercaseArray = array_change_key_case($sArray, CASE_LOWER);
        
        $allTables = array_unique(array_merge(array_keys($firstLowercaseArray), array_keys($secondLowercaseArray)));

        //$allTables = array_unique(array_merge(array_keys($fArray), array_keys($sArray)));
        sort($allTables);

        foreach($fArray as $key => $value) {
            $firstArrayKeys[strtolower($key)] = $key;
        }
        foreach($sArray as $key => $value) {
            $secondArrayKeys[strtolower($key)] = $key;
        }

        foreach ($allTables as $v) {
            $allFields = array_unique(array_merge(array_keys((array)@$fArray[$firstArrayKeys[$v]]), array_keys((array)@$sArray[$secondArrayKeys[$v]])));
            foreach ($allFields as $f) {
                switch (true) {
                    case (!isset($fArray[$firstArrayKeys[$v]][$f])):
                    {
                        if (is_array($sArray[$secondArrayKeys[$v]][$f])) $sArray[$secondArrayKeys[$v]][$f]['isNew'] = true;
                        break;
                    }
                    case (!isset($sArray[$secondArrayKeys[$v]][$f])):
                    {
                        if (is_array($fArray[$firstArrayKeys[$v]][$f])) $fArray[$firstArrayKeys[$v]][$f]['isNew'] = true;
                        break;
                    }
                    case (isset($fArray[$firstArrayKeys[$v]][$f]['dtype']) && isset($sArray[$secondArrayKeys[$v]][$f]['dtype']) && ($fArray[$firstArrayKeys[$v]][$f]['dtype'] != $sArray[$secondArrayKeys[$v]][$f]['dtype'])) :
                    {
                        $fArray[$firstArrayKeys[$v]][$f]['changeType'] = true;
                        $sArray[$secondArrayKeys[$v]][$f]['changeType'] = true;
                        break;
                    }
                }
            }
            $out[$v] = array(
                'fArray' => @$fArray[$firstArrayKeys[$v]],
                'sArray' => @$sArray[$secondArrayKeys[$v]]
            );
        }
        return $out;
    }

    private function _prepareOutArray($result, $diffMode, $ifOneLevelDiff)
    {
        $mArray = array();
        foreach ($result as $r) {
            if ($diffMode) {
                foreach (explode("\n", $r['ARRAY_KEY_2']) as $pr) {
                    $mArray[$r['ARRAY_KEY_1']][$pr] = $r;
                }

            } else {
                if ($ifOneLevelDiff) {
                    $mArray[$r['ARRAY_KEY_1']] = $r;
                } else {
                    $mArray[$r['ARRAY_KEY_1']][$r['ARRAY_KEY_2']] = $r;
                }
            }
        }
        return $mArray;
    }

    public function getCompareTables()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getAdditionalTableInfo()
    {
        return array();
    }

    public function getCompareIndex()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getCompareProcedures()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getCompareFunctions()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getCompareViews()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getCompareKeys()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getCompareTriggers()
    {
        throw new Exception(__METHOD__ . ' Not work');
    }

    public function getTableRows($connectionType, $baseName, $tableName, $rowCount = SAMPLE_DATA_LENGTH)
    {
        if (!$baseName) throw new Exception('$baseName is not set');
        if (!$tableName) throw new Exception('$tableName is not set');
        $rowCount = (int)$rowCount;
        $tableName = preg_replace("$[^A-z0-9.,-_]$", '', $tableName);
        switch (DRIVER) {
            case "mssql":
            case "dblib":
            case "mssql":
            case "sqlsrv":
                $query = 'SELECT TOP ' . $rowCount . ' * FROM ' . $baseName . '..' . $tableName;
                break;
            case "pgsql":
            case "mysql":
                $query = 'SELECT * FROM ' . $tableName . ' LIMIT ' . $rowCount;
                break;
            case "oci":
            case "oci8":
                $query = 'SELECT * FROM ' . $tableName . ' FETCH FIRST ' . $rowCount . ' ROWS ONLY ';
                break;
            default:
                throw new Exception('Select query not set');

        }
        //if ($baseName === FIRST_BASE_NAME) {
        if ($connectionType === 'first') {
            $result = $this->_select($query, $this->_getFirstConnect(), FIRST_BASE_NAME);
        } else {
            $result = $this->_select($query, $this->_getSecondConnect(), SECOND_BASE_NAME);
        }

        if ($result) {
            $firstRow = array_shift($result);

            $out[] = array_keys($firstRow);
            $out[] = array_values($firstRow);

            foreach ($result as $row) {
                $out[] = array_values($row);
            }
        } else {
            $out = array();
        }

        if (DATABASE_ENCODING != 'utf-8' && $out) {
            // $out = array_map(function($item){ return array_map(function($itm){ return iconv(DATABASE_ENCODING, 'utf-8', $itm); }, $item); }, $out);
        }

        return $out;
    }


}