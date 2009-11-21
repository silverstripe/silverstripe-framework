
require 'rubygems'
require 'rack'
require 'server/browsers'

module JSpec
  class Server
    attr_reader :responses, :browsers, :root
    
    def initialize options = {}
      @responses = []
      @browsers = options.delete :browsers
      @root = options.delete :root
    end
    
    def call env
      request = Rack::Request.new env
      path = request.path_info
      body = case path
      when '/'
        agent = env['HTTP_USER_AGENT']
        responses << browser(agent)
        display_results browser(agent), request['failures'], request['passes']
        type = 'text/plain'
        'close'
      when /jspec/
        type = 'application/javascript'
        File.read File.join(JSPEC_ROOT, 'lib', File.basename(path))
      else
        type = Rack::Mime.mime_type File.extname(path)
        File.read File.join(root, path) rescue ''
      end
      [200, { 'Content-Type' => type, 'Content-Length' => body.length.to_s }, body]
    end

    def display_results browser, failures, passes
      puts '%-14s - passes: %s failures: %s' % [bold(browser), green(passes), red(failures)]
    end

    def browser string
      case string
      when /Safari/  ; :Safari
      when /Firefox/ ; :Firefox
      when /MSIE/    ; :MSIE
      when /Opera/   ; :Opera
      end
    end

    def bold string
      color string, 1
    end

    def red string
      color string, 31
    end

    def green string
      color string, 32
    end

    def color string, code
      "\e[#{code}m#{string}\e[m"
    end
    
    def when_finished &block
      Thread.new {
        sleep 0.1 while responses.length < browsers.length
        yield
      }
    end
    
    def self.start options, spec
      app = Rack::Builder.new do
        server = JSpec::Server.new :browsers => options.browsers, :root => '.'
        server.when_finished { exit }
        run server
      end
      unless options.server_only
        Thread.new { 
          sleep 2
          puts "Running browsers: #{options.browsers.join(', ')}\n\n"
          run_browsers options.browsers, spec
        }
      end
      puts "JSpec server started\n"
      Rack::Handler::Mongrel.run app, :Port => 4444
      self
    end
    
    def self.run_browsers browsers, spec
      browsers.each do |name|
        browser(name).open "http://localhost:4444/#{spec}"
      end
    end
    
    def self.browser name
      eval("JSpec::Browser::#{name}").new
    end
  end
end