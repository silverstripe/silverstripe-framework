
load('JSPEC_ROOT/lib/jspec.js')
load('public/javascripts/application.js')

JSpec
.exec('jspec/spec.application.js')
.run({ formatter: JSpec.formatters.Terminal })
.report()