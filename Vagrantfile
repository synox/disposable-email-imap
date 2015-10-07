# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"
Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "avenuefactory/lamp"
  config.vm.network "private_network", ip: "192.168.10.15"

  config.vm.synced_folder "./", "/var/www/html", id: "vagrant-root",
    owner: "vagrant",
    group: "www-data",
    mount_options: ["dmode=775,fmode=664"]

    config.vm.provision "shell", inline: <<-SHELL
      echo updating...    && sudo apt-get -qq update
      echo installing...  &&  sudo apt-get -qq  -y install php5-imap
      sudo service apache2 restart
    SHELL


end
