load('vendor/jquery-1.3.2.js')
load('vendor/jspec/lib/jspec.js')
load('vendor/jspec/lib/jspec.jquery.js')
load('lib/jquery.changetracker.js')

JSpec
.exec('spec/spec.changetracker.basics.js')
.run({ formatter : JSpec.formatters.Terminal })
.report()