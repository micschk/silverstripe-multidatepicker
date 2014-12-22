<?php

class MultiDateField extends DateField {
	
	/**
	 * @var $separator Determines on which character to split tags in a string.
	 */
	protected $separator = ' ';
	
	protected static $separator_to_regex = array(
		' ' => '\s',
		',' => '\,', 
	);
	
	public function FieldHolder($properties = array()) {
		if ($this->getConfig('showcalendar')) {
			// TODO Replace with properly extensible view helper system 
			$d = MultiDateField_View_JQuery::create($this); 
			if(!$d->regionalSettingsExist()) {
				$dateformat = $this->getConfig('dateformat');

				// if no localefile is present, the jQuery DatePicker 
				// month- and daynames will default to English, so the date
				// will not pass Zend validatiobn. We provide a fallback  
				if (preg_match('/(MMM+)|(EEE+)/', $dateformat)) {
					$this->setConfig('dateformat', $this->getConfig('datavalueformat'));
				}
			} 
			$d->onBeforeRender();
		}
		$html = parent::FieldHolder(); 

		if(!empty($d)) {
			$html = $d->onAfterRender($html); 
		}	
		return $html;
	}
	
	function SmallFieldHolder($properties = array()){
		$d = MultiDateField_View_JQuery::create($this);
		$d->onBeforeRender();
		$html = parent::SmallFieldHolder($properties);
		$html = $d->onAfterRender($html);
		return $html;
	}

	public function Field($properties = array()) {
		$config = array(
			'separator' => $this->getConfig('separator'),
			'showcalendar' => $this->getConfig('showcalendar'),
			'isoDateformat' => $this->getConfig('dateformat'),
			'jquerydateformat' => DateField_View_JQuery::convert_iso_to_jquery_format($this->getConfig('dateformat')),
			'min' => $this->getConfig('min'),
			'max' => $this->getConfig('max')
		);

		// Add other jQuery UI specific, namespaced options (only serializable, no callbacks etc.)
		// TODO Move to DateField_View_jQuery once we have a properly extensible HTML5 attribute system for FormField
		$jqueryUIConfig = array();
		foreach($this->getConfig() as $k => $v) {
			if(preg_match('/^jQueryUI\.(.*)/', $k, $matches)) $jqueryUIConfig[$matches[1]] = $v;
		}
		if ($jqueryUIConfig)
			$config['jqueryuiconfig'] =  Convert::array2json(array_filter($jqueryUIConfig));
		$config = array_filter($config);
		foreach($config as $k => $v) $this->setAttribute('data-' . $k, $v);
		
		// Three separate fields for day, month and year (not available for multidates)
		if($this->getConfig('dmyfields')) {
			user_error("MultiDateField doen't work with separate fields for day/month/year");
		}
		
		// Default text input field
		$html = parent::Field();
		
		return $html;
	}

	public function Type() {
		return 'multidate text';
	}
	
	/**
	 * Sets the internal value to ISO date format.
	 * 
	 * @param String|Array $val 
	 */
	public function setValue($val) {
		$locale = new Zend_Locale($this->locale);
		
		if(empty($val)) {
			$this->value = null;
			$this->valueObj = null;
		} else {
			if($this->getConfig('dmyfields')) {
				user_error("MultiDateField doen't work with separate fields for day/month/year");
			} else {
				if(!empty($val)){
					// Setting in corect locale.
					$first = true;
					foreach(explode(',', $val) as $ts){
						if(Zend_Date::isDate(trim($ts), $this->getConfig('dateformat'), $locale)) {
							$dateobj = new Zend_Date(trim($ts), $this->getConfig('dateformat'), $locale);
							if(!$first)$this->value .= $this->getConfig('separator');
							$this->value .= $dateobj->get($this->getConfig('dateformat'), $locale);
							if($first){ // reset array
								$this->valueObj = array($dateobj);
							} else { // add to array
								$this->valueObj[] = $dateobj;
							}
						}
						// load ISO date from database (usually through Form->loadDataForm())
						else if(Zend_Date::isDate(trim($ts), $this->getConfig('datavalueformat'))) {
							$dateobj = new Zend_Date(trim($ts), $this->getConfig('datavalueformat'));
							if(!$first)$this->value .= $this->getConfig('separator');
							$this->value .= $dateobj->get($this->getConfig('dateformat'), $locale);
							if($first){ // reset array
								$this->valueObj = array($dateobj);
							} else { // add to array
								$this->valueObj[] = $dateobj;
							}
						}
						$first = false;
					}
				}
				// this will probably never be executed in MultiDatefield, invalid dates will be discarded
				else {
					$this->value = $val;
					$this->valueObj = null;
				}
			}
		}

		return $this;
	}
	
	/**
	 * @return String ISO 8601 date, suitable for insertion into database
	 */
	public function dataValue() {
		if($this->valueObj) {
			$ret = [];
			// Loop over multiple dates
			foreach($this->valueObj as $dateobj){
				$ret[] = $dateobj->toString($this->getConfig('datavalueformat'));
			}
//			Debug::dump(implode(',', $ret));
			return implode(',', $ret);
		} else {
			return null;
		}
	}

	/**
	 * @return Boolean
	 */
	public function validate($validator) {
//		Debug::dump($this->value);
		
//		$valid = true;
//		
//		// Don't validate empty fields
//		if(empty($this->value)) return true;
//
//		// date format
//		if($this->getConfig('dmyfields')) {
//			$valid = (!$this->value || $this->validateArrayValue($this->value));
//		} else {
//			$valid = (Zend_Date::isDate($this->value, $this->getConfig('dateformat'), $this->locale));
//		}
//		if(!$valid) {
//			$validator->validationError(
//				$this->name, 
//				_t(
//					'DateField.VALIDDATEFORMAT2', "Please enter a valid date format ({format})", 
//					array('format' => $this->getConfig('dateformat'))
//				), 
//				"validation", 
//				false
//			);
//			return false;
//		}
//		
//		// min/max - Assumes that the date value was valid in the first place
//		if($min = $this->getConfig('min')) {
//			// ISO or strtotime()
//			if(Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
//				$minDate = new Zend_Date($min, $this->getConfig('datavalueformat'));
//			} else {
//				$minDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($min)), $this->getConfig('datavalueformat'));
//			}
//			if(!$this->valueObj || (!$this->valueObj->isLater($minDate) && !$this->valueObj->equals($minDate))) {
//				$validator->validationError(
//					$this->name, 
//					_t(
//						'DateField.VALIDDATEMINDATE',
//						"Your date has to be newer or matching the minimum allowed date ({date})", 
//						array('date' => $minDate->toString($this->getConfig('dateformat')))
//					),
//					"validation", 
//					false
//				);
//				return false;
//			}
//		}
//		if($max = $this->getConfig('max')) {
//			// ISO or strtotime()
//			if(Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
//				$maxDate = new Zend_Date($max, $this->getConfig('datavalueformat'));
//			} else {
//				$maxDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($max)), $this->getConfig('datavalueformat'));
//			}
//			if(!$this->valueObj || (!$this->valueObj->isEarlier($maxDate) && !$this->valueObj->equals($maxDate))) {
//				$validator->validationError(
//					$this->name, 
//					_t('DateField.VALIDDATEMAXDATE',
//						"Your date has to be older or matching the maximum allowed date ({date})", 
//						array('date' => $maxDate->toString($this->getConfig('dateformat')))
//					),
//					"validation", 
//					false
//				);
//				return false;
//			}
//		}
		
		return true;
	}
	
}

class MultiDateField_View_JQuery extends DateField_View_JQuery {
	
	/**
	 * @param String $html
	 * @return 
	 */
	public function onAfterRender($html) {
		
		parent::onAfterRender($html);
		
		if($this->getField()->getConfig('showcalendar')) {
			Requirements::css(MULTIDATEFIELD_DIR."/css/multidatefield.css");
			Requirements::javascript(MULTIDATEFIELD_DIR
					."/thirdparty/jquery-multidatepicker/jquery-ui.multidatespicker.js");
			Requirements::javascript(MULTIDATEFIELD_DIR . "/javascript/MultiDateField.js");
		}
		
		return $html;
	}
	
}