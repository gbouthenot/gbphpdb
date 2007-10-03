<?php


Class Gb_Form
{

	protected $formElements=array();
	protected $where;
	protected $tableName;
	protected static $fPostIndicator=false;

  protected $_commonRegex = array(
		'HexColor'        => '/^(#?([\dA-F]{3}){1,2})$/i',
		'UsTelephone'     => '/^(\(?([2-9]\d{2})\)?[\.\s-]?([2-4|6-9]\d\d|5([0-4|6-9]\d|\d[0-4|6-9]))[\.\s-]?(\d{4}))$/',
		'Email'           => '/((^[\w\.!#$%"*+\/=?`{}|~^-]+)@(([-\w]+\.)+[A-Za-z]{2,}))$/',
		'Url'             => '/^((https?|ftp):\/\/([-\w]+\.)+[A-Za-z]{2,}(:\d+)?([\\\\\/]\S+)*?[\\\\\/]?(\?\S*)?)$/i',
		'PositiveInteger' => '/^(\d+)$/',
		'RelativeInteger' => '/^(-?\d+)$/',
		'DecimalNumber'   => '/^(-?(\d*\.)?\d+$)/',
		'AlphaNumeric'    => '/^([\w\s]+)$/i',
		'PostalCodeFr'    => '/^([0-9]{5})$/',
		'Year'            => '/^(((19)|(20))[0-9]{2})$/', // aaaa 1900<=aaaa<=2099
		'Year20xx'        => '/^(20[0-9]{2})$/',          // aaaa 2000<=aaaa<=2099
		'DateFr'          => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/(((19)|(20))[0-9]{2})$/',	// jj/mm/aaaa   \1:jj \2:mm \3:aaaa   1900<=aaaa<=2099
		'DateFr20xx'      => '/^(((0[1-9])|[1|2][0-9])|(30|31))\/((0[1-9])|10|11|12)\/($20[0-9]{2})/',          // jj/mm/aaaa   \1:jj \2:mm \3:aaaa   2000<=aaaa<=2099
	);


	public function __construct($tableName="", array $where=array())
	{
		$this->tableName=$tableName;
		$this->where=$where;
	}

	/**
	 * Ajoute un élément
	 *
	 * @param string $nom Nom unique, défini le NAME de l'élément doit commencer par une lettre et ne comporter que des caractères alphanumériques
	 * @param array $aParams
	 * @throws Gb_Exception
	 */
	public function addElement($nom, array $aParams)
	{
		// $aParams["type"]="SELECT":
		// $aParams["args"]        : array["default"]=array(value=>libelle) (value est recodé dans le html mais renvoie la bonne valeur)
		// $aParams["dbCol"]       : optionnel: nom de la colonne
		// $aParams["fMandatory"]  : doit être rempli ? défaut: false
		// $aParams["classOK"]     : nom de la classe pour élément valide défaut: GBFORM_OK
		// $aParams["classNOK"]    : nom de la classe pour élément non valide défaut: GBFORM_NOK
		// $aParams["classERROR"]  : nom de la classe pour erreur: GBFORM_ERROR
		// $aParams["preInput"]    :
		// $aParams["inInput"]     :
		// $aParams["postInput"]   :
		// renseignés automatiquement:
		// $aParams["class"]       : nom de la classe en cours
		// $aParams["errorMsg"]    : message d'erreur

		if (!preg_match("/^[a-zA-Z][a-zA-Z0-9]*/", $nom))
			throw new Gb_Exception("Nom de variable de formulaire invalide");

		if (isset($this->formElement[$nom]))
			throw new Gb_Exception("Nom de variable de formulaire déjà défini");

		if (!isset($aParams["type"]))
			throw new Gb_Exception("Type de variable de formulaire non précisé");

		if (!isset($aParams["fMandatory"]))
			$aParams["fMandatory"]=false;
		if (!isset($aParams["preInput"]))
			$aParams["preInput"]="";
		if (!isset($aParams["inInput"]))
			$aParams["inInput"]="";
		if (!isset($aParams["postInput"]))
			$aParams["postInput"]="";
		if (!isset($aParams["classOK"]))
			$aParams["classOK"]="GBFORM_OK";
		if (!isset($aParams["classNOK"]))
			$aParams["classNOK"]="GBFORM_NOK";
		if (!isset($aParams["classERROR"]))
			$aParams["classERROR"]="GBFORM_ERROR";
		if (!isset($aParams["class"]))
			$aParams["class"]=$aParams["classOK"];
		$aParams["errorMsg"]="";

		$type=$aParams["type"];
		switch($type)
		{
			case "SELECT":
				if (!isset($aParams["args"]) || !is_array($aParams["args"]))
					throw new Gb_Exception("Paramètres de $nom incorrects");
				//remplit value avec le numéro sélectionné.
				$num=0;
				foreach($aParams["args"] as $ordre=>$val) {
					if ($ordre==="default") {
						unset($aParams["args"]["default"]);
						$aParams["args"][$num]=$val;
						$aParams["value"]=$num;
					}
					$num++;
				}
				if (!isset($aParams["value"]))
					$aParams["value"]="0";		// par défaut, 1er élément de la liste
				$this->formElements[$nom]=$aParams;
				break;

			case "TEXT":
				if (isset($aParams["args"]["regexp"])){
					$regexp=&$aParams["args"]["regexp"];
					if (isset($this->_commonRegex[$regexp])) {
						//regexp connu: remplace par le contenu
						$regexp=$this->_commonRegex[$regexp];
					}
				}
				if (!isset($aParams["value"]))
					$aParams["value"]="";		// par défaut, chaine vide
				$this->formElements[$nom]=$aParams;
				break;

			case "CHECKBOX":
				if (isset($aParams["value"]) && $aParams["value"]==true)
					$aParams["value"]=true;
				else
					$aParams["value"]=false;
				$this->formElements[$nom]=$aParams;
				break;

			case "RADIO":
				if (!isset($aParams["value"]))
					$aParams["value"]="";		// par défaut, chaine vide
				$this->formElements[$nom]=$aParams;
				break;

				default:
				throw new Gb_Exception("Type de variable de formulaire inconnu pour $nom");
		}

	}



	public function set($nom, $value)
	{
		if (!isset($this->formElements[$nom]))
			throw new Gb_Exception("Set impossible: nom=$nom non défini");

		$type=$this->formElements[$nom]["type"];
		if ($type=="SELECT") {
			foreach ($this->formElements[$nom]["args"] as $ordre=>$val) {
				if ($val[0]==$value) {
					$value=$ordre;
					break;
				}
			}
		}
		$this->formElements[$nom]["value"]=$value;
	}


	public function get($nom)
	{
		if (!isset($this->formElements[$nom]))
			throw new Gb_Exception("Set impossible: nom=$nom non défini");

		$value=$this->formElements[$nom]["value"];

		if ($this->formElements[$nom]["type"]=="SELECT" && isset($this->formElements[$nom]["args"][$value])) {
			$value=$this->formElements[$nom]["args"][$value][0];
		}
		return $value;
	}

	/**
	 * Renvoit le code HTML approprié (valeur par défaut, préselectionné, etc)
	 *
	 * @param string[optional] $nom
	 * @param string[optional] $radioValue
	 * @throws Gb_Exception
	 */
	public function getHtml($nom="", $radioValue="")
	{
		$ret="";

		// si nom vide, rappelle la fonction pour tous les éléments
		if ($nom==="") {
			foreach ($this->formElements as $nom=>$aElement) {
				$ret.=$this->getHtml($nom);
			}
			return $ret;
		}

		if (!isset($this->formElements[$nom])) {
			throw new Gb_Exception("Variable de formulaire inexistante");
		}

		if (self::$fPostIndicator==false) {
			// positionnement de la variable statique indiquand que l'indicateur a été mis.
			$ret.="<input type='hidden' name='GBFORMPOST' value='true' />\n";
			self::$fPostIndicator=true;
		}

		$aElement=$this->formElements[$nom];
		$class=$aElement["class"];

		$type=$aElement["type"];
		$value=$aElement["value"];
		switch ($type) {
			case "SELECT":
				$ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
				$ret.=$aElement["preInput"];
				$aValues=$aElement["args"];
				$html=$aElement["inInput"];
				$ret.="<select id='GBFORM_$nom' name='GBFORM_$nom' $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();'>\n";
				$num=0;
				foreach ($aValues as $ordre=>$aOption){
					$sVal=htmlspecialchars($aOption[0], ENT_QUOTES);
					$sLib=htmlspecialchars($aOption[1], ENT_QUOTES);
					$sSelected="";
					if ($ordre==$value)
						$sSelected="selected='selected'";
					$ret.="<option value='$num' $sSelected>$sLib</option>\n";
					$num++;
				}
				$ret.="</select>\n";
				break;

			case "TEXT": case "PASSWORD":
				$ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
				$ret.=$aElement["preInput"];
				$html=$aElement["inInput"];
				$sValue=htmlspecialchars($value, ENT_QUOTES);
				$ret.="<input type='text' class='text' id='GBFORM_$nom' name='GBFORM_$nom' $html value='$sValue' onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			case "CHECKBOX":
				$ret.="<div id='GBFORM_${nom}_div' class='$class'>\n";
				$ret.=$aElement["preInput"];
				$sValue="";
				if ($value==true)
					$sValue=" checked='checked'";
				$html=$aElement["inInput"];
				$ret.="<input type='checkbox' class='checkbox' id='GBFORM_$nom' name='GBFORM_$nom' value='true' $sValue $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			case "RADIO":
				$ret.="<div class='$class'>\n";
				$ret.=$aElement["preInput"];
				$sValue="";
				if ($value==$radioValue)
					$sValue=" checked='checked'";
				$html=$aElement["inInput"];
				$ret.="<input type='radio' class='radio' name='GBFORM_$nom' value='$radioValue' $sValue $html onchange='javascript:validate_GBFORM_$nom();' onkeyup='javascript:validate_GBFORM_$nom();' />\n";
				break;

			default:
				throw new Gb_Exception("Type inconnu");
		}
		$errorMsg=$aElement["errorMsg"];
		if (strlen($errorMsg)) {
			$class=$aElement["classERROR"];
			$ret.="<div class='$class'>$errorMsg</div>";
		}
		$ret.=$aElement["postInput"];
		$ret.="</div>\n";

		return $ret;
	}


	/**
	 * Change la classe d'un elément
	 *
	 * @param string $nom
	 * @param boolean|string $class : false/true: met a classNOK/classOK string: met a la classe spécifiée
	 * @throws Gb_Exception
	 */
	public function setClass($nom, $class=false)
	{
		if (!isset($this->formElements[$nom]))
			throw new Gb_Exception("Element de fomulaire non défini");
		if ($class===false) {
			$class=$this->formElements[$nom]["classNOK"];
		} elseif ($class===true) {
			$class=$this->formElements[$nom]["classOK"];
		}

		$this->formElements[$nom]["class"]=$class;
	}



	/**
	 * Change le message d'erreur d'un elément
	 *
	 * @param string $nom
	 * @param string[optional] $errorMsg
	 * @throws Gb_Exception
	 */
	public function setErrorMsg($nom, $errorMsg="")
	{
		if (!isset($this->formElements[$nom]))
			throw new Gb_Exception("Element de fomulaire non défini");
		$this->formElements[$nom]["errorMsg"]=$errorMsg;
	}


	/**
	 * Renvoie le code javascript pour la validation dynamique
	 *
	 * @param string[optinal] $nom élément à récupérér ou vide pour tous
	 * @return string
	 */
	public function getJavascript($nom="")
	{
		$ret="";

		// si nom vide, rappelle la fonction pour tous les éléments
		if ($nom==="") {
			foreach ($this->formElements as $nom=>$aElement) {
				$ret.=$this->getJavascript($nom);
			}
				return $ret;
		}

		if (!isset($this->formElements[$nom])) {
			throw new Gb_Exception("Variable de formulaire inexistante");
		}
		$aElement=$this->formElements[$nom];

		$type=$aElement["type"];
		switch ($type) {
			case "SELECT":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				// attention utilise prototype String.strip()
				$ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
				if ($aElement["fMandatory"]) {
					$aValues="";
					foreach($aElement["args"] as $ordre=>$val) {
						$val=$val[0];
						if ($val===false) $val="false";
						$aValues[]="'$ordre':'$val'";
					}
					$ret.="var GBFORM_{$nom}_values = { ".implode(", ",$aValues)."};\n";
					$ret.="if ((GBFORM_{$nom}_values[value])=='false') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				break;

			case "TEXT": case "PASSWORD":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				// attention utilise prototype String.strip()
				$ret.="var value=remove_accents(\$F('GBFORM_$nom').strip());\n";
				if ($aElement["fMandatory"]) {
					$ret.="if (value=='') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				if (isset($aElement["args"]["regexp"])){
					$regexp=$aElement["args"]["regexp"];
					if (isset($this->_commonRegex[$regexp])) {
						//regexp connu: remplace par le contenu
						$regexp=$this->_commonRegex[$regexp];
					}
					$ret.="var regexp=$regexp\n";
					$ret.="if (!regexp.test(value)) {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				if (isset($aElement["MINVALUE"])){
					$aMinValues=$aElement["MINVALUE"];
					foreach ($aMinValues as $borne) {
						$ret.=" var bornevalue=value;\n";
						if (is_array($borne) && isset($aElement["args"]["regexp"])) {
							// si array, alors extrait la valeur du regexp avant de comparer
							$ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
							$borne=$borne[1];
						}
						else {
							$ret.=" var bornevalue=value;\n";
						}
						if (strpos($borne, "GBFORM_")===0) {
							// borne commence par GBFORM_
							$borne="\$F('$borne')";
						}
						$ret.=" var borne=eval({$borne});\n";
						$ret.=" if (bornevalue < borne) {";
						$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
						$ret.="}\n";
					}
				}
				if (isset($aElement["MAXVALUE"])){
					$aMaxValues=$aElement["MAXVALUE"];
					foreach ($aMaxValues as $borne) {
						$ret.=" var bornevalue=value;\n";
						if (is_array($borne) && isset($aElement["args"]["regexp"])) {
							// si array, alors extrait la valeur du regexp avant de comparer
							$ret.=" var bornevalue=value.replace({$aElement["args"]["regexp"]}, \"{$borne[0]}\");\n";
							$borne=$borne[1];
						}
						else {
							$ret.=" var bornevalue=value;\n";
						}
						if (strpos($borne, "GBFORM_")===0) {
							// borne commence par GBFORM_
							$borne="\$F('$borne')";
						}
						$ret.=" var borne=eval({$borne});\n";
						$ret.=" if (bornevalue > borne) {";
						$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';";
						$ret.="}\n";
					}
				}
				break;

			case "CHECKBOX":
				$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classOK"]}';\n";
				if ($aElement["fMandatory"]) {
					$ret.="var value=\$F('GBFORM_$nom');\n";
					$ret.="if (value!='true') {\n";
					$ret.="	\$('GBFORM_{$nom}_div').className='{$aElement["classNOK"]}';\n";
					$ret.="}\n";
				}
				break;

			case "RADIO":
				break;

			default:
				throw new Gb_Exception("Type inconnu");
		}

		if (strlen($ret)) {
			$ret2="function validate_GBFORM_$nom()\n";
			$ret2.="{\n";
			$ret2.=$ret;
			$ret2.="}\n";
			return $ret2;
		}

		return "";
	}

	/**
	 * Remplit les valeurs depuis la base de données
	 *
	 * @param Gb_Db $db
	 * @return boolean true si données trouvées
	 */
	public function getFromDb(Gb_Db $db)
	{
		//todo: checkbox
		// obient le nom des colonnes
		$aCols=array();
		foreach ($this->formElements as $nom=>$aElement) {
			if (isset($aElement["dbCol"])) {
				$aCols[$nom]=$aElement["dbCol"];
			}
		}

		if (count($aCols)==0) {
			return false;
		}

		$sql="SELECT ".implode(", ", $aCols)." FROM ".$this->tableName;
		if (count($this->where)) {
			$sql.=" WHERE";
			$sWhere="";
			foreach ($this->where as $w)
			{ if (strlen($sWhere)) {
					$sWhere.=" AND";
				}
				$sWhere.=" $w";
			}
			$sql.=$sWhere;
		}

		$aLigne=$db->retrieve_one($sql);
		if ($aLigne===false) {
		// La requête n'a pas renvoyé de ligne
			return false;
		}

		// La requête a renvoyé une ligne
		foreach ($aCols as $nom=>$dbcol) {
			$this->set($nom, $aLigne[$dbcol]);
		}
		return true;
	}


	/**
	 * Insère/update les valeurs dans la bdd
	 *
	 * @param Gb_Db $db
	 * @param array $moreData
	 * @return boolean true si données ecrites
	 */
	public function putInDb(Gb_Db $db, array $moreData=array())
	{
		//todo: checkbox
		// obient le nom des colonnes
		$aCols=$moreData;
		foreach ($this->formElements as $nom=>$aElement) {
			if (isset($aElement["dbCol"])) {
				$col=$aElement["dbCol"];
				$val=$this->get($nom);
				$aCols[$col]=$val;
			}
		}

		if (count($aCols)==0) {
			return false;
		}

		$nb=$db->replace($this->tableName, $aCols, $this->where);
		if ($nb) {
			GbUtil::Log(GbUtil::LOG_INFO, "GBFORM->putInDb OK table:{$this->tableName} where:".GbUtil::Dump($this->where)."" );
			return true;
		}
		else {
			GbUtil::Log(GbUtil::LOG_ERROR, "GBFORM->putInDb Erreur: replace impossible ! table:{$this->tableName} where:".GbUtil::Dump($this->where)." data:".GbUtil::Dump($aCols) );
			return false;
		}
	}



	/**
	 * Remplit les valeurs depuis $_POST
	 * @return true si données trouvées
	 */
	public function getFromPost()
	{
		$fPost=false;
		if (isset($_POST["GBFORMPOST"])) {
			// detecte que le formulaire a été soumis. Utile pour les checkbox
			$fPost=true;
		}
		foreach ($this->formElements as $nom=>$aElement) {
			$type=$aElement["type"];
			if ($fPost && $type=="CHECKBOX") {
				// met les checkbox à false
				$this->formElements[$nom]["value"]=false;
			}
			if (isset($_POST["GBFORM_".$nom])) {
					$this->formElements[$nom]["value"]=$_POST["GBFORM_".$nom];
					$fPost=true;
			}
		}
		return $fPost;
	}


	/**
	 * Valide le formulaire
	 * En cas d'erreur, appelle $this->setClass() et $this->setErrorMsg pour chaque $nom incorrect
	 *
	 * @return array("nom" => "erreur") ou true si aucune erreur (attention utiliser ===)
	 */
	public function validate()
	{
		$aErrs=array();
		foreach ($this->formElements as $nom=>$aElement) {
			$type=$aElement["type"];
			$value=strtolower(GbUtil::mystrtoupper(trim($aElement["value"])));

			switch ($type) {
				case "SELECT":
					// Vérifie que la valeur est bien dans la liste et maj $value
					if (isset($aElement["args"][$value])) {
						$value=$aElement["args"][$value][0];
					} else {
						$aErrs[$nom]="Choix invalide";
						continue;
					}

				break;
				case "TEXT": case "PASSWORD":
					if (strlen($value) && isset($aElement["args"]["regexp"])) {
						$regexp=$aElement["args"]["regexp"];
						if (!preg_match($regexp, $value)) {
							$aErrs[$nom]="Valeur incorrecte";
							continue;
						}
					}
					if (strlen($value) && isset($aElement["MINVALUE"])) {
						$aBornes=$aElement["MINVALUE"];
						foreach ($aBornes as $borne) {
							$bornevalue=$value;
							if (is_array($borne) && isset($aElement["args"]["regexp"])) {
								// si array, alors extrait la valeur du regexp avant de comparer
								$bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
								$borne=$borne[1];
							}
							$sBorne=$borne;
							if (strpos($borne, "GBFORM_")===0) {
								// borne commence par GBFORM_
								$sBorne=substr($borne,7);
								$borne=$this->get($sBorne);
								$sBorne.=" ($borne)";
							}
							if ($bornevalue < $borne) {
								$aErrs[$nom]="Doit être supérieur ou égal à $sBorne";
								continue;
							}
						}
					}
					if (strlen($value) && isset($aElement["MAXVALUE"])) {
						$aBornes=$aElement["MAXVALUE"];
						foreach ($aBornes as $borne) {
							$bornevalue=$value;
							if (is_array($borne) && isset($aElement["args"]["regexp"])) {
								// si array, alors extrait la valeur du regexp avant de comparer
								$bornevalue=preg_replace( $aElement["args"]["regexp"],$borne[0], $value);
								$borne=$borne[1];
							}
							$sBorne=$borne;
							if (strpos($borne, "GBFORM_")===0) {
								// borne commence par GBFORM_
								$sBorne=substr($borne,7);
								$borne=$this->get($sBorne);
								$sBorne.=" ($borne)";
							}
							if ($bornevalue > $borne) {
								$aErrs[$nom]="Doit être inférieur ou égal à $sBorne";
								continue;
							}
						}
					}


			}

			if ($aElement["fMandatory"]) {
				// Vérifie que le champ et bien rempli
				if ( ($type=="SELECT" && $value===false) || ($type!="SELECT" && strlen($value)==0) ) {
					if ($type=="SELECT")	$aErrs[$nom]="Aucun choix sélectionné";
					elseif ($type=="TEXT")	$aErrs[$nom]="Valeur non renseignée";
					elseif ($type=="CHECKBOX")	$aErrs[$nom]="Case non cochée";
					elseif ($type=="RADIO")	$aErrs[$nom]="?";
					elseif ($type=="PASSWORD")	$aErrs[$nom]="Mot de passe vide";
					else	$aErrs[$nom]="Champ non renseigné";
					continue;
				}
			}
		}//foreach

		foreach($aErrs as $nom=>$reason)
		{
			$this->setClass($nom, false);
			$this->setErrorMsg($nom, $reason);
		}

		if (count($aErrs)==0)
			return true;
		else
			return $aErrs;
	}


}
