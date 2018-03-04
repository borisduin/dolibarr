<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/modules/import/modules_import.php
 *	\ingroup    export
 *	\brief      File of parent class for import file readers
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *	Parent class for import file readers
 */
class ModeleImports
{
    public $db;
    public $datatoimport;

    public $error='';

    public $id;           // Id of driver
	public $label;        // Label of driver
	public $extension;    // Extension of files imported by driver
	public $version;      // Version of driver

	public $label_lib;    // Label of external lib used by driver
	public $version_lib;  // Version of external lib used by driver

	// Array of all drivers
	public $driverlabel=array();
	public $driverdesc=array();
	public $driverversion=array();

	public $liblabel=array();
	public $libversion=array();


	/**
     *  Constructor
	 */
	function __construct()
	{
	}

	
	/**
	 * getDriverId
	 *
	 * @return string		Id
	 */
	function getDriverId()
	{
	    return $this->id;
	}
	
	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label
	 */
	function getDriverLabel()
	{
	    return $this->label;
	}
	
	/**
	 *	getDriverDesc
	 *
	 *	@return string	Description
	 */
	function getDriverDesc()
	{
	    return $this->desc;
	}
	
	/**
	 * getDriverExtension
	 *
	 * @return string	Driver suffix
	 */
	function getDriverExtension()
	{
	    return $this->extension;
	}
	
	/**
	 *	getDriverVersion
	 *
	 *	@return string	Driver version
	 */
	function getDriverVersion()
	{
	    return $this->version;
	}
	
	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label of external lib
	 */
	function getLibLabel()
	{
	    return $this->label_lib;
	}
	
	/**
	 * getLibVersion
	 *
	 *	@return string	Version of external lib
	 */
	function getLibVersion()
	{
	    return $this->version_lib;
	}
	
	
	/**
	 *  Charge en memoire et renvoie la liste des modeles actifs
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	function liste_modeles($db,$maxfilenamelength=0)
	{
		dol_syslog(get_class($this)."::liste_modeles");

		$dir=DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers imports disponibles
		$var=True;
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (preg_match("/^import_(.*)\.modules\.php/i",$file,$reg))
    			{
    				$moduleid=$reg[1];

    				// Loading Class
    				$file = $dir."/import_".$moduleid.".modules.php";
    				$classname = "Import".ucfirst($moduleid);

    				require_once $file;
    				$module = new $classname($db,'');

    				// Picto
    				$this->picto[$module->id]=$module->picto;
    				// Driver properties
    				$this->driverlabel[$module->id]=$module->getDriverLabel('');
    				$this->driverdesc[$module->id]=$module->getDriverDesc('');
    				$this->driverversion[$module->id]=$module->getDriverVersion('');
    				// If use an external lib
    				$this->liblabel[$module->id]=$module->getLibLabel('');
    				$this->libversion[$module->id]=$module->getLibVersion('');

    				$i++;
    			}
    		}
        }

		return array_keys($this->driverlabel);
	}


	/**
	 *  Return picto of import driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getPictoForKey($key)
	{
		return $this->picto[$key];
	}

	/**
	 *  Renvoi libelle d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getDriverLabelForKey($key)
	{
		return $this->driverlabel[$key];
	}

	/**
	 *  Renvoi la description d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getDriverDescForKey($key)
	{
		return $this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getDriverVersionForKey($key)
	{
		return $this->driverversion[$key];
	}

	/**
	 *  Renvoi libelle de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getLibLabelForKey($key)
	{
		return $this->liblabel[$key];
	}

	/**
	 *  Renvoi version de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	function getLibVersionForKey($key)
	{
		return $this->libversion[$key];
	}

		/**
	 * Insert a record into database
	 *
	 * @param	array	$arrayrecord					Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param	array	$array_match_file_to_database	Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 * @param 	Object	$objimport						Object import (contains objimport->array_import_tables, objimport->array_import_fields, objimport->array_import_convertvalue, ...)
	 * @param	int		$maxfields						Max number of fields to use
	 * @param	string	$importid						Import key
	 * @param	array	$updatekeys						Array of keys to use to try to do update
	 * @return	int										<0 if KO, >0 if OK
	 */
	function import_insert($arrayrecord,$array_match_file_to_database,$objimport,$maxfields,$importid,$updatekeys)
	{
		global $langs,$conf,$user;
        global $thirdparty_static;    	// Specific to thirdparty import
		global $tablewithentity_cache;	// Cache to avoid to call  desc at each rows on tables

		$error=0;
		$this->errors=array();
		$this->warnings=array();

		//dol_syslog("import_csv.modules maxfields=".$maxfields." importid=".$importid);

		//var_dump($array_match_file_to_database);
		//var_dump($arrayrecord);
		$array_match_database_to_file=array_flip($array_match_file_to_database);
		$sort_array_match_file_to_database=$array_match_file_to_database;
		ksort($sort_array_match_file_to_database);

		//var_dump($sort_array_match_file_to_database);

		if (count($arrayrecord) == 0 || (count($arrayrecord) == 1 && empty($arrayrecord[0]['val'])))
		{
			//print 'W';
			$this->warnings[$warning]['lib']=$langs->trans('EmptyLine');
			$this->warnings[$warning]['type']='EMPTY';
		}
		else
		{
			$last_insert_id_array = array(); // store the last inserted auto_increment id for each table, so that dependent tables can be inserted with the appropriate id (eg: extrafields fk_object will be set with the last inserted object's id)
			$updatedone = false;
			$insertdone = false;
			// For each table to insert, me make a separate insert
			foreach($objimport->array_import_tables[0] as $alias => $tablename)
			{
				// Build sql request
				$sql='';
				$listfields=array();
				$listvalues=array();
				$i=0;
				$errorforthistable=0;

				// Define $tablewithentity_cache[$tablename] if not already defined
				if (! isset($tablewithentity_cache[$tablename]))	// keep this test with "isset"
				{
					dol_syslog("Check if table ".$tablename." has an entity field");
					$resql=$this->db->DDLDescTable($tablename,'entity');
					if ($resql)
					{
						$obj=$this->db->fetch_object($resql);
						if ($obj) $tablewithentity_cache[$tablename]=1;		// table contains entity field
						else $tablewithentity_cache[$tablename]=0;			// table does not contains entity field
					}
					else dol_print_error($this->db);
				}
				else
				{
					//dol_syslog("Table ".$tablename." check for entity into cache is ".$tablewithentity_cache[$tablename]);
				}


				// Loop on each fields in the match array: $key = 1..n, $val=alias of field (s.nom)
				foreach($sort_array_match_file_to_database as $key => $val)
				{
				    $fieldalias=preg_replace('/\..*$/i','',$val);
				    $fieldname=preg_replace('/^.*\./i','',$val);

				    if ($alias != $fieldalias) continue;    // Not a field of current table

					if ($key <= $maxfields)
					{
						// Set $newval with value to insert and set $listvalues with sql request part for insert
						$newval='';
						if ($arrayrecord[($key-1)]['type'] > 0) $newval=$arrayrecord[($key-1)]['val'];    // If type of field into input file is not empty string (so defined into input file), we get value

						// Make some tests on $newval

						// Is it a required field ?
						if (preg_match('/\*/',$objimport->array_import_fields[0][$val]) && ((string) $newval==''))
						{
							$this->errors[$error]['lib']=$langs->trans('ErrorMissingMandatoryValue',$key);
							$this->errors[$error]['type']='NOTNULL';
							$errorforthistable++;
							$error++;
						}
						// Test format only if field is not a missing mandatory field (field may be a value or empty but not mandatory)
						else
						{
						    // We convert field if required
						    if (! empty($objimport->array_import_convertvalue[0][$val]))
						    {
                                //print 'Must convert '.$newval.' with rule '.join(',',$objimport->array_import_convertvalue[0][$val]).'. ';
                                if ($objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromcodeid'
                                	|| $objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromref'
                                	|| $objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromcodeorlabel'
                                	)
                                {
                                    // New val can be an id or ref. If it start with id: it is forced to id, if it start with ref: it is forced to ref. It not, we try to guess.
                                    $isidorref='id';
                                    if (! is_numeric($newval) && $newval != '' && ! preg_match('/^id:/i',$newval)) $isidorref='ref';
                                    $newval=preg_replace('/^(id|ref):/i','',$newval);    // Remove id: or ref: that was used to force if field is id or ref
                                    //print 'Val is now '.$newval.' and is type '.$isidorref."<br>\n";
                                    
                                    if ($isidorref == 'ref')    // If value into input import file is a ref, we apply the function defined into descriptor
                                    {
                                        $file=(empty($objimport->array_import_convertvalue[0][$val]['classfile'])?$objimport->array_import_convertvalue[0][$val]['file']:$objimport->array_import_convertvalue[0][$val]['classfile']);
                                        $class=$objimport->array_import_convertvalue[0][$val]['class'];
                                        $method=$objimport->array_import_convertvalue[0][$val]['method'];
                                        if ($this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval] != '')
                                        {
                                        	$newval=$this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval];
                                        }
                                        else
										{
                                            $resultload = dol_include_once($file);
                                            if (empty($resultload))
                                            {
                                                dol_print_error('', 'Error trying to call file='.$file.', class='.$class.', method='.$method);
                                                break;
                                            }
                                            $classinstance=new $class($this->db);
                                            // Try the fetch from code or ref
                                            call_user_func_array(array($classinstance, $method),array('', $newval));
                                            // If not found, try the fetch from label
                                            if (! ($classinstance->id != '') && $objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromcodeorlabel')
                                            {
												call_user_func_array(array($classinstance, $method),array('', '', $newval));
                                            }
                                            $this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval]=$classinstance->id;
                                            //print 'We have made a '.$class.'->'.$method.' to get id from code '.$newval.'. ';
                                            if ($classinstance->id != '')	// id may be 0, it is a found value
                                            {
                                                $newval=$classinstance->id;
                                            }
                                            else
                                            {
                                                if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) $this->errors[$error]['lib']=$langs->trans('ErrorFieldValueNotIn',$key,$newval,'code',$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
                                                else if (!empty($objimport->array_import_convertvalue[0][$val]['element'])) $this->errors[$error]['lib']=$langs->trans('ErrorFieldRefNotIn',$key,$newval,$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['element']));
                                                else $this->errors[$error]['lib']='ErrorFieldValueNotIn';
                                                $this->errors[$error]['type']='FOREIGNKEY';
                                                $errorforthistable++;
                                                $error++;
                                            }
                                        }
                                    }

                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='zeroifnull')
                                {
                                    if (empty($newval)) $newval='0';
                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='getcustomercodeifauto')
                                {
                                    if (strtolower($newval) == 'auto')
                                    {
                                        $this->thirpartyobject->get_codeclient(0,0);
                                        $newval=$this->thirpartyobject->code_client;
                                        //print 'code_client='.$newval;
                                    }
                                    if (empty($newval)) $arrayrecord[($key-1)]['type']=-1;	// If we get empty value, we will use "null"
                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='getsuppliercodeifauto')
                                {
                                    if (strtolower($newval) == 'auto')
                                    {
                                        $newval=$this->thirpartyobject->get_codefournisseur(0,1);
                                        $newval=$this->thirpartyobject->code_fournisseur;
                                        //print 'code_fournisseur='.$newval;
                                    }
                                    if (empty($newval)) $arrayrecord[($key-1)]['type']=-1;	// If we get empty value, we will use "null"
                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='getcustomeraccountancycodeifauto')
                                {
                                    if (strtolower($newval) == 'auto')
                                    {
                                        $this->thirpartyobject->get_codecompta('customer');
                                        $newval=$this->thirpartyobject->code_compta;
                                        //print 'code_compta='.$newval;
                                    }
                                    if (empty($newval)) $arrayrecord[($key-1)]['type']=-1;	// If we get empty value, we will use "null"
                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='getsupplieraccountancycodeifauto')
                                {
                                    if (strtolower($newval) == 'auto')
                                    {
                                        $this->thirpartyobject->get_codecompta('supplier');
                                        $newval=$this->thirpartyobject->code_compta_fournisseur;
                                        if (empty($newval)) $arrayrecord[($key-1)]['type']=-1;	// If we get empty value, we will use "null"
                                        //print 'code_compta_fournisseur='.$newval;
                                    }
                                    if (empty($newval)) $arrayrecord[($key-1)]['type']=-1;	// If we get empty value, we will use "null"
                                }
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='getrefifauto')
                                {
                                    $defaultref='';
                                    // TODO provide the $modTask (module of generation of ref) as parameter of import_insert function
                                    $obj = empty($conf->global->PROJECT_TASK_ADDON)?'mod_task_simple':$conf->global->PROJECT_TASK_ADDON;
                                    if (! empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php"))
                                    {
                                        require_once DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
                                        $modTask = new $obj;
                                        $defaultref = $modTask->getNextValue(null,null);
                                    }
                                    if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';
                                    $newval=$defaultref;
                                }                                
                                
                                
                                elseif ($objimport->array_import_convertvalue[0][$val]['rule']=='numeric')
                                {
                                    $newval = price2num($newval);
                                }

                                //print 'Val to use as insert is '.$newval.'<br>';
						    }

						    // Test regexp
							if (! empty($objimport->array_import_regex[0][$val]) && ($newval != ''))
							{
								// If test is "Must exist in a field@table"
								if (preg_match('/^(.*)@(.*)$/',$objimport->array_import_regex[0][$val],$reg))
								{
									$field=$reg[1];
									$table=$reg[2];

									// Load content of field@table into cache array
									if (! is_array($this->cachefieldtable[$field.'@'.$table])) // If content of field@table not already loaded into cache
									{
										$sql="SELECT ".$field." as aliasfield FROM ".$table;
										$resql=$this->db->query($sql);
										if ($resql)
										{
											$num=$this->db->num_rows($resql);
											$i=0;
											while ($i < $num)
											{
												$obj=$this->db->fetch_object($resql);
												if ($obj) $this->cachefieldtable[$field.'@'.$table][]=$obj->aliasfield;
												$i++;
											}
										}
										else
										{
											dol_print_error($this->db);
										}
									}

									// Now we check cache is not empty (should not) and key is into cache
									if (! is_array($this->cachefieldtable[$field.'@'.$table]) || ! in_array($newval,$this->cachefieldtable[$field.'@'.$table]))
									{
										$this->errors[$error]['lib']=$langs->transnoentitiesnoconv('ErrorFieldValueNotIn',$key,$newval,$field,$table);
										$this->errors[$error]['type']='FOREIGNKEY';
									    $errorforthistable++;
										$error++;
									}
								}
								// If test is just a static regex
								else if (! preg_match('/'.$objimport->array_import_regex[0][$val].'/i',$newval))
								{
								    //if ($key == 19) print "xxx".$newval."zzz".$objimport->array_import_regex[0][$val]."<br>";
									$this->errors[$error]['lib']=$langs->transnoentitiesnoconv('ErrorWrongValueForField',$key,$newval,$objimport->array_import_regex[0][$val]);
									$this->errors[$error]['type']='REGEX';
									$errorforthistable++;
									$error++;
								}
							}

							// Other tests
							// ...
						}

						// Define $listfields and $listvalues to build SQL request
						$listfields[] = $fieldname;

						// Note: arrayrecord (and 'type') is filled with ->import_read_record called by import.php page before calling import_insert
						if (empty($newval) && $arrayrecord[($key-1)]['type'] < 0)		 $listvalues[] = ($newval=='0'?$newval:"null");
						elseif (empty($newval) && $arrayrecord[($key-1)]['type'] == 0)	 $listvalues[] = "''";
						else															 $listvalues[] = "'".$this->db->escape($newval)."'";
					}
					$i++;
				}

				// We add hidden fields (but only if there is at least one field to add into table)
				if (!empty($listfields) && is_array($objimport->array_import_fieldshidden[0]))
				{
    				// Loop on each hidden fields to add them into listfields/listvalues
				    foreach($objimport->array_import_fieldshidden[0] as $key => $val)
    				{
    				    if (! preg_match('/^'.preg_quote($alias).'\./', $key)) continue;    // Not a field of current table
    				    if ($val == 'user->id')
    				    {
    				        $listfields[] = preg_replace('/^'.preg_quote($alias).'\./','',$key);
    				        $listvalues[] = $user->id;
    				    }
    				    elseif (preg_match('/^lastrowid-/',$val))
    				    {
    				        $tmp=explode('-',$val);
    				        $lastinsertid=(isset($last_insert_id_array[$tmp[1]]))?$last_insert_id_array[$tmp[1]]:0;
							$keyfield = preg_replace('/^'.preg_quote($alias).'\./','',$key);
    				        $listfields[] = $keyfield;
                            $listvalues[] = $lastinsertid;
    				        //print $key."-".$val."-".$listfields."-".$listvalues."<br>";exit;
    				    }
    				}
				}
				//print 'listfields='.$listfields.'<br>listvalues='.$listvalues.'<br>';

				// If no error for this $alias/$tablename, we have a complete $listfields and $listvalues that are defined
				if (! $errorforthistable)
				{
					//print "$alias/$tablename/$listfields/$listvalues<br>";
					if (!empty($listfields))
					{
						$updatedone = false;
						$insertdone = false;
						if (!empty($updatekeys)) {
							// We do SELECT to get the rowid, if we already have the rowid, it's to be used below for related tables (extrafields)
							if (empty($lastinsertid)) {
								$sqlSelect = 'SELECT rowid FROM '.$tablename;
								
								$data = array_combine($listfields, $listvalues);
								$where = array();
								$filters = array();
								foreach ($updatekeys as $key) {
									$col = $objimport->array_import_updatekeys[0][$key];
									$key=preg_replace('/^.*\./i','',$key);
									$where[] = $key.' = '.$data[$key];
									$filters[] = $col.' = '.$data[$key];
								}
								$sqlSelect.= ' WHERE '.implode(' AND ', $where);
								
								$resql=$this->db->query($sqlSelect);
								if($resql) {
									$res = $this->db->fetch_object($resql);
									if($resql->num_rows == 1) {
										$lastinsertid = $res->rowid;
										$last_insert_id_array[$tablename] = $lastinsertid;
									} else if($resql->num_rows > 1) {
										$this->errors[$error]['lib']=$langs->trans('MultipleRecordFoundWithTheseFilters', implode($filters, ', '));
										$this->errors[$error]['type']='SQL';
										$error++;
									} else {
										// No record found with filters, insert will be tried below
									}
								}
								else
								{
									//print 'E';
									$this->errors[$error]['lib']=$this->db->lasterror();
									$this->errors[$error]['type']='SQL';
									$error++;
								}
							}
							
							if (!empty($lastinsertid)) {
								// Build SQL UPDATE request
								$sqlstart = 'UPDATE '.$tablename;
								
								$data = array_combine($listfields, $listvalues);
								$set = array();
								foreach ($data as $key => $val) {
									$set[] = $key.' = '.$val;
								}
								$sqlstart.= ' SET '.implode(', ', $set);
								
								if(empty($keyfield)) $keyfield = 'rowid';
								$sqlend = ' WHERE '.$keyfield.' = '.$lastinsertid;
								
								$sql = $sqlstart.$sqlend;
								
								// Run update request
								$resql=$this->db->query($sql);
								if($resql) {
									// No error, update has been done. $this->db->db->affected_rows can be 0 if data hasn't changed
									$updatedone = true;
								}
								else
								{
									//print 'E';
									$this->errors[$error]['lib']=$this->db->lasterror();
									$this->errors[$error]['type']='SQL';
									$error++;
								}
							}
						}

						// Update not done, we do insert
						if (!$error && !$updatedone) {
							// Build SQL INSERT request
							$sqlstart = 'INSERT INTO '.$tablename.'('.implode(', ', $listfields).', import_key';
							$sqlend = ') VALUES('.implode(', ', $listvalues).", '".$importid."'";
							if (! empty($tablewithentity_cache[$tablename])) {
								$sqlstart.= ', entity';
								$sqlend.= ', '.$conf->entity;
							} 
							if (! empty($objimport->array_import_tables_creator[0][$alias])) {
								$sqlstart.= ', '.$objimport->array_import_tables_creator[0][$alias];
								$sqlend.=', '.$user->id;
							}
							$sql = $sqlstart.$sqlend.')';
							dol_syslog("import_csv.modules", LOG_DEBUG);
							
							// Run insert request
							if ($sql)
							{
								$resql=$this->db->query($sql);
								if ($resql)
								{
								    $last_insert_id_array[$tablename] = $this->db->last_insert_id($tablename); // store the last inserted auto_increment id for each table, so that child tables can be inserted with the appropriate id. This must be done just after the INSERT request, else we risk losing the id (because another sql query will be issued somewhere in Dolibarr).
								    $insertdone = true;
								}
								else
								{
									//print 'E';
									$this->errors[$error]['lib']=$this->db->lasterror();
									$this->errors[$error]['type']='SQL';
									$error++;
								}
							}
						}
					}
					/*else
					{
						dol_print_error('','ErrorFieldListEmptyFor '.$alias."/".$tablename);
					}*/
				}

			    if ($error) break;
			}

			if($updatedone) $this->nbupdate++;
			if($insertdone) $this->nbinsert++;
		}

		return 1;
	}

}

