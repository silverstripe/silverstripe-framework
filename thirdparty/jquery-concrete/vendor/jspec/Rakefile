
require 'rubygems'
require 'rake'
require 'echoe'

def version
  $1 if File.read('lib/jspec.js').match /version *: *'(.*?)'/
end

Echoe.new "jspec", version do |p|
  p.author = "TJ Holowaychuk"
  p.email = "tj@vision-media.ca"
  p.summary = "JavaScript BDD Testing Framework"
  p.url = "http://visionmedia.github.com/jspec"
  p.runtime_dependencies << "sinatra"
  p.runtime_dependencies << "json_pure"
  p.runtime_dependencies << "commander >=4.0.0"
  p.runtime_dependencies << "bind >=0.2.8"
end

namespace :pkg do
  desc 'Build package'
  task :build => ['pkg:clear'] do
    begin
      sh 'mkdir pkg'
      sh 'cp -fr lib/* pkg'
      minify 'lib/jspec.js', 'pkg/jspec.min.js'
      minify 'lib/jspec.jquery.js', 'pkg/jspec.jquery.min.js'
      compress 'lib/jspec.css', 'pkg/jspec.min.css'
      sh 'git add pkg/.'
    rescue Exception => e
      puts "Failed to package: #{e}."
    else 
      puts "Packaging of JSpec-#{version} completed."
    end
  end
  
  desc 'Clear packaging'
  task :clear do
    if File.directory? 'pkg'
      sh 'rm -fr pkg/*'
      sh 'rmdir pkg'
    end
  end
  
  desc 'Display compression savings of last release'
  task :savings do
    totals = Hash.new { |h, k|  h[k] = 0 }
    format = '%-20s : %0.3f kb'
    totals = %w( pkg/jspec.min.js pkg/jspec.jquery.min.js pkg/jspec.min.css ).inject totals do |total, file|
      uncompressed = File.size(file.sub('.min', '')).to_f / 1024
      compressed = File.size(file).to_f / 1024
      saved = uncompressed - compressed
      puts format % [file.sub('pkg/', ''), saved]
      totals[:saved] += saved
      totals[:uncompressed] += uncompressed
      totals[:compressed] += compressed
      totals
    end
    puts
    puts format % ['total uncompressed', totals[:uncompressed]]
    puts format % ['total compressed', totals[:compressed]]
    puts format % ['total saved', totals[:saved]]
  end
end

def minify from, to
  sh "jsmin < #{from} > #{to}"
end

def compress from, to
  File.open(to, 'w+') do |file|
    file.write File.read(from).gsub(/(^[\t ]*)|\n/, '')
  end
end