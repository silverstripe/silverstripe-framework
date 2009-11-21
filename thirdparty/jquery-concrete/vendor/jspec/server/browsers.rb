
module JSpec
  class Browser
    def open url
      `open -g -a #{name} #{url}`
    end
    
    def name
      self.class.to_s.split('::').last
    end
    
    class Firefox < self; end
    class Safari < self; end
    class Opera < self; end
  end
end