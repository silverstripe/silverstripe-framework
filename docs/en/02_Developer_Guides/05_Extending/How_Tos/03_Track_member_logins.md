# Howto: Track Member Logins

Sometimes its good to know how active your users are,
and when they last visited the site (and logged on).
A simple `LastVisited` property on the `Member` record
with some hooks into the login process can achieve this.
In addition, a `NumVisit` property will tell us how
often the member has visited. Or more specifically,
how often he has started a browser session, either through
explicitly logging in or by invoking the "remember me" functionality.

	:::php
	<?php
	class MyMemberExtension extends DataExtension {
		private static $db = array(
			'LastVisited' => 'Datetime',
			'NumVisit' => 'Int',
		);

		public function memberLoggedIn() {
			$this->logVisit();
		}

		public function memberAutoLoggedIn() {
			$this->logVisit();
		}

		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldsToTab('Root.Main', array(
				ReadonlyField::create('LastVisited', 'Last visited'),
				ReadonlyField::create('NumVisits', 'Number of visits')
			));
		}

		protected function logVisit() {
			if(!Security::database_is_ready()) return;
			
			DB::query(sprintf(
				'UPDATE "Member" SET "LastVisited" = %s, "NumVisit" = "NumVisit" + 1 WHERE "ID" = %d',
				DB::get_conn()->now(),
				$this->owner->ID
			));
		}
	}

Now you just need to apply this extension through your config:

	:::yml
	Member:
		extensions:
			- MyMemberExtension

