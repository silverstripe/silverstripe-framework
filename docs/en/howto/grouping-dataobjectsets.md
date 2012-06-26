# Grouping lists of records

The [api:SS_List] class is designed to return a flat list of records.
These lists can get quite long, and hard to present on a single list.
[Pagination](/howto/pagination) is one way to solve this problem,
by splitting up the list into multiple pages.

In this howto, we present an alternative to pagination: 
Grouping a list by various criteria, through the `[api:GroupedList]` class.
This class is a `[api:SS_ListDecorator]`, which means it wraps around a list,
adding new functionality. 

It provides a `groupBy()` method, which takes a field name, and breaks up the managed list 
into a number of arrays, where each array contains only objects with the same value of that field. 
Similarly, the `GroupedBy()` method builds on this and returns the same data in a template-friendly format.

## Grouping Sets By First Letter

This example deals with breaking up a [api:SS_List] into sub-headings by the first letter.

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

The first step is to set up the basic data model, 
along with a method that returns the first letter of the title. This
will be used both for grouping and for the title in the template.

	:::php
	class Module extends DataObject {
		public static $db = array(
			'Title' => 'Text'
		);
	
		/**
		 * Returns the first letter of the module title, used for grouping.
		 * @return string
		 */
		public function getTitleFirstLetter() {
			return $this->Title[0];
		}
	}

The next step is to create a method or variable that will contain/return all the objects, 
sorted by title. For this example this will be a method on the `Page` class.

	:::php
	class Page extends SiteTree {
	
		// ...
	
		/**
		 * Returns all modules, sorted by their title.
		 * @return GroupedList
		 */
		public function getGroupedModules() {
			return GroupedList::create(Module::get()->sort('Title'));
		}
	
	}

The final step is to render this into a template. The `GroupedBy()` method breaks up the set into
a number of sets, grouped by the field that is passed as the parameter. 
In this case, the `getTitleFirstLetter()` method defined earlier is used to break them up.

	:::ss
	<%-- Modules list grouped by TitleFirstLetter --%>
	<h2>Modules</h2>
	<% loop GroupedModules.GroupedBy(TitleFirstLetter) %>
		<h3>$TitleFirstLetter</h3>
		<ul>
			<% loop Children %>
				<li>$Title</li>
			<% end_loop %>
		</ul>
	<% end_loop %>

## Grouping Sets By Month

Grouping a set by month is a very similar process. 
The only difference would be to sort the records by month name, and
then create a method on the DataObject that returns the month name, 
and pass that to the [api:GroupedList->GroupedBy()] call.

We're reusing our example `Module` object,
but grouping by its built-in `Created` property instead,
which is automatically set when the record is first written to the database.
This will have a method which returns the month it was posted in:

	:::php
	class Module extends DataObject {
	
		// ...
	
		/**
		 * Returns the month name this news item was posted in.
		 * @return string
		 */
		public function getMonthCreated() {
			return date('F', strtotime($this->Created));
		}
	
	}

The next step is to create a method that will return all records that exist, 
sorted by month name from January to December. This can be accomplshed by sorting by the `Created` field:

	:::php
	class Page extends SiteTree {
		
		// ...

		/**
		 * Returns all news items, sorted by the month they were posted
		 * @return GroupedList
		 */
		public function getGroupedModulesByDate() {
			return GroupedList::create(Module::get()->sort('Created'));
		}
	
	}

The final step is the render this into the template using the [api:GroupedList->GroupedBy()] method.

	:::ss
	// Modules list grouped by the Month Posted
	<h2>Modules</h2>
	<% loop GroupedModulesByDate.GroupedBy(MonthCreated) %>
		<h3>$MonthCreated</h3>
		<ul>
			<% loop Children %>
				<li>$Title ($Created.Nice)</li>
			<% end_loop %>
		</ul>
	<% end_loop %>

## Related

 * [Howto: "Pagination"](/howto/pagination)