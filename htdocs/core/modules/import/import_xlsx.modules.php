<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016 Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *		\file       htdocs/core/modules/import/import_csv.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with CSV format
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/import/modules_import.php';


/**
 *	Class to import Excel files
 */
class ImportXlsx extends ModeleImports
{
    var $db;
    var $datatoimport;

	var $error='';
	var $errors=array();

    var $id;           // Id of driver
	var $label;        // Label of driver
	var $extension;    // Extension of files imported by driver
	var $version;      // Version of driver

	var $label_lib;    // Label of external lib used by driver
	var $version_lib;  // Version of external lib used by driver

	var $separator;

    var $file;      // Path of file
	var $handle;    // Handle fichier

	var $cacheconvert=array();      // Array to cache list of value found after a convertion
	var $cachefieldtable=array();   // Array to cache list of value found into fields@tables
  
	var $workbook; // temporary import file
	var $record; // current record
	var $headers; 


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db				Database handler
	 *	@param	string		$datatoimport	String code describing import set (ex: 'societe_1')
	 */
	function __construct($db,$datatoimport)
	{
		global $conf,$langs;
		$this->db = $db;

		// this is used as an extension from the example file code, so we have to put xlsx here !!!
		$this->id='xlsx';                // Same value as xxx in file name export_xxx.modules.php
		$this->label='Excel 2007';             // Label of driver
		$this->desc=$langs->trans("Excel2007FormatDesc");
		$this->extension='xlsx';         // Extension for generated file by this driver
		$this->picto='mime/xls';		// Picto (This is not used by the example file code as Mime type, too bad ...)
		$this->version='1.0';         // Driver version

		// If driver use an external library, put its name here
        require_once PHPEXCEL_PATH.'PHPExcel.php';
		require_once PHPEXCEL_PATH.'PHPExcel/Style/Alignment.php';
        if (! class_exists('ZipArchive')) // For Excel2007, PHPExcel need ZipArchive
        {
                $langs->load("errors");
                $this->error=$langs->trans('ErrorPHPNeedModule','zip');
                return -1;
        }
        $this->label_lib='PhpExcel';
        $this->version_lib='1.8.0';

		$this->datatoimport=$datatoimport;
		if (preg_match('/^societe_/',$datatoimport)) $this->thirpartyobject=new Societe($this->db);
	}

	
	/**
	 * 	Output header of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string
	 */
	function write_header_example($outputlangs)
	{
	  global $user,$conf,$langs;
	  // create a temporary object, the final output will be generated in footer
          if (!empty($conf->global->MAIN_USE_FILECACHE_EXPORT_EXCEL_DIR)) {
            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
            $cacheSettings = array (
              'dir' => $conf->global->MAIN_USE_FILECACHE_EXPORT_EXCEL_DIR
          );
          PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        }

            $this->workbook = new PHPExcel();
            $this->workbook->getProperties()->setCreator($user->getFullName($outputlangs).' - Dolibarr '.DOL_VERSION);
            $this->workbook->getProperties()->setTitle($outputlangs->trans("Import").' - '.$file);
            $this->workbook->getProperties()->setSubject($outputlangs->trans("Import").' - '.$file);
            $this->workbook->getProperties()->setDescription($outputlangs->trans("Import").' - '.$file);

            $this->workbook->setActiveSheetIndex(0);
            $this->workbook->getActiveSheet()->setTitle($outputlangs->trans("Sheet"));
            $this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);

	    return '';
	}

	/**
	 * 	Output title line of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @param	array		$headerlinefields	Array of fields name
	 * 	@return	string
	 */
	function write_title_example($outputlangs,$headerlinefields)
	{
    global $conf;
    $this->workbook->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
    $this->workbook->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

    $col = 0;
    foreach($headerlinefields as $field) {
      $this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, 1, $outputlangs->transnoentities($field));
      // set autowidth
      //$this->workbook->getActiveSheet()->getColumnDimension($this->column2Letter($col + 1))->setAutoSize(true); 
      $col++;
    }
		return ''; // final output will be generated in footer
	}

	/**
	 * 	Output record of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@param	array		$contentlinevalues	Array of lines
	 * 	@return	string
	 */
	function write_record_example($outputlangs,$contentlinevalues)
	{
    $col = 0;
    $row = 2;
    foreach($contentlinevalues as $cell) {
        $this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $cell);
        $col++;
    }
    return ''; // final output will be generated in footer
	}

	/**
	 * 	Output footer of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string
	 */
	function write_footer_example($outputlangs)
	{
		// return te file content as a string
    $tempfile = tempnam(sys_get_temp_dir(), 'dol');
    $objWriter = new PHPExcel_Writer_Excel2007($this->workbook);
    $objWriter->save($tempfile);
    $this->workbook->disconnectWorksheets();
    unset($this->workbook);

    $content = file_get_contents($tempfile);
    unlink($tempfile);
    return $content;
	}



	/**
	 *	Open input file
	 *
	 *	@param	string	$file		Path of filename
	 *	@return	int					<0 if KO, >=0 if OK
	 */
	function import_open_file($file)
	{
		global $langs;
		$ret=1;

		dol_syslog(get_class($this)."::open_file file=".$file);

		$reader = new PHPExcel_Reader_Excel2007();
		$this->workbook = $reader->load($file);
		$this->record = 1;
		$this->file = $file;

		return $ret;
	}

	
	/**
	 * 	Return nb of records. File must be closed.
	 * 
	 *	@param	string	$file		Path of filename
	 * 	@return		int		<0 if KO, >=0 if OK
	 */
	function import_get_nb_of_lines($file)
	{
		$reader = new PHPExcel_Reader_Excel2007();
		$this->workbook = $reader->load($file);
	    
	    $rowcount = $this->workbook->getActiveSheet()->getHighestDataRow();
	    
	    $this->workbook->disconnectWorksheets();
	    unset($this->workbook);
	    
		return $rowcount;
    }
    

	/**
	 * 	Input header line from file
	 *
	 * 	@return		int		<0 if KO, >=0 if OK
	 */
	function import_read_header()
	{
		// This is not called by the import code !!!
		$this->headers = array();
		$colcount = PHPExcel_Cell::columnIndexFromString($this->workbook->getActiveSheet()->getHighestDataColumn());
		for($col=0;$col<$colcount;$col++) {
			$this->headers[$col] = $this->workbook->getActiveSheet()->getCellByColumnAndRow($col, 1)->getValue();
		}
		return 0;
	}


	/**
	 * 	Return array of next record in input file.
	 *
	 * 	@return		Array		Array of field values. Data are UTF8 encoded. [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=not empty string)
	 */
	function import_read_record()
	{
		global $conf;

		$rowcount = $this->workbook->getActiveSheet()->getHighestDataRow();
		if($this->record > $rowcount)
			return false;
		$array = array();
		$colcount = PHPExcel_Cell::columnIndexFromString($this->workbook->getActiveSheet()->getHighestDataColumn(0));
		for($col=0;$col<$colcount;$col++) {
			$val = $this->workbook->getActiveSheet()->getCellByColumnAndRow($col, $this->record)->getValue();
			$array[$col]['val'] = $val;
			$array[$col]['type'] = (dol_strlen($val)?1:-1); // If empty we consider it null
		}
		$this->record++;
		return $array;
	}

	/**
	 * 	Close file handle
	 *
	 *  @return	integer
	 */
	function import_close_file()
	{
		$this->workbook->disconnectWorksheets();
		unset($this->workbook);
	}


}
