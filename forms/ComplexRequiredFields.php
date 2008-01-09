<?php 

/**
 * @package forms
 * @subpackage validators
 */

/**
 * Validator that makes it easy to do required-fields while still allowing custom behaviour.
 * @package forms
 * @subpackage validators
 * @deprecated How is this better than / different from {@link CustomRequiredFields}?
 */
class ComplexRequiredFields extends RequiredFields{
	protected $required;

	function __construct() {
		$Required = func_get_args();
		$this->required = $Required;
	}	
	/**
	 * Removes all required fields from this validator
	 */
	public function removeValidation(){
		$this->required = null;
	}
	
	
	/**
	 * Creates the client side validation from form fields
	 * which is generated at the header of each page 
	 */
	function javascript() {
		foreach($this->form->Fields() as $field){
			//if the field type has some special specific specification for validation of itself
			$valid = $field->jsValidation();
			if($valid){
				$code .= $valid;
			}
		}
		if($this->required){
			foreach($this->required[0] as $field) {

				if(is_array($field)){

					$special = "\n						clearValidationErrorCache();\n";
					$special .= "						errors = false;\n";
				
						foreach($field as $compareset){
	
							$special .= "\n						errors = errors || (";
								foreach($compareset as $required){
									$special .= "\n							require('$required',true) && ";						
								}
							$special = substr($special,0,-4);
							$special .= ");";
								
						}
						
						$special .= "\n						if(!errors) showCachedValidationErrors();\n";
						$code .= $special;


				}else{
					$code .= "						require('$field');\n";
					//Tabs for output tabbing :-)
			
				}
			}
		}
		return $code;
	}
	
	/**
	 * Creates the server side validation from form fields
	 * which is executed on form submission
	 */
	function php($data) {
		$valid = true;
		foreach($this->form->Fields() as $field) {
			$valid = ($field->validate($this) && $valid);
		}
		if($this->required){
			foreach($this->required[0] as $key => $field) {
				if(is_array($field)){
					$dataok = false;
					// Items to XOR 
					foreach($field as $compareset){
						// Items to AND
						$requiredblock = false;
					
						foreach($compareset as $requiredset){
							$data[$requiredset] ? $requiredblock = $requiredblock || true : $requiredblock = $requiredblock && false;
							$cachedErrors[$requiredset] = $requiredblock;
						}
						$dataok = $requiredblock || $dataok;
					}
					if(!$dataok){
						foreach($cachedErrors as $field => $valid){
							if(!$valid){
								$this->validationError($field,"$field is required","required");
							}
						}					
						return false;
					}
								
				}else{
					// if an error is found, the form is returned.
					if(!$data[$field]) {
						$this->validationError($field,"$field is required","required");
						return false;
					}
				}
			}	
		}
		return $valid;
	}
}

?>
