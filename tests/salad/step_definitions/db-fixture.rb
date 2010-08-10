def loadFixture(fileName)
	if $baseURL and $browser then
		$browser.goto $baseURL + "dev/tests/sessionloadyml?fixture=" + fileName + "&flush=1"
	else
		fail("No \$baseUrl or \$browser found")
	end
end

def startSession
	# Reset database
	if $baseURL and $browser then
		$browser.goto $baseURL + 'dev/tests/endsession'
	else
		fail("No \$baseUrl or \$browser found")
	end
	fileName = 'sapphire/tests/Bare.yml'
	if $baseURL and $browser then
		$browser.goto $baseURL + "dev/tests/startsession?fixture=" + fileName + "&flush=1"
	else
		fail("No \$baseUrl or \$browser found")
	end
end

Given /load the fixture file "([^"]+)"/ do |fileName|
	loadFixture(fileName)
end

startSession()

Before do
  Given "I visit /dev/tests/emptydb?fixture=sapphire/tests/Bare.yml"
end