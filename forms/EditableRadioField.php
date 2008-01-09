<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDropdown
 * Represents a set of selectable radio buttons
 * @package forms
 * @subpackage fieldeditor
 */
class EditableRadioField extends EditableFormField {
	
	static $has_many = array(
		"Options" => "EditableRadioOption"
	);
	
	static $singular_name = 'Radio field';
	static $plural_name = 'Radio fields';
    
	function delete() {
  		$options = $this->Options();

		foreach( $options as $option )
			$option->delete();
		
		parent::delete();   
	}
	
	function duplicate() {
		$clonedNode = parent::duplicate();
		
		foreach( $this->Options() as $field ) {
			$newField = $field->duplicate();
			$newField->ParentID = $clonedNode->ID;
			$newField->write();
		}
		
		return $clonedNode;
	}
	
	function EditSegment() {
		return $this->renderWith( $this->class );
	}
	
	function populateFromPostData( $data ) {
		parent::populateFromPostData( $data );
		
		$fieldSet = $this->Options();
		$deletedOptions = explode( ',', $data['Deleted'] );
		
		//Debug::show( $deletedOptions );
		
		// store default, etc
		foreach( $fieldSet as $option ) {
			
			//Debug::show( $option );
			
			if( $deletedOptions && array_search( $option->ID, $deletedOptions ) !== false ) {
				$option->delete();
				continue;
			}
			
			if( $data[$option->ID] ) {
				$option->setField( 'Default', $option->ID == $data['Default'] );
				$option->populateFromPostData( $data[$option->ID] );
			}
				
			unset( $data[$option->ID] );
		}
		
		// Debug::show( $data );
		
		foreach( $data as $tempID => $optionData ) {
			
			$optionNumber = 0;
			
			if( !$tempID || !is_array( $optionData ) || empty( $optionData ) || !preg_match('/^_?\d+$/', $tempID ) )
				continue;
			
			// what will we name the new option?
			$newOption = new EditableRadioOption();
			$newOption->Name =  sprintf( 'option%d', $optionNumber++ );
			$newOption->ParentID = $this->ID;
			$newOption->setField( 'Default', $tempID == $data['Default'] );
			$newOption->populateFromPostData( $optionData );
			
			// $mail .= "NEW: " . $optionData['Title'] . "\n";
			
			if( Director::is_ajax() ) {
				$fieldID = $this->ID;
				$fieldEditorName = $this->editor ? $this->editor->Name() : 'Fields';
				$prefix = $fieldEditorName . '[' . $fieldID . ']';
				$newID = $newOption->ID;
				$newSort = $newOption->Sort;
				echo "\$('". $fieldEditorName . "[$fieldID]').updateOption('$prefix','$tempID','$newID','$newSort');";
			}
			
			if( !$newOption->Title )
				user_error('Added blank option '.$tempID, E_USER_ERROR);
		}
	}
	
	function DefaultOption() {
		$defaultOption = 0;
		
		foreach( $this->Options() as $option ) {
			if( $option->getField('Default') )
				return $defaultOption;
			else
				$defaultOption++;
		}
		
		return -1;
	}
	
	function getFormField() {
		return $this->createField();
	}
	
	function getFilterField() {
		return $this->createField( true );
	}
	
	function createField( $asFilter = false ) {
		$optionSet = $this->Options();
		$options = array();
		$defaultOption = '';
		
		if( $asFilter )
			$options['-1'] = '(Any)';
		
		// $defaultOption = '-1';
		
		foreach( $optionSet as $option ) {
			$options[$option->Title] = $option->Title;
			if( $option->getField('Default') && !$asFilter ) $defaultOption = $option->Title;
		}
		
		// return radiofields
		return new OptionsetField($this->Name, $this->Title, $options, $defaultOption);
	}
    
    function prepopulate( $value ) {
        
        $options = $this->Options();
        
        $paramMap = $this->parsePrepopulateValue( $value );
        
        // find options and add them
        $optionNumber = 0;
        foreach( $paramMap['Options'] as $newOption ) {
            if( preg_match( '/([^:]+)[:](.*)/', $newOption, $match ) ) {
                $newOptionValue = $match[1]; 
                $newOptionTitle = $match[2];
                
                $newOptionTitle = preg_replace('/__/', ' ', $newOptionTitle );
                
                $newOption = $this->createOption( 
                    'option' . (string)$optionNumber,
                    $newOptionTitle,
                    'new-' . (string)$optionNumber,
                    $newOption['Sort'],
                    $optionNumber == 1,
                    false
                );
                
                $optionNumber++;
                $options->addWithoutWrite( $newOption );    
            }
        }
    }
    
protected function createOption( $name, $title, $id, $sort = 0, $isDefault = false ) {
  $newOption = new EditableRadioOption();
		$newOption->Name =  $name;
		$newOption->Title = $title;
	  $newOption->ID = $id;
		$newOption->Sort = $sort;
		$newOption->setField('Default', $isDefault ? '1' : '0');
			
      return $newOption;
    }
        
  	function TemplateOption() {
			$option = new EditableRadioOption();
			$option->ParentID = $this->ID;
			return $option->EditSegment();
		}
	}
?>
