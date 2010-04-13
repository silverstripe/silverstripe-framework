
__loading__ = []
__loadDelay__ = 1000

originalPrint = print
print = puts

readFile = function(path, callback) {
  __loading__.push(path)
  var promise = node.fs.cat(path, "utf8")
  promise.addErrback(function(){ throw "failed to read file `" + path + "'" })
  promise.addCallback(function(contents){
    setTimeout(function(){
      if (__loading__[0] == path)
        __loading__.shift(), callback(contents)
      else
        setTimeout(arguments.callee, 50)
    }, 50)
  })  
}

load = function(path) {
  readFile(path, function(contents){
    eval(contents)
  })
}

load('lib/jspec.js')
load('spec/modules.js')
load('spec/spec.grammar-less.js')

setTimeout(function(){
  JSpec
  .exec('spec/spec.grammar.js')
  .exec('spec/spec.js')
  .exec('spec/spec.matchers.js')
  .exec('spec/spec.utils.js')
  .exec('spec/spec.shared-behaviors.js')
  setTimeout(function(){ 
    JSpec.run({ formatter : JSpec.formatters.Terminal, failuresOnly : false })
    setTimeout(function() {
      JSpec.report()
    }, __loadDelay__ / 3)
  }, __loadDelay__ / 3)
}, __loadDelay__ / 3)

