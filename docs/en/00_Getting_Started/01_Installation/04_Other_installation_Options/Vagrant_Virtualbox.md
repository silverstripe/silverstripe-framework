# Install SilverStripe using Vagrant
This guide steps through installing a virtual machine on your chosen virtual platform.

This guide will work for:
- Virtualbox
- Parallels

This guide may work for:
- VMWare

Vagrant has all server settings stored in a Vagrantfile which is a text file containing the commands and settings needed to setup a webserver. This is ideal for version control, which helps distribution and sharing between team members.

## Requirements
This can be used with many modern computers, this can be done on Windows, Mac or Linux operating systems. We recommend a relatively strong computer that can handle a virtual machine in the background.
- Minimum memory would be 4GB of RAM
- Roughly 20GB Harddrive space (depends on which box you choose to use)
- Software to install beforehand:
  - [Vagrant](http://www.vagrantup.com/downloads)
  - [Virtualbox](https://www.virtualbox.org/wiki/Downloads)
  - Installing SilverStripe by [Composer](https://getcomposer.org/download/) or download the [ZIP file](https://www.silverstripe.org/download/).

*Important*: It is strongly advised to have a fast and wired internet connection when running initial the setup, as there is a lot of downloading required.
Vagrant downloads and sets up an entire operating system.

## Setting it up
Most of this requires using only the command line and text editor or IDE.

Create a folder where your vagrant will be in and browse to the folder in the command line:
```bash
mkdir virtuallythere
cd virtuallythere
```

### Creating the Vagrantfile
Create/Browse to the folder you’ll be developing in:
```bash
vagrant init
```

In its current state, you could start the vagrant machine and it will run, but you won't be able to do much with it yet.

### Setting the box
Open the `Vagrantfile` that was created in your vagrant folder with your preferred text editor.

Look for the line which describes the box you are going to use:
```ruby
config.vm.box = "base"
```

This defines what pre-built Operating System the vagrant machine will be using. We'll be changing `base` to something closer to what we’d like, perhaps similar to your production server, you can find a range of boxes [listed here](https://atlas.hashicorp.com/search)

We've chosen to use `RHEL7.0`, but you can easily change it to suit your needs.
```ruby
config.vm.box = "box-cutter/centos70"
```

*Important*: Because this is redhat, the shell commands used later on will be using `yum install` instead of `apt-get install` for Debian based boxes.

### The private network and hostname
Now we’ll add the vagrant machine to our computer’s private network, this will mean no one outside this computer will be able to access it without some special setup.
So this will be your own development environment!

To do that, look for this line:
```ruby
# config.vm.network "private_network", ip: "192.168.33.10"
```

First we’ll need to uncomment it, so delete only the `#` at the start of the line, then add a hostname IP address of your choice to use.
```ruby
config.vm.hostname = "virtuallythere.dev"
config.vm.network "privatenetwork", ip: "10.1.2.50"
```
### Syncing files
Next we’ll sync our website folder to the virtual machine, so it has the files needed to run SilverStripe. There are many different ways to do this, depending on your own preferences and possibly different boxes.

To keep things simple, we’re going to sync our vagrant folder to the virtual machine, so everything in your vagrant folder will be visible to the virtual machine.

Find this line:
```ruby
config.vm.synced_folder "../data", "/vagrant_data"
```

Then change to match this:
```ruby
config.vm.synced_folder ".", "/vagrant"
```

### Setting resources
This step is optional, but it is recommended to configure the virtual machine resources allocated to it, so it doesn’t take more resources than it should, something like this should be enough to start with:
```ruby
config.vm.provider "virtualbox" do |vb|
    vb.memory = "1024"
    vb.name = "virtuallythere"
end
```

*Important*: This is for Virtualbox again, change “virtualbox” to the virtual platform that you are using, you might need to make sure the setting `vb.memory` is supported by the platform you’re using because it may be different.

### Script to setup server
Now we need to setup our environment using shell scripts, this will install software that you need for your server to be working and usable. You could even customise the setup to be closer like your production server.

For now find these lines:
```ruby
# config.vm.provision "shell", inline: <<-SHELL
#   sudo apt-get update
#   sudo apt-get install -y apache2
# SHELL
```
And modify it to call a shell script in your vagrant folder:
```ruby
config.vm.provision "shell", path: "setup.sh"
```

*Important*: We’re using shell script because we’re using a Linux server, please use the scripting language that your server environment supports.

Now to create the `setup.sh` file. This script will setup `php+modules`, `mariadb/mysql` and `apache`, the ones I had listed is the minimal required to get SilverStripe started and working out of the box.
```bash
yum update -y --disableplugin=fastestmirror
systemctl restart sshd

yum install -y httpd httpd-devel mod_ssl
yum -y install php php-common php-mysql php-pdo php-mcrypt* php-gd php-xml php-mbstring
echo "Include /vagrant/apache/*.conf" >> /etc/httpd/conf/httpd.conf
echo "date.timezone = Pacific/Auckland" >> /etc/php.ini
systemctl start httpd.service
systemctl enable httpd.service

yum install -y mariadb-server mariadb
systemctl start mariadb.service
systemctl enable mariadb.service
```

*Important*: Again, as noted above, this uses RHEL so `yum install` is used, please remember to change to `apt-get install` or other packaging tool as necessary.

Save `setup.sh` in the same folder as your Vagrantfile.

### Setting up Apache
If you inspect the script I’ve included above, you’ll notice this line:
```bash
echo "Include /vagrant/apache/*.conf" >> /etc/httpd/conf/httpd.conf
```
This will allow us to customise our apache, particularly the VirtualHost part

Earlier in the post, I had defined a hostname:
```ruby
config.vm.hostname = "virtuallythere.dev"
```
We’ll need to create a conf file for this hostname in a apache folder, create the folder first:
```bash
mkdir apache
```

We'll save a `vagrant.conf` file in the newly created apache folder, and inside we’ll define the VirtualHost:
```apache
ServerRoot "/etc/httpd"

<Directory />
    AllowOverride none
    Require all denied
</Directory>

DocumentRoot "/vagrant/public"

<Directory "/vagrant/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<VirtualHost *:80>
  ServerName virtuallythere.dev
  ServerAlias www.virtuallythere.dev
  DocumentRoot /vagrant/public
  LogLevel warn
  ServerSignature Off

  <Directory /vagrant/public>
    Options +FollowSymLinks
    Options -ExecCGI -Includes -Indexes
    AllowOverride all
    Require all granted
  </Directory>

  # SilverStripe specific
  <LocationMatch assets/>
    php_flag engine off
  </LocationMatch>
</VirtualHost>
```

### Download SilverStripe
Last step is to download SilverStripe for the virtual machine to use, if you have an existing SilverStripe installation you could also move everything to this folder instead of downloading a new installation to the public folder.
As mentioned above, you could install SilverStripe by [Composer](https://getcomposer.org/download/) or download the [ZIP file](https://www.silverstripe.org/download/).

### We’re ready for launch
That’s all! When that’s done, run:
```bash
vagrant up
```


If you've been following this guide, you can browse to http://virtuallythere.dev/install if you are using a new installation, or go to http://virtuallythere.dev if you are using an existing SilverStripe installation. If you've modified the hostname, follow the new hostname you've chosen.

### Last step
Version control this and share it with your teammates!

All they need is listed in the Requirements at the top, and the files you’ve just created:
- Vagrantfile
- setup.sh
- apache/virtuallythere.conf
- public/ _(with SilverStripe files here)_

## Advanced tasks
Once you get started with Vagrant, it’s very easy to improve and tweak things further if you needed. With version control, if you've made a mistake, you can easily rollback to the last working version.

Some examples of things you could do:
- You could have multiple hostnames for the same virtual machine by using the plugin [vagrant-hostsupdater](https://github.com/cogitatio/vagrant-hostsupdater).
- Have [multiple machines](https://docs.vagrantup.com/v2/multi-machine/) running, if you wanted to test communications between two servers
- Perhaps you have access to API server code you could host locally for development.
- [Vagrant Push](https://docs.vagrantup.com/v2/push/index.html) could be used to deploy to a Testing server.

