
ExampleModule = {
  name : 'ExampleModule',
  utilities : {
    doFoo : function(){ return 'foo' },
    doBar : function(){ return 'bar' }
  },
  randomHook : function(a, b) {
    return [a, b]
  },
  beforeSpec  : function() { addedBeforeSpec  = true; doFoo()  },
  afterSpec   : function() { addedAfterSpec   = true  },
  beforeSuite : function() { addedBeforeSuite = true  },
  afterSuite  : function() { addedAfterSuite  = true  },
  checkJSpecContext : function(){ return each },
  checkContext : function() { return fixture('test') },
  checkModuleContext : function() { return this.name },
  checkUtilityContext : function() { return doFoo() },
  matchers : {
    be_foo_bar : function() {
      return true
    }
  },
  DSLs : {
    snake : {
      some_snake_case_stuff : function(){
        return true
      }
    },
    camel : {
      someCamelCaseStuff : function() {
        return true
      }
    }
  }
}

JSpec.include(ExampleModule)

JSpec.include({
  name : 'EmptyModule'
})