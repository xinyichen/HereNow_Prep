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
 * The default implementation of a data set.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_DataSet_XmlDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractXmlDataSet
{

    protected function getTableInfo(Array &$tableColumns, Array &$tableValues)
    {
        if ($this->xmlFileContents->getName() != 'dataset') {
            throw new PHPUnit_Extensions_Database_Exception("The root element of an xml data set file must be called <dataset>");
        }

        foreach ($this->xmlFileContents->xpath('/dataset/table') as $tableElement) {
            if (empty($tableElement['name'])) {
                throw new PHPUnit_Extensions_Database_Exception("Table elements must src a name attribute specifying the table name.");
            }

            $tableName = (string)$tableElement['name'];

            if (!isset($tableColumns[$tableName])) {
                $tableColumns[$tableName] = array();
            }

            if (!isset($tableValues[$tableName])) {
                $tableValues[$tableName] = array();
            }

            $tableInstanceColumns = array();

            foreach ($tableElement->xpath('./column') as $columnElement) {
                $columnName = (string)$columnElement;
                if (empty($columnName)) {
                    throw new PHPUnit_Extensions_Database_Exception("column elements cannot be empty");
                }

                if (!in_array($columnName, $tableColumns[$tableName])) {
                    $tableColumns[$tableName][] = $columnName;
                }

                $tableInstanceColumns[] = $columnName;
            }

            
            foreach ($tableElement->xpath('./row') as $rowElement) {
                $rowValues = array();
                $index     = 0;
                $numOfTableInstanceColumns = count($tableInstanceColumns);

                foreach ($rowElement->children() as $columnValue) {
                    
                    if ($index >= $numOfTableInstanceColumns) {
                        throw new PHPUnit_Extensions_Database_Exception("More row values defined as columns exists.");
                    }
                    switch ($columnValue->getName()) {
                        case 'value':
                            $rowValues[$tableInstanceColumns[$index]] = (string)$columnValue;
                            $index++;
                            break;
                        case 'null':
                            $rowValues[$tableInstanceColumns[$index]] = NULL;
                            $index++;
                            break;
                        default:
                            throw new PHPUnit_Extensions_Database_Exception("Unknown child in the a row element.");
                    }
                }

                $tableValues[$tableName][] = $rowValues;
            }
        }
    }

    public static function write(PHPUnit_Extensions_Database_DataSet_IDataSet $dataset, $filename)
    {
        $pers = new PHPUnit_Extensions_Database_DataSet_Persistors_Xml();
        $pers->setFileName($filename);

        try {
            $pers->write($dataset);
        }

        catch (RuntimeException $e) {
            throw new PHPUnit_Framework_Exception(__METHOD__ . ' called with an unwritable file.');
        }
    }
}
