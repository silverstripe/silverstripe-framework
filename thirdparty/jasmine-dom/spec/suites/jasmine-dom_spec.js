function findSandbox() {
  return document.getElementById('sandbox');
}

function findNode(id) {
  return document.getElementById(id);
}


describe("jasmine.Fixtures", function() {
  var ajaxData = 'some ajax data';
  var fixtureUrl = 'some_url';
  var anotherFixtureUrl = 'another_url';

  var fixturesContainer = function() {
    return document.getElementById(jasmine.getFixtures().containerId);
  };
  
  var appendFixturesContainerToDom = function() {
    var container= document.createElement('div');
    container.id= jasmine.getFixtures().containerId;
    
    container.appendChild(document.createTextNode('old content'));
  };

  beforeEach(function() {
    jasmine.getFixtures().clearCache();
    // spyOn($, 'ajax').andCallFake(function(options) {
    //   options.success(ajaxData);
    // });
  });

  // describe("cache", function() {
  //   describe("clearCache", function() {
  //     it("should clear cache and in effect force subsequent AJAX call", function() {
  //       jasmine.getFixtures().read(fixtureUrl);
  //       jasmine.getFixtures().clearCache();
  //       jasmine.getFixtures().read(fixtureUrl);
  //       expect($.ajax.callCount).toEqual(2);
  //     });
  //   });
  // 
  //   it("first-time read should go through AJAX", function() {
  //     jasmine.getFixtures().read(fixtureUrl);
  //     expect($.ajax.callCount).toEqual(1);
  //   });
  // 
  //   it("subsequent read from the same URL should go from cache", function() {
  //     jasmine.getFixtures().read(fixtureUrl, fixtureUrl);
  //     expect($.ajax.callCount).toEqual(1);
  //   });    
  // });

  // describe("read", function() {
  //   it("should return fixture HTML", function() {
  //     var html = jasmine.getFixtures().read(fixtureUrl);
  //     expect(html).toEqual(ajaxData);
  //   });
  // 
  //   it("should return duplicated HTML of a fixture when its url is provided twice in a single call", function() {
  //     var html = jasmine.getFixtures().read(fixtureUrl, fixtureUrl);
  //     expect(html).toEqual(ajaxData + ajaxData);
  //   });
  // 
  //   it("should return merged HTML of two fixtures when two different urls are provided in a single call", function() {
  //     var html = jasmine.getFixtures().read(fixtureUrl, anotherFixtureUrl);
  //     expect(html).toEqual(ajaxData + ajaxData);
  //   });
  // 
  //   it("should have shortcut global method readFixtures", function() {
  //     var html = readFixtures(fixtureUrl, anotherFixtureUrl);
  //     expect(html).toEqual(ajaxData + ajaxData);
  //   });
  // });

  // describe("load", function() {
  //   it("should insert fixture HTML into container", function() {
  //     jasmine.getFixtures().load(fixtureUrl);
  //     expect(fixturesContainer().html()).toEqual(ajaxData);
  //   });
  // 
  //   it("should insert duplicated fixture HTML into container when the same url is provided twice in a single call", function() {
  //     jasmine.getFixtures().load(fixtureUrl, fixtureUrl);
  //     expect(fixturesContainer().html()).toEqual(ajaxData + ajaxData);
  //   });
  // 
  //   it("should insert merged HTML of two fixtures into container when two different urls are provided in a single call", function() {
  //     jasmine.getFixtures().load(fixtureUrl, anotherFixtureUrl);
  //     expect(fixturesContainer().html()).toEqual(ajaxData + ajaxData);
  //   });
  // 
  //   it("should have shortcut global method loadFixtures", function() {
  //     loadFixtures(fixtureUrl, anotherFixtureUrl);
  //     expect(fixturesContainer().html()).toEqual(ajaxData + ajaxData);
  //   });
  // 
  //   describe("when fixture container does not exist", function() {
  //     it("should automatically create fixtures container and append it to DOM", function() {
  //       jasmine.getFixtures().load(fixtureUrl);
  //       expect(fixturesContainer().size()).toEqual(1);
  //     });      
  //   });
  // 
  //   describe("when fixture container exists", function() {
  //     beforeEach(function() {
  //       appendFixturesContainerToDom();
  //     });
  // 
  //     it("should replace it with new content", function() {
  //       jasmine.getFixtures().load(fixtureUrl);
  //       expect(fixturesContainer().html()).toEqual(ajaxData);
  //     });
  //   });
  // });

  describe("set", function() {
    var html = '<div>some HTML</div>';
    
    it("should insert HTML into container", function() {
      jasmine.getFixtures().set(html);
      expect(fixturesContainer().innerHTML).toEqual(jasmine.DOM.browserTagCaseIndependentHtml(html));
    });

    it("should have shortcut global method setFixtures", function() {
      setFixtures(html);
      expect(fixturesContainer().innerHTML).toEqual(jasmine.DOM.browserTagCaseIndependentHtml(html));
    });

    describe("when fixture container does not exist", function() {
      it("should automatically create fixtures container and append it to DOM", function() {
        jasmine.getFixtures().set(html);
        expect(fixturesContainer().childNodes.length).toEqual(1);
      });
    });

    describe("when fixture container exists", function() {
      beforeEach(function() {
        appendFixturesContainerToDom();
      });

      it("should replace it with new content", function() {
        jasmine.getFixtures().set(html);
        expect(fixturesContainer().innerHTML).toEqual(jasmine.DOM.browserTagCaseIndependentHtml(html));
      });
    });
  });

  describe("sandbox", function() {
    describe("with no attributes parameter specified", function() {
      it("should create DIV with id #sandbox", function() {
        var sandbox= jasmine.getFixtures().sandbox();
        expect(sandbox.id).toEqual('sandbox');
        expect(jasmine.DOM.browserTagCaseIndependentHtml(sandbox.innerHTML)).toEqual('');
      });
    });

    describe("with attributes parameter specified", function() {
      it("should create DIV with attributes", function() {
        var attributes = {
          attr1: 'attr1 value',
          attr2: 'attr2 value'
        };
        var element = jasmine.getFixtures().sandbox(attributes);

        expect(element.getAttribute('attr1')).toEqual(attributes.attr1);
        expect(element.getAttribute('attr2')).toEqual(attributes.attr2);
      });

      it("should be able to override id by setting it as attribute", function() {
        var idOverride = 'overridden';
        var element = jasmine.getFixtures().sandbox({id: idOverride});
        expect(element.id).toEqual(idOverride);
      });
    });

    it("should have shortcut global method sandbox", function() {
      var attributes = {
        id: 'overridden'
      };
      var element = sandbox(attributes);
      expect(element.id).toEqual(attributes.id);
    });
    
    describe("with string parameter specified", function(){
      it("should create a node with id sandbox", function() {
        var sandbox= jasmine.getFixtures().sandbox("abc");
        expect(sandbox.id).toEqual('sandbox');
        expect(sandbox.innerHTML).toEqual("abc");
      });

      describe("with one tag", function() {
        it("should return a node for the tag rather than nest the tag", function() {
          var sandbox= jasmine.getFixtures().sandbox("<span></span>");
          expect(sandbox.id).toEqual('sandbox');
          expect(sandbox.innerHTML).toEqual('');
        });

        it("should be able to override the id in the tag", function() {
          var sandbox= jasmine.getFixtures().sandbox('<span id="zebra"></span>');
          expect(sandbox.id).toEqual('zebra');
        });
      });
      
      describe("with two tags", function() {
        it("should return a container with both tags", function() {
          var sandbox= jasmine.getFixtures().sandbox("<span></span><div></div>");
          expect(sandbox.id).toEqual('sandbox');
          expect(sandbox.childNodes.length).toEqual(2);
        });
      });
            
    });
    
  });

  describe("cleanUp", function() {
    it("should remove fixtures container from DOM", function() {
      appendFixturesContainerToDom();
      jasmine.getFixtures().cleanUp();
      expect(fixturesContainer()).toEqual(null);
    });
  });

  // WARNING: this block requires its two tests to be invoked in order!
  // (Really ugly solution, but unavoidable in this specific case)
  describe("automatic DOM clean-up between tests", function() {
    // WARNING: this test must be invoked first (before 'SECOND TEST')!
    it("FIRST TEST: should pollute the DOM", function() {
      appendFixturesContainerToDom();
    });

    // WARNING: this test must be invoked second (after 'FIRST TEST')!
    it("SECOND TEST: should see the DOM in a blank state", function() {
      expect(fixturesContainer()).toEqual(null);
    });
  });
});


describe("DOM matchers", function() {
  // describe("when DOM matcher hides original Jasmine matcher", function() {
  //   describe("and tested item is a DOM node", function() {
  //     it("should invoke DOM version of matcher", function() {
  //       var div= document.createElement('div');
  //       expect(div).toBe('div');
  //     });
  //   });
  // 
  //   describe("and tested item is not a DOM node", function() {
  //     it("should invoke original version of matcher", function() {
  //       expect(true).toBe(true);
  //     });
  //   });
  // });

  describe("when DOM matcher does not hide any original Jasmine matcher", function() {
    describe("and tested item in not jQuery object", function() {
      it("should pass negated", function() {
        expect({}).not.toHaveClass("some-class");
      });
    });
  });

  describe("when invoked multiple times on the same fixture", function() {
    it("should not reset fixture after first call", function() {
      setFixtures(sandbox());
      expect(findSandbox()).toExist();
      expect(findSandbox()).toExist();
    });
  });

  describe("toHaveClass", function() {
    var className = "some-class";

    it("should pass when class found", function() {
      setFixtures(sandbox({'class': className}));
      expect(findSandbox()).toHaveClass(className);
    });

    it("should pass negated when class not found", function() {
      setFixtures(sandbox());
      expect(findSandbox()).not.toHaveClass(className);
    });    
  });

  describe("toHaveAttr", function() {
    var attributeName = 'attr1';
    var attributeValue = 'attr1 value';
    var wrongAttributeName = 'wrongName';
    var wrongAttributeValue = 'wrong value';

    beforeEach(function() {
      var attributes = {};
      attributes[attributeName] = attributeValue;
      setFixtures(sandbox(attributes));
    });

    describe("when only attribute name is provided", function() {
      it("should pass if element has matching attribute", function() {
        expect(findSandbox()).toHaveAttr(attributeName);
      });

      it("should pass negated if element has no matching attribute", function() {
        expect(findSandbox()).not.toHaveAttr(wrongAttributeName);
      });
    });

    describe("when both attribute name and value are provided", function() {
      it("should pass if element has matching attribute with matching value", function() {
        expect(findSandbox()).toHaveAttr(attributeName, attributeValue);
      });

      it("should pass negated if element has matching attribute but with wrong value", function() {
        expect(findSandbox()).not.toHaveAttr(attributeName, wrongAttributeValue);
      });

      it("should pass negated if element has no matching attribute", function() {
        expect(findSandbox()).not.toHaveAttr(wrongAttributeName, attributeValue);
      });
    });
  });

  describe("toHaveId", function() {
    beforeEach(function() {
      setFixtures(sandbox());
    });

    it("should pass if id attribute matches expectation", function() {
      expect(findSandbox()).toHaveId('sandbox');
    });

    it("should pass negated if id attribute does not match expectation", function() {
      expect(findSandbox()).not.toHaveId('wrongId');
    });

    it("should pass negated if id attribute is not present", function() {
      var div= document.createElement('div');
      expect(div).not.toHaveId('sandbox');
    });
  });

  describe("toHaveHtml", function() {
    var html = '<div>some text</div>';
    var wrongHtml = '<span>some text</span>';
    var element;

    beforeEach(function() {
      element= document.createElement('div');
      element.innerHTML= html;
    });

    it("should pass when html matches", function() {
      expect(element).toHaveHtml(html);
    });

    it("should pass negated when html does not match", function() {
      expect(element).not.toHaveHtml(wrongHtml);
    });
  });

  describe("toHaveText", function() {
    var text = 'some text';
    var wrongText = 'some other text';
    var element;

    beforeEach(function() {
      element= document.createElement('div');
      element.appendChild(document.createTextNode(text));
    });

    it("should pass when text matches", function() {
      expect(element).toHaveText(text);
    });

    it("should pass negated when text does not match", function() {
      expect(element).not.toHaveText(wrongText);
    });
  });

  describe("toHaveValue", function() {
    var value = 'some value';
    var differentValue = 'different value';

    beforeEach(function() {
      var node= document.createElement('input');
      node.id= 'sandbox';
      node.setAttribute('value', value);
      setFixtures(node);
    });

    it("should pass if value matches expectation", function() {
      expect(findSandbox()).toHaveValue(value);
    });

    it("should pass negated if value does not match expectation", function() {
      expect(findSandbox()).not.toHaveValue(differentValue);
    });

    it("should pass negated if value attribute is not present", function() {
      expect(sandbox()).not.toHaveValue(value);
    });
  });

  // describe("toHaveData", function() {
  //   var key = 'some key';
  //   var value = 'some value';
  //   var wrongKey = 'wrong key';
  //   var wrongValue = 'wrong value';
  // 
  //   beforeEach(function() {
  //     setFixtures(sandbox().data(key, value));
  //   });
  // 
  //   describe("when only key is provided", function() {
  //     it("should pass if element has matching data key", function() {
  //       expect(findSandbox()).toHaveData(key);
  //     });
  // 
  //     it("should pass negated if element has no matching data key", function() {
  //       expect(findSandbox()).not.toHaveData(wrongKey);
  //     });
  //   });
  // 
  //   describe("when both key and value are provided", function() {
  //     it("should pass if element has matching key with matching value", function() {
  //       expect(findSandbox()).toHaveData(key, value);
  //     });
  // 
  //     it("should pass negated if element has matching key but with wrong value", function() {
  //       expect(findSandbox()).not.toHaveData(key, wrongValue);
  //     });
  // 
  //     it("should pass negated if element has no matching key", function() {
  //       expect(findSandbox()).not.toHaveData(wrongKey, value);
  //     });
  //   });
  // });

  describe("toBeVisible", function() {
    it("should pass on visible element", function() {
      setFixtures(sandbox());
      expect(findSandbox()).toBeVisible();
    });

    it("should pass negated on hidden element", function() {
      expect(findSandbox()).not.toBeVisible();
    });
  });

  describe("toBeHidden", function() {
    it("should pass on hidden element", function() {
      var node= sandbox();
      node.style.display='none';
      setFixtures(node);
      expect(findSandbox()).toBeHidden();
    });

    it("should pass negated on visible element", function() {
      setFixtures(sandbox());
      expect(findSandbox()).not.toBeHidden();
    });
  });

  describe("toBeSelected", function() {
    beforeEach(function() {
      setFixtures('\
        <select>\n\
          <option id="not-selected"></option>\n\
          <option id="selected" selected="selected"></option>\n\
        </select>');
    });

    it("should pass on selected element", function() {
      expect(findNode('selected')).toBeSelected();
    });

    it("should pass negated on not selected element", function() {
      expect(findNode('not-selected')).not.toBeSelected();
    });
  });

  describe("toBeChecked", function() {
    beforeEach(function() {
      setFixtures('\
        <input type="checkbox" id="checked" checked="checked" />\n\
        <input type="checkbox" id="not-checked" />');
    });

    it("should pass on checked element", function() {
      expect(findNode('checked')).toBeChecked();
    });

    it("should pass negated on not checked element", function() {
      expect(findNode('not-checked')).not.toBeChecked();
    });
  });

  describe("toBeEmpty", function() {
    it("should pass on empty element", function() {
      setFixtures(sandbox());
      expect(findSandbox()).toBeEmpty();
    });

    it("should pass negated on element with a tag inside", function() {
      var node= sandbox().innerHTML="<span></span>";
      setFixtures(node);
      expect(findSandbox()).not.toBeEmpty();
    });

    it("should pass negated on element with text inside", function() {
      var node= sandbox();
      node.appendChild(document.createTextNode("Some text"));
      setFixtures(node);
      expect(findSandbox()).not.toBeEmpty();
    });
  });

  describe("toExist", function() {
    it("should pass on visible element", function() {
      setFixtures(sandbox());
      expect(findSandbox()).toExist();
    });

    it("should pass on hidden element", function() {
      var node= sandbox();
      node.style.display='none';
      setFixtures(node);
      expect(findSandbox()).toExist();
    });

    it("should pass negated if element is not present in DOM", function() {
      expect(findNode('non-existent-element')).not.toExist();
    });
  });

  describe("toMatchSelector", function() {
    beforeEach(function() {
      setFixtures(sandbox());
    });

    it("should pass if object matches selector", function() {
      expect(findSandbox()).toMatchSelector('#sandbox');
    });

    it("should pass negated if object does not match selector", function() {
      expect(findSandbox()).not.toMatchSelector('#wrong-id');
    });
  });

  describe("toContain", function() {
    beforeEach(function() {
      var node= sandbox();
      node.innerHTML="<span></span>";
      setFixtures(node);
    });

    it("should pass if object contains selector", function() {
      expect(findSandbox()).toContain('span');
    });

    it("should pass negated if object does not contain selector", function() {
      expect(findSandbox()).not.toContain('div');
    });
  });
});