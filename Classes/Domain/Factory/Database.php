<?php

namespace B13\Container\Domain\Factory;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

class Database implements SingletonInterface
{
    /**
     * @var int
     */
    protected $backendUserId = 0;


    public function __construct()
    {
         $this->backendUserId = 0;
    }

    protected function parseDatabaseResultToInt(array $row)
    {
        $integerKeys = ['deleted', 'hidden', 'pid', 'uid', 'tx_container_parent', 'colPos', 'sys_language_uid', 'l18n_parent'];
        foreach ($integerKeys as $key) {
            $row[$key] = (int)$row[$key];
        }
        return $row;
    }


    /**
     * @return DatabaseConnection
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return string
     */
    protected function getAdditionalWhereClause()
    {

        if (TYPO3_MODE === 'BE') {
            return ' AND deleted=0';
        } elseif (TYPO3_MODE === 'FE') {
            return ' AND deleted=0 AND hidden=0';
        }
    }

    public function fetchRecordsByPidAndLanguage($pid, $language)
    {
        $additionalWhereClause = $this->getAdditionalWhereClause();
        $rows = (array)$this->getDatabase()
            ->exec_SELECTgetRows(
                '*',
                'tt_content',
                'sys_language_uid=' . (int)$language . ' AND pid=' . (int)$pid . $additionalWhereClause,
                '',
                'sorting ASC'
            );
        $records = [];
        foreach ($rows as $row) {
            $records[] = $this->parseDatabaseResultToInt($row);
        }
        return $records;
    }

    /**
     * @param int $uid
     * @return array|null
     */
    public function fetchOneRecord($uid)
    {
        $record = $this->getDatabase()
            ->exec_SELECTgetSingleRow(
                '*',
                'tt_content',
                'uid=' . (int)$uid . $this->getAdditionalWhereClause()
            );
        if (!$record) {
            return null;
        }
        return $this->parseDatabaseResultToInt($record);
    }

    /**
     * @param array $record
     * @return array|null
     */
    public function fetchOneDefaultRecord(array $record)
    {

        $record = $this->getDatabase()
            ->exec_SELECTgetSingleRow(
                '*',
                'tt_content',
                'uid=' . $record['l18n_parent'] . ' AND sys_language_uid=0' . $this->getAdditionalWhereClause()
            );
        if (!$record) {
            return null;
        }
        return $this->parseDatabaseResultToInt($record);
    }

    /**
     * @param int $parent
     * @param int $language
     * @return array
     */
    public function fetchRecordsByParentAndLanguage($parent, $language)
    {
        $rows = (array)$this->getDatabase()
            ->exec_SELECTgetRows(
                '*',
                'tt_content',
                'sys_language_uid=' . (int)$language . ' AND tx_container_parent=' . (int)$parent . $this->getAdditionalWhereClause(),
                '',
                'sorting ASC'
            );
        $records = [];
        foreach ($rows as $row) {
            $records[] = $this->parseDatabaseResultToInt($row);
        }
        return $records;
    }

    /**
     * @param array $records
     * @param int $language
     * @return array
     */
    public function fetchOverlayRecords(array $records, $language)
    {
        $uids = [];
        foreach ($records as $record) {
            $uids[] = $record['uid'];
            if ($record['t3ver_oid'] > 0) {
                $uids[] = $record['t3ver_oid'];
            }
        }

        $rows = (array)$this->getDatabase()
            ->exec_SELECTgetRows(
                '*',
                'tt_content',
                'sys_language_uid=' . (int)$language . ' AND l18n_parent in (' . implode(',', $uids) . ')' . $this->getAdditionalWhereClause()
            );
        $records = [];
        foreach ($rows as $row) {
            $records[] = $this->parseDatabaseResultToInt($row);
        }
        return $records;
    }

    /**
     * @param int $uid
     * @param int $language
     * @return array
     */
    public function fetchOneOverlayRecord($uid, $language)
    {
        $record = $this->getDatabase()
            ->exec_SELECTgetSingleRow(
                '*',
                'tt_content',
                'l18n_parent=' . (int)$uid . ' AND sys_language_uid=' . (int)$language . $this->getAdditionalWhereClause()
            );
        if (!$record) {
            return null;
        }
        return $this->parseDatabaseResultToInt($record);
    }
}
