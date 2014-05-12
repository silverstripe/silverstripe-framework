Feature: GridField
	As a CMS user
	I want to be able to ensure GridFields display correctly
	In order to ensure accurate GridField interaction

	Background:
		Given a "member" "Joe" belonging to "Admin Group" with "Email"="joe@test.com" and "Password"="secret"
		And the "group" "Admin Group" has permissions "Full administrative rights"
		And I log in with "joe@test.com" and "secret"

	# This would benefit from a fixture, rather than relying on default CMS behaviour for testing
	Scenario: I can see a spyglass icon when a GridFieldFilterHeader is present
		Then I go to "admin/security"
		And I should see a ".filter-header" element
		And I should see a ".ss-gridfield-button-filter" element

	# This would benefit from a fixture, rather than relying on default CMS behaviour for testing
	Scenario: I cannot see a spyglass icon when a GridFieldFilterHeader isn't present
		Then I go to "admin/pages"
		And I should not see a ".filter-header" element
		And I should not see a ".ss-gridfield-button-filter" element