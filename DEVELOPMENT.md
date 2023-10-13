# Development hints

## Setting up a VM on Linux using KVM/libvirt

For development and testing it is useful to set up a VM, e.g. on a
Linux desktop machine. In principle this should also work on Windows
but a different type of virtualization software will have to be used
(e.g. VirtualBox).

For the basic VM installation on Debian, refer e.g. to [here](https://wiki.debian.org/KVM):

```
# Install required packages
sudo apt install qemu-system libvirt-daemon-system gir1.2-spiceclientgtk-3.0

# Add user to  group 'libvirt'
adduser <youruser> libvirt

# Download Debian iso and install the VM
# Use e.g. 'palma' as a hostname
virt-install --virt-type kvm --name palma \
--cdrom ~/Downloads/debian-11.4.0-amd64-netinst.iso \
--os-variant debian10 \
--disk size=10 --memory 1000

# Configure networking (see [1] below)

# Autostart the system-wide default network
sudo virsh net-autostart --network default
sudo virsh net-start --network default

# Create /etc/qemu/bridge.conf
sudo mkdir -p /etc/qemu
echo "allow virbr0" | sudo tee /etc/qemu/bridge.conf

# Enable SUID on qemu-bridge-helper
chmod u+s /usr/lib/qemu/qemu-bridge-helper

# Change VM settings to use bridged networking with virbr0, e.g. using
# virt-manager

# (Re)Start the VM and check the IP in the guest using
ip a
# or on the host using (see [2] below)
sudo virsh net-list
sudo virsh net-info default
sudo virsh net-dhcp-leases default

# Add a corresponding entry to /etc/hosts on the host.
# The name has to be identical to the hostname chosen for
# the guest VM.
192.168.xxx.xxx palma

# Download palma debian packages from [3] using wget and install
sudo dpkg -i *.deb
sudo apt-get -f install

# Customize theme and configuration to your liking. Done!
# Open http://palma in your web browser.
```

## Links

* [1]: [Fix qemu-bridge-helper](https://mike42.me/blog/2019-08-how-to-use-the-qemu-bridge-helper-on-debian-10)
* [2]: [Find IP address of VM guest](https://www.cyberciti.biz/faq/find-ip-address-of-linux-kvm-guest-virtual-machine/)
* [3]: [PalMA releases](https://github.com/UB-Mannheim/PalMA/releases)

## Notes / References

### File upload (general)

* http://www.php.net/manual/en/features.file-upload.post-method.php

### File upload with dropzone
* http://www.dropzonejs.com/
* http://www.startutorial.com/articles/view/how-to-build-a-file-upload-form-using-dropzonejs-and-php
* http://maxoffsky.com/code-blog/howto-ajax-multiple-file-upload-in-laravel/

### Websockets
* https://en.wikipedia.org/wiki/Server-sent_events
* https://developer.mozilla.org/en-US/docs/WebSockets/Writing_WebSocket_client_applications
* http://code.google.com/p/phpwebsocket/
* http://dharman.eu/?menu=phpWebSocketsTutorial

### Keyboard input
* http://jsfiddle.net/angusgrant/E3tE6/
* http://stackoverflow.com/questions/3181648/how-can-i-handle-arrowkeys-and-greater-than-in-a-javascript-function-which
* http://stackoverflow.com/questions/5597060/detecting-arrow-key-presses-in-javascript
* http://www.quirksmode.org/js/keys.html

### Key symbols
* http://www.tcl.tk/man/tcl8.4/TkCmd/keysyms.htm

### Authorization
* http://aktuell.de.selfhtml.org/artikel/php/loginsystem/

### Overlays
* http://answers.oreilly.com/topic/1823-adding-a-page-overlay-in-javascript/

### Misc

* wmctrl, suckless-tools (lsw, sprop, wmname, ...)
* display.im6, evince
