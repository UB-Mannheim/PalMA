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
