<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2002-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */

/**
 * A table decorator that allows filtering out table columns from results.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_DataSet_TableFilter extends PHPUnit_Extensions_Database_DataSet_AbstractTable
{

    /**
     * The table meta data being decorated.
     * @var PHPUnit_Extensions_Database_DataSet_ITable
     */
    protected $originalTable;

    /**
     * Creates a new table filter using the original table
     *
     * @param $originalTable PHPUnit_Extensions_Database_DataSet_ITable
     * @param $excludeColumns Array @deprecated, use the set* methods instead.
     */
    public function __construct(PHPUnit_Extensions_Database_DataSet_ITable $originalTable, Array $excludeColumns = array())
    {
        $this->originalTable = $originalTable;
        $this->setTableMetaData(new PHPUnit_Extensions_Database_DataSet_TableMetaDataFilter($originalTable->getTableMetaData()));
        $this->addExcludeColumns($excludeColumns);
    }

    /**
     * Returns the number of rows in this table.
     *
     * @return int
     */
    public function getRowCount()
    {
        return $this->originalTable->getRowCount();
    }

    /**
     * Returns the value for the given column on the given row.
     *
     * @param int $row
     * @param int $column
     */
    public function getValue($row, $column)
    {
        if (in_array($column, $this->getTableMetaData()->getColumns())) {
            return $this->originalTable->getValue($row, $column);
        } else {
            throw new InvalidArgumentException("The given row ({$row}) and column ({$column}) do not exist in table {$this->getTableMetaData()->getTableName()}");
        }
    }

    /**
     * Sets the columns to src in the table.
     * @param Array $includeColumns
     */
    public function addIncludeColumns(Array $includeColumns)
    {
        $this->tableMetaData->addIncludeColumns($includeColumns);
    }

    /**
     * Clears the included columns.
     */
    public function clearIncludeColumns()
    {
        $this->tableMetaData->clearIncludeColumns();
    }

    /**
     * Sets the columns to exclude from the table.
     * @param Array $excludeColumns
     */
    public function addExcludeColumns(Array $excludeColumns)
    {
        $this->tableMetaData->addExcludeColumns($excludeColumns);
    }

    /**
     * Clears the included columns.
     */
    public function clearExcludeColumns()
    {
        $this->tableMetaData->clearExcludeColumns();
    }

	/**
     * Checks if a given row is in the table
     *
     * @param array $row
     *
     * @return bool
     */
    public function assertContainsRow(Array $row)
    {
        $this->loadData();
        return parent::assertContainsRow($row);
    }

	/**
	 * Loads data into local data table if it's not already loaded
     */
    protected function loadData()
    {
        if ($this->data === NULL) {
            $data = array();
            for($row = 0;$row < $this->originalTable->getRowCount();$row++) {
                $tRow = array();
                foreach($this->getTableMetaData()->getColumns() as $col) {
                    $tRow[$col] = $this->getValue($row, $col);
                }
                $data[$row] = $tRow;
            }
            $this->data   = $data;
        }
    }
}
