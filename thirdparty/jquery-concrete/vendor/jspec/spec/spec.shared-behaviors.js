
describe 'Shared Behaviors'
  describe 'User'
    before
      User = function(name) { this.name = name }
      user = new User('joe')
    end
    
    it 'should have a name'
      user.should.have_property 'name'
    end
    
    describe 'Administrator'
      should_behave_like('User')

      before
        Admin = function(name) { this.name = name }
        Admin.prototype.may = function(perm){ return true }
        user = new Admin('tj')
      end

      it 'should have access to all permissions'
        user.may('edit pages').should.be_true
        user.may('delete users').should.be_true
      end

      describe 'Super Administrator'
        should_behave_like('Administrator')

        before
          SuperAdmin = function(name) { this.name = name }
          SuperAdmin.prototype.may = function(perm){ return true }
          user = new SuperAdmin('tj')
        end
      end
    end
  end
  
  describe 'User with toString()'
    before
      user = { toString : function() { return '<User tj>' }}
    end
    
    it 'should return &lt;User NAME&gt;'
      user.toString().should.match(/\<User/)
    end
  end
  
  describe 'Manager'
    should_behave_like('User')
    should_behave_like('User with toString()')
    
    before
      Manager = function(name) { this.name = name }
      Manager.prototype.may = function(perm){ return perm == 'hire' || perm == 'fire' }
      Manager.prototype.toString = function(){ return '<User ' + this.name + '>' }
      user = new Manager('tj')
    end
    
    it 'should have access to hire or fire employees'
      user.may('hire').should.be_true
      user.may('fire').should.be_true
      user.may('do anything else').should.be_false
    end
  end
  
  describe 'findSuite'
    it 'should find a suite by full description'
      JSpec.findSuite('Shared Behaviors User Administrator').should.be_a JSpec.Suite
    end
    
    it 'should find a suite by name'
      JSpec.findSuite('User').should.be_a JSpec.Suite
    end
    
    it 'should return null when not found'
      JSpec.findSuite('Rawr').should.be_null
    end
  end
end