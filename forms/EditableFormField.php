<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * Represents an editable form field
 * @package forms
 * @subpackage fieldeditor
 */
class EditableFormField extends DataObject {
	
	static $default_sort = "Sort";
	
	static $db = array(
		"Name" => "Varchar",
		"Title" => "Varchar(255)",
		"Default" => "Varchar",
		"Sort" => "Int",
		"Required" => "Boolean",
	  	"CanDelete" => "Boolean",
    	"CustomParameter" => "Varchar"
	);
    
	static $defaults = array(
		"CanDelete" => "1"
	);
    
	static $has_one = array(
		"Parent" => "SiteTree",
	);
	
	protected $readonly;
	
	protected $editor;
	
	function setEditor( $editor ) {
		$this->editor = $editor;
	}
	
	function __construct( $record = null, $isSingleton = false ) {
		$this->setField('Default', -1);
		parent::__construct( $record, $isSingleton );
	}	
	
	function EditSegment() {
		return $this->renderWith('EditableFormField');
	}
	
	function isReadonly() {
		return $this->readonly;
	}
	
	function ClassName() {
		return $this->class;
	}
	
	function makeReadonly() {
		$this->readonly = true;
		return $this;
	}
	
	function ReadonlyEditSegment() {
		$this->readonly = true;
		return $this->EditSegment();
	}
	
	function TitleField() {
		// return new TextField( "Fields[".$this->ID."][Title]", null, $this->Title );
		$titleAttr = Convert::raw2att($this->Title);
		$readOnlyAttr = '';
		
		if( $this->readonly ) {
			$readOnlyAttr = ' disabled="disabled"';
		} else {
			$readOnlyAttr = '';
		}
		
		return "<input type=\"text\" class=\"text\" title=\"("._t('EditableFormField.ENTERQUESTION', 'Enter Question').")\" value=\"$titleAttr\" name=\"Fields[{$this->ID}][Title]\"$readOnlyAttr />";
	}
	
	function Name() {
		return "Fields[".$this->ID."]";
	}
	
	/*function getName() {
		return "field" . $this->ID;
	}*/
		
	function populateFromPostData( $data ) {
		$this->Title = $data['Title'];
		if(isset($data['Default'])) {
			$this->setField('Default', $data['Default']);
		}
		$this->Sort = $data['Sort'];
  		$this->CustomParameter = $data['CustomParameter'];
		$this->Required = !empty( $data['Required'] ) ? 1 : 0;
  		$this->CanDelete = ( isset( $data['CanDelete'] ) && !$data['CanDelete'] ) ? 0 : 1;
		$this->write();
		
		// The field must be written to ensure a unique ID.
		$this->Name = $this->class.$this->ID;
		$this->write();
	}
	
	function ExtraOptions() {
		
		$baseName = "Fields[$this->ID]";
		$extraOptions = new FieldSet();
		
		if( !$this->Parent()->hasMethod( 'hideExtraOption' ) ){
		        $extraOptions->push( new CheckboxField($baseName . "[Required]", _t('EditableFormField.REQUIRED', 'Required?'), $this->Required) );
		}elseif( !$this->Parent()->hideExtraOption( 'Required' ) ){
			$extraOptions->push( new CheckboxField($baseName . "[Required]", _t('EditableFormField.REQUIRED', 'Required?'), $this->Required) );
		}
		
		if( $this->Parent()->hasMethod( 'getExtraOptionsForField' ) ) {
			$extraFields = $this->Parent()->getExtraOptionsForField( $this );
		
			foreach( $extraFields as $extraField )
				$extraOptions->push( $extraField );
		}
		
		if( $this->readonly )
			$extraOptions = $extraOptions->makeReadonly();		
				
		return $extraOptions;
	}
	
	/**
	 * Return a FormField to appear on the front end
	 */
	function getFormField() {
	}
	
	function getFilterField() {
		
	}
	
	/**
	 * Return an evaluation appropriate for a filter clause
	 * @todo: escape the string
	 */
	function filterClause( $value ) {
		// Not filtering on this field
		
		if( $value == '-1' ) 
			return "";
		else
			return "`{$this->name}` = '$value'";
	}
	
	function showInReports() {
		return true;
	}
    
    function prepopulate( $value ) {
        $this->prepopulateFromMap( $this->parsePrepopulateValue( $value ) );
    }
    
    protected function parsePrepopulateValue( $value ) {
        $paramList = explode( ',', $value );
        
        $paramMap = array();
        
        foreach( $paramList as $param ) {
    
            if( preg_match( '/([^=]+)=(.+)/', $param, $match ) ) {
                if( isset( $paramMap[$match[1]] ) && is_array( $paramMap[$match[1]] ) ) {
                    $paramMap[$match[1]][] = $match[2];
                } else if( isset( $paramMap[$match[1]] ) ) {
                    $paramMap[$match[1]] = array( $paramMap[$match[1]] );
                    $paramMap[$match[1]][] = $match[2];
                    //Debug::message( $match[1] . '[]=' . $match[2] );  
                } else {
                    $paramMap[$match[1]] = $match[2];
                    //Debug::message( $match[1] . '=' . $match[2] );
                }
            } else {
                //Debug::message('Invalid: ' . $param );   
            }
        }
        
        //Debug::show( $paramMap );
        
        return $paramMap;   
    }
    
    protected function prepopulateFromMap( $paramMap ) {
        //Debug::show( $paramMap );
        //Debug::show( $this->stat('db') );
        
        foreach( $paramMap as $field => $fieldValue ) {
            if( /*$this->hasField( $field ) &&*/ !is_array( $fieldValue ) ) {
                $this->$field = $fieldValue;
                // Debug::message( 'Set ' . $field . ':'. $fieldValue );
            }   
        }
        
        // exit();   
    }
    
    function Type() {
        return $this->class;   
    }
    
    function CustomParameter() {
        return $this->CustomParameter;   
    }
    /*
  function saveInto( DataObject $record ) {
		if(	
	}
		*/
}
?>