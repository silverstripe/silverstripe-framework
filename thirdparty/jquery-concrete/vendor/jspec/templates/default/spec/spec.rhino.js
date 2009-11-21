
load('JSPEC_ROOT/lib/jspec.js')
load('lib/yourlib.core.js')

JSpec
.exec('spec/spec.core.js')
.run({ formatter: JSpec.formatters.Terminal })
.report()