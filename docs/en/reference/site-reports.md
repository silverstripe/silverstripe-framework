# Site Reports

## Introduction
A report is a little bit of functionally in the CMS designed to provide a report of your data or content. You can access
the site reports by clicking *Reports* in the left hand side bar and selecting the report you wish to view.

![](_images/sitereport.png) 


## Default Reports

By default the CMS ships with several basic reports:

*  VirtualPages pointing to deleted pages
*  RedirectorPages pointing to deleted pages
*  Pages with broken files
*  Pages with broken links
*  Broken links report
*  Pages with no content
*  Pages edited in the last 2 weeks

Modules may come with ther own additional reports.

## Creating Custom Reports

Custom reports can be created quickly and easily. A general knowledge of SilverStripe's
[Datamodel](/topics/datamodel) is useful before creating a custom report. 

Inside the *mysite/code* folder create a file called *CustomSideReport.php*. Inside this file we can add our site reports. 

The following example will list every Page on the current site.

###CustomSideReport.php 

	:::php
	class CustomSideReport_NameOfReport extends SS_Report {
		
		// the name of the report
		public function title() {
			return 'All Pages';
		}
		
		// what we want the report to return and what order
		public function sourceRecords($params = null) {
			return Page::get()->sort('Title');
		}
		
		// which fields on that object we want to show
		public function columns() {
			$fields = array(
				'Title' => 'Title'
			);
			
			return $fields;
		}
	}
	
More useful reports can be created by changing the `DataList` returned in the `sourceRecords` function.

## Notes

*  `CustomSideReport_ReportName` must extend `SS_Report`
*  It is recommended to place all custom reports in the 1 file.
** Create a *CustomSideReport.php* file and add classes as you need them inside that for each report

## TODO

*  How to format and make advanced reports.
*  More examples

## API Documentation
`[api:ReportAdmin]`