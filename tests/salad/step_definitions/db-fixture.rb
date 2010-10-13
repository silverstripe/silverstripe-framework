# Reset database
if $baseURL and $browser then
  $browser.goto $baseURL + 'dev/tests/endsession'
  $browser.goto $baseURL + 'dev/tests/startsession?fixture=sapphire/tests/Bare.yml&flush=1'
end