# Grouping Data Object Sets

The [api:DataObjectSet] class has a number of methods useful for grouping objects by fields. Together with sorting this
can be used to break up long lists of data into more manageable sub-sections.

The [api:DataObjectSet->groupBy()] method takes a field name as the single argument, and breaks the set up into a number
of arrays, where each array contains only objects with the same value of that field. The [api:DataObjectSet->GroupedBy()]
method builds on this and returns the same data in a template-friendly format.

## Grouping Sets By First Letter

This example deals with breaking up a [api:DataObjectSet] into sub-headings by the first letter.

Let's say you have a set of Module objects, each representing a SilverStripe module, and you want to output a list of
these in alphabetical order, with each letter as a heading; something like the following list:

	*	B
		* Blog
	*	C
		* CMS Workflow
		* Custom Translations
	*	D
		* Database Plumber
		* ...

The first step is to set up the basic data model, along with a method that returns the first letter of the title. This
will be used both for grouping and for the title in the template.

	:::php
	class Module extends DataObject {
	
		public static $db = array(
			'Title' => 'Varchar(255)'
		);
	
		// ...
	
		/**
		 * Returns the first letter of the module title, used for grouping.
		 *
		 * @return string
		 */
		public function getTitleFirstLetter() {
			return $this->Title[0];
		}
	
	}

The next step is to create a method or variable that will contain/return all the Module objects, sorted by title. For
this example this will be a method on the Page class.

	:::php
	class Page extends SiteTree {
	
		// ...
	
		/**
		 * Returns all modules, sorted by their title.
		 *
		 * @return DataObjectSet
		 */
		public function getModules() {
			return DataObject::get('Module', null, '"Title"');
		}
	
	}

The final step is to render this into a template. The [api:DataObjectSet->GroupedBy()] method breaks up the set into
a number of sets, grouped by the field that is passed as the parameter. In this case, the getTitleFirstLetter method
defined earlier is used to break them up.

	:::ss
	// Modules list grouped by TitleFirstLetter
	<h2>Modules</h2>
	<% control Modules.GroupedBy(TitleFirstLetter) %>
		<h3>$TitleFirstLetter</h3>
		<ul>
			<% control Children %>
				<li>$Title</li>
			<% end_control %>
		</ul>
	<% end_control %>

## Grouping Sets By Month

Grouping a set by month is a very similar process. The only difference would be to sort the records by month name, and
then create a method on the DataObject that returns the month name, and pass that to the [api:DataObjectSet->GroupedBy()]
call.

Again, the first step is to create a method on the class in question that will be displayed in a list. For this example,
a [api:DataObject] called NewsItem will be used. This will have a method which returns the month it was posted in:

	:::php
	class NewsItem extends DataObject {
	
		public static $db = array(
			'Title' => 'Varchar(255)',
			'Date'  => 'Date'
		);
	
		// ...
	
		/**
		 * Returns the month name this news item was posted in.
		 *
		 * @return string
		 */
		public function getMonthPosted() {
			return date('F', strtotime($this->Date));
		}
	
	}

The next step is to create a method that will return all the News records that exist, sorted by month name from
January to December. This can be accomplshed by sorting by the Date field:

	:::php
	class Page extends SiteTree {
	
		/**
		 * Returns all news items, sorted by the month they were posted
		 *
		 * @return DataObjectSet
		 */
		public function getNewsItems() {
			return DataObject::get('NewsItem', null, '"Date"');
		}
	
	}

The final step is the render this into the template using the [api:DataObjectSet->GroupedBy()] method.

	:::ss
	// Modules list grouped by the Month Posted
	<h2>Modules</h2>
	<% control NewsItems.GroupedBy(MonthPosted) %>
		<h3>$MonthPosted</h3>
		<ul>
			<% control Children %>
				<li>$Title ($Date.Nice)</li>
			<% end_control %>
		</ul>
	<% end_control %>