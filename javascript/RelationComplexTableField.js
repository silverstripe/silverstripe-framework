

Event.observe( window, 'load', function() {
	if(document.getElementById('sitetree')){
		if(typeof document.getElementById('sitetree').observeMethod != 'undefined') {
			document.getElementById('sitetree').observeMethod( 'NodeClicked' , function() {
				checkedListNameArray = null;
				checkedArray = null;
			} );
		}
	}
} );

RelationComplexTableField = Class.create();
RelationComplexTableField.prototype = {	
	initialize: function() {
		var checkedListNameArray = null;
		var checkedListEndName = 'CheckedList';
		var checkedListField = 'selected';
		var checkedArray = null;
		
		// 1) Find The Hidden Field Where The IDs Will Be Stored
		
		var checkedList = document.getElementById( this.id + '_' + checkedListEndName );
		
		// 2) Initialize The Array Or Update The Hidden Input Field And The HTML Table
		
		var checkedListName = checkedList.getAttribute( 'name' );
		//if( checkedListNameArray == null ) {
			checkedListNameArray = [];
			checkedListNameArray.push( checkedListName );
			checkedArray = [];
			if( checkedList.getAttribute( 'value' ) )
				checkedArray.push( checkedList.getAttribute( 'value' ).split( ',' ) );
		//}
		/*
		else if( checkedListNameArray.indexOf( checkedListName ) < 0 ) {
			checkedListNameArray.push( checkedListName );
			if( checkedList.getAttribute( 'value' ) )
				checkedArray[ checkedListNameArray.length - 1 ] = checkedList.getAttribute( 'value' ).split( ',' );
		}
		else {
			
			var index = checkedListNameArray.indexOf( checkedListName );
			
			// a) Update The Hidden Input Field
			
			checkedList.setAttribute( 'value', checkedArray[ index ] );
			
			// b) Update The HTML Table
			
			markingInputs = document.getElementsByName( checkedListName.substring( 0, checkedListName.indexOf( '[' ) ) + '[]' );
			
			for( var i = 0; i < markingInputs.length; i++ ) {
				markingInput = markingInputs[ i ];
				if( checkedArray[ index ] && checkedArray[ index ].indexOf( markingInput.getAttribute( 'value' ) ) > -1 ) {
					markingInput.setAttribute( 'checked', 'checked' );}
				else
					markingInput.removeAttribute( 'checked' );
			}
		} */
				
		// 3) Create The Rules
		
		var rules = {};
				
		rules[ '#' + this.id + ' table.data tbody td.markingcheckbox input' ] = {
			onclick : function() {
				
				// 1) Find The Hidden Field Where The IDs Will Be Stored
				
				var checkedListName = this.getAttribute( 'name' );
				checkedListName = checkedListName.substring( 0, checkedListName.length - 1 ) + checkedListField + ']';
				var inputs = document.getElementsByTagName( 'input' );
				for( var i = 0; i < inputs.length; i++ ) {
					var checkedList = inputs[ i ];
					if( checkedList.getAttribute( 'name' ) == checkedListName )
						break;
				}
				var index = checkedListNameArray.indexOf( checkedListName );
				
				// 2) Update The Array
				
				if( checkedArray[ index ] && checkedArray[ index ].indexOf( this.getAttribute( 'value' ) ) > -1 ) {
					index2 = checkedArray[ index ].indexOf( this.getAttribute( 'value' ) );
					var previousCheckedArray = checkedArray[ index ];
					checkedArray[ index ] = [];
					for( var i = 0; i < previousCheckedArray.length; i++ ) {
						if( i != index2 )
							checkedArray[ index ].push( previousCheckedArray[ i ] );
					}
					if( this.getAttribute( 'type' ) == 'radio' )
						this.checked = false;
				}
				else if( checkedArray[ index ] ) {
					if( this.getAttribute( 'type' ) == 'radio' )
						checkedArray[ index ] = [];
					checkedArray[ index ].push( this.getAttribute( 'value' ) );
				}
				else {
					checkedArray[ index ] = [];
					checkedArray[ index ].push( this.getAttribute( 'value' ) );
				}
				
				// 3) Update The Hidden Input Field
				
				checkedList.setAttribute( 'value', checkedArray[ index ] );
			}
		};
		
		Behaviour.register( rules );
	}
}

RelationComplexTableField.applyTo('#Form_EditForm div.HasOneComplexTableField');
RelationComplexTableField.applyTo('#Form_EditForm div.HasManyComplexTableField');
RelationComplexTableField.applyTo('#Form_EditForm div.ManyManyComplexTableField');
