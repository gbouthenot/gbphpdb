<?php
namespace Gb\Excelxml;

/**
 * Parse Office 2003 xml and return an array:
 * Use \SimpleXMLElement, so the whole data file must fit in memory
 * @return array(array(array()))
 * array_of_worksheets(
 *     "worksheetpagename") => array_of_rows(
 *         array(cellvalues)
 *     )
 * )
 * @example
 *     $c = new \Gb\Excelxml\Excelxml(array("insertNulls"=>true, "getType"=>false));
 *     $sheets = $c->parse(file_get_contents("deverse03d.xml"));
 */
class Excelxml
{
    protected $insertNulls = true;
    protected $getType = false;

    /**
     * @param $opts["insertNulls"] (true): if they are holes between rows or cells,
     *        insert null values or empty rows
     * @param $opts["getType"] (false): if true, values are rendered as "(type)value",
     *        otherwise "value"
     */
    public function __construct($opts = array()) {
        if (isset($opts["insertNulls"])) {
            $this->insertNulls = $opts["insertNulls"];
        }
        if (isset($opts["getType"])) {
            $this->getType = $opts["getType"];
        }
    }

    /**
     * Process an excel file and replace prefixed namespaces, so SimpleXMLElement can handle it
     * @param string $string
     * @return string
     */
    public function fromExcel($string) {
        $string = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $string);
        $string = str_replace(' xmldatans="', ' ns="', $string);
        $string = str_replace(' xmlns="', ' ns="', $string);
        return $string;
    }

    /**
     * Put back prefixed namespaces, counterpart of fromExcel()
     * @param string $string
     * @return string
     */
    public function toExcel($string) {
        $replace = array(
            ' ns="urn:schemas-microsoft-com:office:excel"',
            ' xmlns="urn:schemas-microsoft-com:office:excel"',

             ' ns="urn:schemas-microsoft-com:office:spreadsheet"',
             ' xmlns="urn:schemas-microsoft-com:office:spreadsheet"',

             ' ns="urn:schemas-microsoft-com:office:office"',
             ' xmlns="urn:schemas-microsoft-com:office:office"',
        );
        for ($i=0; $i < count($replace); $i += 2) {
            $string = str_replace($replace[$i], $replace[$i+1], $string);
        }
        return $string;
    }

    /**
     * Parse Office 2003 xml and return an array
     * Call $this->parseWorksheet for each worksheet
     * @param string $string
     * @return an array of worksheets indexed with their names
     */
    public function asArray($string) {
        $string = $this->fromExcel($string);
        $xmldata = new \SimpleXMLElement($string);

        // extrait les feuilles
        $xmlWorksheets = $xmldata->xpath('/Workbook/Worksheet');

        foreach ($xmlWorksheets as $xmlWorksheet) {
            $aWorksheet = $this->parseWorksheet($xmlWorksheet);

            $att = $xmlWorksheet->attributes("urn:schemas-microsoft-com:office:spreadsheet", false);
            $name = (string) $att->Name;    // Name of the sheet

            $aWorksheets[$name] = $aWorksheet;
        }

        return $aWorksheets;
    }

    /**
     * Calls $this->parseRow for each rows of the worksheet
     * Return an array containing the rows, with proper indexes
     * if they are holes and if insertNulls is true, insert empty rows between rows
     */
    protected function parseWorksheet($xmlWorksheet) {
        $aRows = array();
        $iRow = 0;

        // extract lines
        foreach ($xmlWorksheet->xpath("Table/Row") as $xmlRow) {
            $aRow = $this->parseRow($xmlRow);

            $att = $xmlRow->attributes("urn:schemas-microsoft-com:office:spreadsheet", false);
            $index = (int) ((string) $att->Index);
            while($index > ($iRow + 1)) {
                if ($this->insertNulls) {
                    $aRows[$iRow] = array();
                }
                $iRow++;
            }

            $aRows[$iRow++] = $aRow;
        }

        return $aRows;
    }

    /**
     * Calls $this->parseCell for each cells of the row
     * Return an array containing the cells, with proper indexes
     * if they are holes and if insertNulls is true, insert null between cells
     */
    protected function parseRow($xmlRow) {
        $aRow = array();
        $iCol = 0;
        foreach ($xmlRow->xpath("Cell") as $xmlCell) {
            // récupère les attributs msoffice, va chercher l'attribut "index", pour placer
            // la valeur au bon endroit
            $att = $xmlCell->attributes("urn:schemas-microsoft-com:office:spreadsheet", false);
            $index = (int) ((string) $att->Index);
            while($index > ($iCol + 1)) {
                if ($this->insertNulls) {
                    $aRow[$iCol] = null;
                }
                $iCol++;
            }

            $aRow[$iCol++] = $this->parseCell($xmlCell);
        }
        return $aRow;
    }

    /**
     * Get the value of a cell.
     * Return "value" or "(type)value" if getType is true
     */
    protected function parseCell($xmlCell) {
        $data = $xmlCell->Data;
        if ($this->getType) {
            $att = $data->attributes("urn:schemas-microsoft-com:office:spreadsheet", false);
            $type = (string) $att->Type;
            $value = "($type)" . (string) $data; // valeur de la colonne
        } else {
            $value = (string) $data; // valeur de la colonne
        }

        return $value;
    }
}
