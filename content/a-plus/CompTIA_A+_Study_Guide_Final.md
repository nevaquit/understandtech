# Study Notes for the CompTIA A+ Exam

## "The Red Study Guide"

**CompTIA A+ 220-1101 & 220-1102**

---

**About This Guide**

This comprehensive study guide covers all exam objectives for both CompTIA A+ Core 1 (220-1101) and Core 2 (220-1102) certification exams. The content is organized by domain and objective to help you systematically prepare for the certification.

**Exam Overview**

To earn the CompTIA A+ certification, you must pass two exams:

- **Core 1 (220-1101)**: Focuses on mobile devices, networking, hardware, virtualization, and hardware/network troubleshooting

- **Core 2 (220-1102)**: Focuses on operating systems, security, software troubleshooting, and operational procedures

**How to Use This Guide**

Each domain is broken down by specific exam objectives. Key concepts are highlighted, and exam tips are provided throughout to help you focus on the most important information for test day.

---

## CORE 1 (220-1101)

---

## Domain 1.0: Mobile Devices (13% of exam)

### 1.1 Given a scenario, monitor mobile device hardware and use appropriate replacement techniques.

**Battery Replacement**

Most modern mobile devices use **Lithium-ion (Li-ion)** or **Lithium-ion Polymer (LiPo)** batteries, which offer high energy density and no memory effect. Battery replacement procedures vary significantly by device type. Some laptops feature **modular batteries** that can be swapped by releasing a latch mechanism, while others require complete disassembly. Internal battery replacement typically involves removing screws, carefully prying open the chassis, disconnecting a small connector from the motherboard, and sometimes dealing with adhesives that secure the battery in place.

**Keyboard and Keys**

Laptop keyboards are typically a single integrated unit connected to the motherboard via a delicate ribbon cable. Replacement usually requires removing the top case or prying the keyboard assembly from the front. Individual **keycaps** are extremely fragile and use a scissor-switch or butterfly mechanism underneath. When replacing keycaps, extreme care must be taken to avoid breaking the underlying plastic components. Always consult manufacturer documentation before attempting keycap replacement.

**RAM (Random-Access Memory)**

Laptops and compact devices use **SO-DIMM (Small Outline Dual In-line Memory Module)** form factor memory. Many laptops provide an access panel on the bottom for easy RAM upgrades, making this one of the simplest upgrade paths. However, ultra-thin and ultra-portable models increasingly use RAM that is **soldered directly to the motherboard**, making upgrades impossible. Always verify upgrade capability before purchasing additional memory.

**Storage Devices**

**Hard Disk Drives (HDDs)** use spinning magnetic platters and are available in a **2.5-inch** form factor for laptops. **Solid-State Drives (SSDs)** use flash memory with no moving parts, offering superior speed, durability, and power efficiency. The **2.5-inch SATA SSD** uses the same form factor and connector as traditional laptop HDDs, providing an easy upgrade path. The **M.2 form factor** is a smaller, stick-like design that connects directly to the motherboard and can use either the SATA protocol or the much faster **NVMe (Non-Volatile Memory Express)** protocol over PCIe.

**Wireless and Cellular Cards**

Mobile devices use various wireless technologies including Wi-Fi, Bluetooth, and cellular (WWAN) connectivity. Wireless cards come in **Mini-PCIe** (older standard) and **M.2** (newer standard) form factors. These cards connect to antennas via tiny, fragile wires (typically black and white) that route through the device chassis, usually through the display bezel. During replacement, these antenna connections must be carefully reconnected to ensure proper signal strength.

**Physical Privacy and Security Components**

**Biometric authentication** includes fingerprint scanners (capacitive, optical, or ultrasonic sensors) and facial recognition systems (using IR cameras and depth sensing for technologies like Windows Hello). **Near-Field Communication (NFC)** enables short-range wireless communication (less than 4 inches) for mobile payments, quick device pairing, and reading information tags. NFC operates at 13.56 MHz and is integrated into many modern smartphones and tablets.

> **Exam Tip:** Be prepared for scenario questions requiring you to identify the correct hardware component for a specific mobile device situation. Understanding that M.2 slots can support both SATA and NVMe drives, but that NVMe offers significantly faster performance, is a critical distinction.

### 1.2 Compare and contrast accessories and connectivity options for mobile devices.

**Connection Methods**

**USB (Universal Serial Bus)** comes in several variants. **USB-C** is a reversible, oval-shaped connector supporting high-speed data transfer, video output via DisplayPort Alternate Mode, and power delivery up to 100W. **Micro-USB and Mini-USB** are older, non-reversible standards still found on legacy devices. **Lightning** is Apple's proprietary reversible 8-pin connector used on older iPhones and some iPads.

**Near-Field Communication (NFC)** enables tap-to-pay transactions, quick device pairing, and reading information from NFC tags. **Bluetooth** is a wireless standard for creating Personal Area Networks (PANs) to connect peripherals like headsets, keyboards, mice, and speakers over short distances. **Tethering and Mobile Hotspot** functionality allows sharing a device's cellular internet connection with other devices via Wi-Fi, Bluetooth, or USB.

**Accessories**

A **docking station** provides multiple ports (USB, video, network, power) and allows a laptop to connect to a complete desktop setup with a single cable connection. Docking stations are often proprietary and designed for specific laptop models. A **port replicator** is a simpler, more generic device that provides additional USB ports and sometimes video outputs, typically smaller and more portable than a full docking station. **Trackpads and drawing pads** serve as external input devices for cursor control or digital artwork creation.

> **Pro Tip:** The key difference between a docking station and a port replicator is that a true docking station often integrates deeply with a specific laptop model, providing additional power delivery and advanced features, while a port replicator is usually a more generic USB-based expansion device.

### 1.3 Given a scenario, configure basic mobile device network connectivity and provide application support.

**Network Connectivity Configuration**

**Wireless and cellular data** can be enabled or disabled in device settings. Mobile devices use a **SIM (Subscriber Identity Module)** or **eSIM (embedded SIM)** to authenticate and connect to cellular networks. Network options include 3G, 4G, 5G, and Wi-Fi connectivity.

**Bluetooth pairing** requires enabling Bluetooth in settings, making the device discoverable, and selecting it from the available devices list on the connecting device. A PIN code may be required to confirm and secure the connection.

**Location Services** utilize **GPS (Global Positioning System)** for accurate location data, along with cellular tower triangulation and Wi-Fi positioning. Location services can be enabled or disabled globally or configured on a per-application basis for privacy control.

**Application Support**

**Mobile Device Management (MDM)** platforms provide centralized management and security for corporate-owned or employee-owned (BYOD - Bring Your Own Device) mobile devices. MDM solutions can enforce security policies, deploy and manage applications, configure device settings, and remotely wipe devices if lost or stolen. Common configurations include **Corporate** (company-owned and managed) and **BYOD** (employee-owned with corporate access).

**Mobile Device Synchronization** ensures data consistency across devices and cloud services. Common synchronization targets include contacts, email, calendar events, photos, and cloud storage (iCloud, Google Drive, OneDrive). Synchronization can occur automatically over Wi-Fi or cellular data, with options to recognize data caps and limit background synchronization.

---

## Domain 2.0: Networking (23% of exam)

### 2.1 Compare and contrast TCP and UDP ports, protocols, and their purposes.

| Protocol | Port(s) | TCP/UDP | Purpose & Description |
| --- | --- | --- | --- |
| **FTP** | 20, 21 | TCP | **File Transfer Protocol**: Transfers files between systems. Port 21 handles control/command, Port 20 handles data transfer. Unencrypted and insecure. |
| **SSH** | 22 | TCP | **Secure Shell**: Provides secure, encrypted remote command-line access and file transfer (SFTP/SCP). Modern replacement for Telnet. |
| **Telnet** | 23 | TCP | **Telecommunication Network**: Provides insecure, unencrypted remote command-line access. Legacy protocol, avoid using. |
| **SMTP** | 25 | TCP | **Simple Mail Transfer Protocol**: Sends email from client to server or between mail servers. Outbound mail only. |
| **DNS** | 53 | UDP/TCP | **Domain Name System**: Resolves domain names to IP addresses. Uses UDP for standard queries, TCP for zone transfers and large responses. |
| **DHCP** | 67, 68 | UDP | **Dynamic Host Configuration Protocol**: Automatically assigns IP addresses and network configuration. Port 67 for server, 68 for client. |
| **HTTP** | 80 | TCP | **Hypertext Transfer Protocol**: Fundamental protocol for unencrypted web traffic. No security or encryption. |
| **HTTPS** | 443 | TCP | **Hypertext Transfer Protocol Secure**: Encrypts web traffic using TLS/SSL for confidentiality, integrity, and authentication. |
| **POP3** | 110 | TCP | **Post Office Protocol v3**: Retrieves email from server, typically downloading and deleting the server copy. Single-device access. |
| **IMAP** | 143 | TCP | **Internet Message Access Protocol**: Retrieves and manages email on server. Supports multi-device synchronization. |
| **SMB/CIFS** | 445 | TCP | **Server Message Block**: Windows file and printer sharing protocol. Port 139 used for legacy NetBIOS. |
| **RDP** | 3389 | TCP | **Remote Desktop Protocol**: Provides graphical remote access to Windows computers. |
| **LDAP** | 389 | TCP | **Lightweight Directory Access Protocol**: Accesses directory services like Active Directory for authentication and user information. |

**TCP vs. UDP Comparison**

**TCP (Transmission Control Protocol)** is connection-oriented and reliable. It establishes a formal connection using a three-way handshake (SYN, SYN-ACK, ACK), guarantees data arrives in the correct order, provides error checking, and retransmits lost packets. TCP is used for applications where accuracy is critical, such as web browsing, email, and file transfers.

**UDP (User Datagram Protocol)** is connectionless and unreliable. It sends data without establishing a connection or verifying receipt. UDP has minimal overhead and is much faster than TCP, making it ideal for real-time applications like DNS queries, VoIP, video streaming, and online gaming where speed is more important than perfect accuracy.

### 2.2 Explain wireless networking technologies.

**Wireless Frequencies**

Wi-Fi operates on multiple frequency bands. The **2.4 GHz band** provides longer range but experiences more interference from devices like microwaves and cordless phones. The **5 GHz band** offers shorter range but provides less interference, more available channels, and faster speeds. The **6 GHz band** was introduced with Wi-Fi 6E, providing even more channels and less congestion.

**802.11 Standards**

The 802.11 family defines specifications for wireless local area networks (WLANs). **802.11ac (Wi-Fi 5)** operates on the 5 GHz band and provides speeds up to several gigabits per second. **802.11ax (Wi-Fi 6 and Wi-Fi 6E)** operates on 2.4 GHz, 5 GHz, and 6 GHz bands, offering higher efficiency in congested environments, improved battery life for connected devices, and faster maximum speeds.

**Other Wireless Technologies**

**Bluetooth** creates short-range Personal Area Networks (PANs) for connecting peripherals like headphones, keyboards, and mice. **NFC (Near-Field Communication)** operates at very short range (less than 4 inches) for mobile payments and device pairing. **RFID (Radio-Frequency Identification)** uses radio waves to read information stored on tags, commonly used for inventory tracking, access badges, and asset management.

### 2.3 Summarize services provided by networked hosts.

**Server Roles**

Network servers provide essential services to clients. **Web servers** host websites using software like Apache, Nginx, or IIS. **File servers** store and manage files for network users using protocols like SMB or NFS. **Print servers** manage print jobs and network printers. **DHCP servers** automatically assign IP addresses to network clients. **DNS servers** resolve domain names to IP addresses. **Proxy servers** act as intermediaries for client requests, providing caching, content filtering, and anonymity. **Mail servers** handle sending and receiving email. **Authentication servers** manage user credentials and access control using systems like Active Directory or RADIUS.

**Internet Appliances**

**UTM (Unified Threat Management)** devices combine multiple security functions into a single appliance, including firewall, intrusion detection/prevention (IDS/IPS), antivirus, anti-spam, and content filtering. **Load balancers** distribute network traffic across multiple servers to prevent any single server from becoming overwhelmed, improving performance and availability.

**Legacy and Embedded Systems**

**SCADA (Supervisory Control and Data Acquisition)** systems manage critical infrastructure like power plants and manufacturing facilities. These systems often use legacy protocols and require special security considerations due to their inability to patch or update.

### 2.4 Explain common network configuration concepts.

**DNS Records**

DNS uses various record types to map names to resources. **A records** map hostnames to IPv4 addresses. **AAAA records** map hostnames to IPv6 addresses. **CNAME records** create aliases for hostnames. **MX records** specify mail servers for a domain. **TXT records** provide text information, often used for domain verification, SPF (Sender Policy Framework), and DKIM (DomainKeys Identified Mail) for email security.

**DHCP Configuration**

DHCP provides automatic IP configuration through a four-step process (DORA: Discover, Offer, Request, Acknowledge). A DHCP **scope** defines the range of IP addresses available for lease. **Reservations** ensure specific devices always receive the same IP address based on their MAC address. **Leases** define how long a client can use an assigned IP address before renewal is required.

**VLANs and VPNs**

A **VLAN (Virtual LAN)** creates a logical grouping of devices on a network, allowing them to communicate as if connected to the same physical wire regardless of physical location. VLANs segment networks for improved security and performance. A **VPN (Virtual Private Network)** creates a secure, encrypted tunnel over a public network to connect remote users or offices to a private network.

### 2.5 Compare and contrast common networking hardware devices.

**Core Network Devices**

**Routers** operate at Layer 3 (Network Layer) of the OSI model and forward data packets between different networks based on IP addresses. **Switches** operate at Layer 2 (Data Link Layer) and connect devices within a network using MAC addresses. **Managed switches** offer advanced features like VLANs, Quality of Service (QoS), and port monitoring, while **unmanaged switches** are simple plug-and-play devices with no configuration options.

**Wireless and Access Devices**

**Access Points (APs)** create wireless local area networks (WLANs), allowing wireless devices to connect to a wired network. **Cable modems** connect networks to the internet via coaxial cable TV lines. **DSL modems** use telephone lines for internet connectivity.

**Security and Power Devices**

**Firewalls** monitor and filter network traffic based on security policies, operating at various OSI layers depending on type. **Power over Ethernet (PoE)** allows network cables to carry electrical power to devices like IP phones, cameras, and access points. **PoE injectors** add PoE capability to non-PoE network links, while **PoE switches** have this capability built-in. PoE standards include 802.3af (15.4W), 802.3at (25.5W), and 802.3bt (up to 100W).

### 2.6 Given a scenario, configure basic wired/wireless SOHO networks.

**IP Addressing**

**IPv4** uses 32-bit addresses (e.g., 192.168.1.100) with three classes of private addresses: Class A (10.0.0.0/8), Class B (172.16.0.0/12), and Class C (192.168.0.0/16). **IPv6** uses 128-bit addresses (e.g., 2001:0db8:85a3::8a2e:0370:7334) providing a vastly larger address space. **APIPA (Automatic Private IP Addressing)** assigns addresses in the 169.254.0.0/16 range when a Windows client cannot contact a DHCP server, indicating a network configuration problem.

**SOHO Router Configuration**

Typical SOHO (Small Office/Home Office) router configuration includes setting the **SSID (Service Set Identifier)** for the wireless network name, configuring **security** (WPA2 or WPA3), setting a strong **password**, and optionally configuring **port forwarding**, **content filtering**, and **Quality of Service (QoS)** for traffic prioritization.

### 2.7 Compare and contrast internet connection types, network types, and their characteristics.

**Internet Connection Types**

| Connection Type | Description | Characteristics |
| --- | --- | --- |
| **Fiber** | Transmits data as light pulses through fiber-optic cables | Fastest and most reliable, symmetrical speeds, limited availability |
| **Cable** | Uses coaxial TV cable infrastructure | High speed, widely available, bandwidth shared with neighbors |
| **DSL** | Uses existing telephone lines | Speed decreases with distance from central office, asymmetrical speeds |
| **Satellite** | Uses satellite dish for two-way communication | Available anywhere, high latency (500-700ms), weather-dependent |
| **Cellular** | Uses mobile phone networks (4G LTE, 5G) | Mobile connectivity, potential data caps, variable speeds |
| **WISP** | Wireless Internet Service Provider using radio towers | Alternative for rural areas, line-of-sight required |

**Network Types**

**LAN (Local Area Network)** covers a small geographical area like an office or home. **WAN (Wide Area Network)** spans large geographical areas, with the internet being the largest WAN. **PAN (Personal Area Network)** connects personal devices over very short distances using Bluetooth or NFC. **MAN (Metropolitan Area Network)** spans a city or large campus. **SAN (Storage Area Network)** provides high-speed connections between servers and storage devices.

### 2.8 Explain networking tools and their purposes.

**Cable Installation Tools**

A **crimper** attaches connectors (like RJ-45) to the end of network cables. A **cable stripper** removes the outer jacket of cables without damaging internal wires. A **punchdown tool** terminates wires on patch panels or keystone jacks using 110 or Krone blade styles.

**Testing and Troubleshooting Tools**

A **cable tester** verifies cables are wired correctly and can carry signals, checking for opens, shorts, and crossed pairs. A **loopback plug** tests network ports by connecting transmit pins to receive pins. A **toner probe** (toner and probe set) traces cables from one end to another in walls or cable bundles. A **Wi-Fi analyzer** (often a mobile app) displays nearby wireless networks, their signal strength, channels, and security settings to optimize wireless network placement and configuration.

---

## Domain 3.0: Hardware (25% of exam)

### 3.1 Compare and contrast display components and attributes.

**Display Technologies**

**LCD (Liquid Crystal Display)** panels use liquid crystals and a backlight. **TN (Twisted Nematic)** panels offer fast response times ideal for gaming but have poor color reproduction and narrow viewing angles. **IPS (In-Plane Switching)** panels provide excellent color accuracy and wide viewing angles but typically have slower response times. **VA (Vertical Alignment)** panels offer a compromise with better contrast than IPS but slower response than TN.

**OLED (Organic Light-Emitting Diode)** displays have pixels that produce their own light, resulting in perfect black levels, vibrant colors, and no backlight requirement. OLED displays are susceptible to burn-in with static images. **Mini-LED** uses thousands of tiny LEDs for backlighting, providing better contrast and local dimming than traditional LED backlights.

**Display Attributes**

**Refresh rate** measures how many times per second the display updates (60Hz, 120Hz, 144Hz, 240Hz). Higher refresh rates provide smoother motion, important for gaming. **Resolution** defines the number of pixels (1920x1080 for 1080p, 2560x1440 for 1440p, 3840x2160 for 4K). **Color gamut** describes the range of colors a display can reproduce (sRGB, Adobe RGB, DCI-P3).

**Legacy Components**

An **inverter** converts DC power to AC power for CCFL (Cold Cathode Fluorescent Lamp) backlights in older LCD displays. Modern LED-backlit displays do not require inverters. **Touch screens** use **digitizers** to convert touch input into digital signals.

### 3.2 Summarize basic cable types and their connectors, features, and purposes.

**Video Cables**

**HDMI (High-Definition Multimedia Interface)** carries both video and audio digitally, commonly used for monitors, TVs, and projectors. **DisplayPort** offers higher bandwidth than HDMI and supports daisy-chaining multiple monitors. **VGA (Video Graphics Array)** is a legacy analog video connector with a blue 15-pin connector. **DVI (Digital Visual Interface)** can carry digital or analog video signals.

**Peripheral Cables**

**USB (Universal Serial Bus)** comes in multiple versions: USB 2.0 (480 Mbps), USB 3.0/3.1/3.2 (5-20 Gbps), and USB4 (40 Gbps). Connector types include USB-A (rectangular), USB-B (square), USB-C (oval, reversible), Micro-USB, and Mini-USB. **Thunderbolt** combines PCIe and DisplayPort into a single interface, offering up to 40 Gbps (Thunderbolt 3/4) using USB-C connectors.

**Storage and Network Cables**

**SATA (Serial ATA)** connects internal storage drives with data and power cables. **eSATA (External SATA)** provides external connectivity. **Ethernet cables** use RJ-45 connectors with various categories: Cat 5e (1 Gbps), Cat 6 (1-10 Gbps), Cat 6a (10 Gbps), Cat 7/8 (10+ Gbps). **Plenum-rated** cables use fire-resistant jackets for installation in air handling spaces.

### 3.3 Compare and contrast RAM characteristics.

**Form Factors**

**DIMM (Dual In-line Memory Module)** is used in desktop computers with 288 pins for DDR4/DDR5. **SO-DIMM (Small Outline DIMM)** is used in laptops and small form-factor devices with 260 pins (DDR4) or 262 pins (DDR5).

**DDR Generations**

Each DDR generation is incompatible with others due to different notch positions and electrical characteristics. **DDR3** operates at 1.5V with speeds from 800-2133 MHz. **DDR4** operates at 1.2V with speeds from 2133-3200+ MHz, offering better performance and power efficiency. **DDR5** operates at 1.1V with speeds from 4800-6400+ MHz, featuring on-die ECC and improved efficiency.

**Error Correction**

**ECC (Error-Correcting Code) RAM** can detect and correct single-bit memory errors, critical for servers and workstations where data integrity is paramount. **Non-ECC RAM** is used in consumer systems where cost is more important than error correction.

**Channel Configurations**

Memory channels allow the CPU to access multiple memory modules simultaneously. **Single-channel** uses one module, **dual-channel** uses two matched modules, **triple-channel** uses three, and **quad-channel** uses four, each providing increased memory bandwidth.

### 3.4 Compare and contrast storage devices.

**Hard Disk Drives (HDDs)**

HDDs use spinning magnetic platters with read/write heads. Common spindle speeds include 5400 RPM (laptops, energy-efficient), 7200 RPM (desktops, balanced), and 10,000 RPM (performance). Form factors include 3.5-inch (desktop) and 2.5-inch (laptop). HDDs offer lower cost per gigabyte but are slower and less durable than SSDs.

**Solid-State Drives (SSDs)**

SSDs use flash memory with no moving parts, providing faster access times, better durability, lower power consumption, and silent operation. **SATA SSDs** use the SATA III interface (6 Gbps, ~550 MB/s maximum). **NVMe SSDs** use the PCIe interface, offering speeds from 2000-7000+ MB/s depending on PCIe generation.

**SSD Form Factors**

**2.5-inch SATA** SSDs use the same form factor as laptop HDDs. **M.2** is a small form factor connecting directly to the motherboard, available in various lengths (2242, 2260, 2280, 22110). **mSATA** is an older compact form factor. M.2 drives use keying (B-key, M-key, or B+M-key) to indicate supported interfaces.

**RAID Configurations**

**RAID 0 (Striping)** combines drives for increased performance with no redundancy. **RAID 1 (Mirroring)** duplicates data across drives for redundancy. **RAID 5 (Striping with Parity)** requires minimum 3 drives, providing performance and single-drive fault tolerance. **RAID 6 (Dual Parity)** requires minimum 4 drives with two-drive fault tolerance. **RAID 10 (1+0)** combines mirroring and striping, requiring minimum 4 drives for both performance and redundancy.

### 3.5 Given a scenario, install and configure motherboards, CPUs, and add-on cards.

**Motherboard Form Factors**

**ATX (Advanced Technology eXtended)** measures 12" x 9.6" and is the standard desktop form factor. **Micro-ATX** measures 9.6" x 9.6" with fewer expansion slots. **Mini-ITX** measures 6.7" x 6.7" for compact builds with limited expansion.

**CPU Installation**

Intel CPUs use **LGA (Land Grid Array)** sockets where pins are on the motherboard. AMD CPUs use **PGA (Pin Grid Array)** where pins are on the CPU, or **LGA** on newer platforms. Socket types include Intel LGA 1700, 1200 and AMD AM4, AM5. The CPU must match the motherboard socket type.

**BIOS/UEFI Settings**

**BIOS (Basic Input/Output System)** is legacy firmware with text-based interface. **UEFI (Unified Extensible Firmware Interface)** is modern firmware with graphical interface, Secure Boot support, and faster boot times. Common settings include boot order, enabling/disabling integrated peripherals, fan controls, virtualization support (Intel VT-x, AMD-V), and **TPM (Trusted Platform Module)** for encryption key storage.

**Expansion Cards**

**PCIe (Peripheral Component Interconnect Express)** slots come in x1, x4, x8, and x16 configurations. **Video cards** use PCIe x16 slots. **Sound cards** enhance audio quality. **Network interface cards (NICs)** provide wired or wireless connectivity. **Capture cards** record video and audio from external sources.

### 3.6 Given a scenario, install the appropriate power supply.

**Power Supply Specifications**

Power supplies are rated by wattage (300W-1200W+). Calculate required wattage by adding all component power requirements plus 20-30% headroom. **Voltage rails** include +3.3V, +5V, and +12V, with modern systems drawing most power from the +12V rail.

**Connectors**

**20+4 pin** main motherboard connector (20-pin legacy, 24-pin modern). **4+4 pin or 8-pin CPU** power connector. **6-pin and 6+2 pin PCIe** connectors for graphics cards. **SATA power** for drives. **Molex** for legacy devices.

**Efficiency and Features**

**80 Plus certification** indicates efficiency at 20%, 50%, and 100% load: Standard (80%), Bronze (82%), Silver (85%), Gold (87%), Platinum (90%), Titanium (92%). Higher efficiency reduces heat and electricity costs. **Modular power supplies** have detachable cables for better cable management. **Semi-modular** has fixed motherboard and CPU cables with modular peripheral cables. **Non-modular** has all cables permanently attached.

### 3.7 Given a scenario, deploy and configure multifunction devices/printers and settings.

**Printer Connectivity**

Printers connect via **USB** (direct connection), **Ethernet** (wired network), or **Wi-Fi** (wireless network). **Print servers** manage multiple printers on a network. **Shared printers** are connected to one computer and shared with others on the network.

**Printer Drivers**

**PCL (Printer Control Language)** is HP's page description language. **PostScript** is Adobe's language offering better graphics quality. Use manufacturer-provided drivers for full functionality. **Universal/generic drivers** provide basic functionality when specific drivers are unavailable.

**Printer Settings**

**Duplex printing** prints on both sides of paper (automatic or manual). **Orientation** sets portrait or landscape mode. **Tray settings** select paper source. **Quality settings** balance speed and output quality. **Secured prints** require authentication at the printer before releasing jobs.

**Security Features**

**User authentication** requires login before printing. **Audit logs** track printing activity. **Badging** uses ID cards for access control.

### 3.8 Given a scenario, perform appropriate printer maintenance.

**Laser Printer Maintenance**

Replace **toner cartridges** when low or empty. Apply **maintenance kits** at specified intervals, typically including fuser assembly, transfer roller, pickup rollers, and separation pads. **Calibrate** the printer for color accuracy. **Clean** internal components to prevent toner buildup.

**Inkjet Printer Maintenance**

Replace **ink cartridges** when depleted. **Clean printheads** if output is streaky or faded, using automated cleaning cycles. Replace printheads if cleaning doesn't resolve issues. Clean **rollers** and **feeder** mechanisms. **Calibrate** for color accuracy and alignment.

**Thermal Printer Maintenance**

Use **special thermal paper** designed for thermal printing. **Clean the heating element** regularly to prevent buildup. Remove **debris** from the paper path. Replace the heating element if print quality degrades.

**Impact Printer Maintenance**

Replace the **ribbon** when print becomes faint. Replace the **printhead** if characters print incompletely. Use **multi-part paper** for carbon copies. Keep the **paper path** clear of debris.

---

## Domain 4.0: Virtualization and Cloud Computing (11% of exam)

### 4.1 Explain virtualization concepts.

**Virtual Machines**

A **virtual machine (VM)** is a software-based emulation of a physical computer, running its own operating system (guest OS) and applications while sharing the physical hardware of the host machine. VMs provide isolation, allowing multiple operating systems to run simultaneously on a single physical machine.

**Hypervisors**

A **hypervisor** creates and manages virtual machines. **Type 1 (Bare-Metal) hypervisors** run directly on hardware without a host operating system (VMware ESXi, Microsoft Hyper-V Server, KVM, Citrix XenServer). Type 1 hypervisors offer better performance and are used in enterprise environments. **Type 2 (Hosted) hypervisors** run as applications on top of an existing operating system (VMware Workstation, Oracle VirtualBox, Parallels Desktop). Type 2 hypervisors are easier to set up and commonly used for development and testing.

**Purpose of Virtualization**

**Sandbox environments** provide isolated spaces for testing software or analyzing malware without risking the host system. **Test development** allows creating multiple identical environments for developing and testing applications across different operating systems. **Legacy software** support enables running older applications on legacy operating systems that are no longer supported on modern hardware. **Application virtualization** allows running applications in isolated containers without full OS virtualization.

**Containers**

**Containers** are lightweight alternatives to VMs that virtualize the operating system rather than hardware. Multiple applications run in isolated user spaces on a single host OS, sharing the kernel. Containers (Docker, Kubernetes) start faster, use fewer resources, and are more portable than VMs but provide less isolation.

**Resource Requirements**

Virtualization requires adequate **CPU** (with virtualization extensions like Intel VT-x or AMD-V), **RAM** (divided among host and guests), **storage** (for VM disk images), and **network** configuration (virtual switches, bridged, NAT, or host-only networking).

### 4.2 Summarize cloud computing concepts.

**Cloud Service Models**

**IaaS (Infrastructure as a Service)** provides virtualized computing resources over the internet, including virtual machines, storage, and networking. Users manage the operating system, applications, and data (AWS EC2, Azure Virtual Machines, Google Compute Engine). **PaaS (Platform as a Service)** provides a platform for developers to build, deploy, and manage applications without managing underlying infrastructure (Azure App Service, Google App Engine, Heroku). **SaaS (Software as a Service)** delivers software applications over the internet on a subscription basis, with the provider managing everything (Microsoft 365, Google Workspace, Salesforce).

**Cloud Deployment Models**

**Public cloud** services are offered over the public internet and available to anyone who wants to purchase them (AWS, Azure, Google Cloud). Resources are shared among multiple tenants (multi-tenancy). **Private cloud** infrastructure is operated solely for a single organization, managed internally or by a third party, hosted internally or externally. **Hybrid cloud** combines public and private clouds, allowing data and applications to be shared between them for flexibility. **Community cloud** is shared by several organizations with common concerns.

**Cloud Characteristics**

**Shared resources** mean cloud providers maintain large pools of resources shared among multiple tenants, providing economies of scale. **Metered utilization** charges users only for resources consumed (pay-as-you-go). **Rapid elasticity** allows automatic scaling of resources up or down based on demand. **High availability** is achieved through redundancy across multiple data centers and geographic regions. **File synchronization** services (OneDrive, Google Drive, Dropbox, iCloud) automatically sync files between devices and the cloud, providing backup and accessibility.

---

## Domain 5.0: Hardware and Network Troubleshooting (28% of exam)

### The CompTIA 6-Step Troubleshooting Methodology

This systematic approach should be followed for all troubleshooting scenarios:

1. **Identify the problem**: Gather information from users, identify symptoms, determine if anything has changed, duplicate the problem if possible, question users, and approach multiple problems individually.

1. **Establish a theory of probable cause**: Question the obvious first, consider multiple approaches (top-to-bottom, bottom-to-top OSI model), and research knowledge bases if necessary.

1. **Test the theory to determine the cause**: Once theory is confirmed, determine next steps to resolve the problem. If theory is not confirmed, establish a new theory or escalate.

1. **Establish a plan of action to resolve the problem and implement the solution**: Consider corporate policies, procedures, and impacts before implementing changes.

1. **Verify full system functionality and, if applicable, implement preventive measures**: Test the solution thoroughly and implement measures to prevent recurrence.

1. **Document findings, actions, and outcomes**: Record the problem, solution, and lessons learned for future reference.

> **Exam Tip:** You MUST know these six steps in order. Expect scenario questions where you must identify the next appropriate step in the troubleshooting process.

### 5.1 Given a scenario, troubleshoot common problems related to motherboards, RAM, CPU, and power.

**Power and Boot Issues**

**No power** indicates checking power connections, testing the power supply with a multimeter or tester, and verifying the power button connection to the motherboard. **Unexpected shutdowns** can result from overheating (check fans and heatsinks), failing power supply, or faulty hardware. **Continuous reboots** may be caused by hardware failure, misconfigured BIOS settings, or corrupted OS.

**POST and Boot Problems**

**POST (Power-On Self-Test) beep codes** indicate specific hardware failures. Patterns vary by BIOS manufacturer (AMI, Award, Phoenix). Consult motherboard documentation for beep code meanings. **Blank screen on boot** could indicate bad video card, faulty RAM, dead CPU, or monitor issues. Check cable connections first, then reseat components.

**Performance Issues**

**System lockups and freezing** often relate to failing RAM, corrupted operating system, or driver conflicts. Run memory diagnostics (Windows Memory Diagnostic, MemTest86). **Overheating** causes shutdowns, performance throttling, and long-term component damage. Ensure proper airflow, clean dust from fans and heatsinks, and reapply thermal paste between CPU and heatsink if necessary.

### 5.2 Given a scenario, troubleshoot common problems related to storage.

**Drive Failure Symptoms**

**Read/write failures** indicate a failing drive. Run diagnostics like `chkdsk` (Windows) or manufacturer utilities. **Slow performance** can indicate a failing drive, nearly full drive, or file fragmentation on HDDs. **Loud clicking noise** (click of death) is a classic sign of failing mechanical hard drive. Back up data immediately and replace the drive.

**Boot Problems**

**Failure to boot** or **"Operating System Not Found"** errors indicate the boot drive may have failed, boot order in BIOS may be incorrect, or OS boot files may be corrupted. Use Windows Recovery Environment to access repair tools like `bootrec /fixmbr`, `bootrec /fixboot`, and `bootrec /rebuildbcd`.

**RAID Issues**

**RAID not found** or **RAID stops working** indicates a drive in the array may have failed or the RAID controller has issues. Check the RAID utility during boot for array status. Most RAID configurations can survive a single drive failure (RAID 1, 5, 6, 10) but require prompt replacement of the failed drive.

### 5.3 Given a scenario, troubleshoot common problems related to displays and projectors.

**Display Output Issues**

**No image on screen** requires checking power connections, ensuring the monitor is on the correct input source, and verifying video cables are securely connected at both ends. Test with a different cable or monitor to isolate the problem. **Dim image** on a laptop may indicate backlight or inverter failure (on older CCFL displays). On projectors, the bulb may be nearing end of life.

**Image Quality Problems**

**Flickering video** could be caused by loose cables, outdated drivers, or failing video card. **Incorrect color patterns** may indicate bent pins on cable connectors (VGA) or failing video card. Try different cables. **Distorted image** or **incorrect resolution** requires checking display settings and ensuring native resolution is selected. **Dead pixels** are individual pixels that remain black or stuck on a color; a few dead pixels may be within manufacturer specifications.

### 5.4 Given a scenario, troubleshoot common problems related to mobile devices.

**Display Issues**

**No display** or **dim display** indicates the display assembly has failed. Dim displays can indicate backlight failure. **Non-responsive touchscreen** requires cleaning the screen first. If problems persist, the digitizer has likely failed and requires screen assembly replacement.

**Connectivity Problems**

**Cannot broadcast to external monitor** requires verifying the device supports video output (e.g., via USB-C DisplayPort Alt Mode) and using the correct adapter and cable. **Poor battery life** results from power-hungry apps (check battery usage in settings), weak cellular signal forcing the radio to work harder, or old battery requiring replacement. **Overheating** often results from intensive use (gaming, video streaming) or direct sunlight exposure. Remove case and stop using device until it cools.

**Application Issues**

**Apps not loading** or **crashing** requires clearing app cache, restarting device, or reinstalling the app. **Slow performance** indicates closing background apps, clearing cache, or restarting the device. Check for OS and app updates.

### 5.5 Given a scenario, troubleshoot common problems related to printers.

**Print Quality Issues**

**Streaks or blurry print** on inkjet printers results from clogged printheads (run cleaning cycle). On laser printers, low toner or damaged fuser causes this. **Faded print** indicates low ink/toner or printhead issues. **Garbled characters** typically indicates printer driver issues. Reinstall the correct driver for the printer model.

**Paper Handling Problems**

**Paper jams** require checking for and removing stuck paper, ensuring the paper tray isn't overloaded, and using correct paper type and size. **Multiple sheets feeding** indicates worn pickup rollers needing replacement.

**Connectivity Issues**

**No connectivity** for network printers requires checking IP address configuration and ensuring printer is on the same network as computers. For USB printers, try different cable or port. **Print jobs stuck in queue** may require restarting the print spooler service or clearing the print queue.

### 5.6 Given a scenario, troubleshoot common problems related to networks.

**Connectivity Problems**

**No connectivity** requires checking physical connections (cable, Wi-Fi), using `ipconfig` (Windows) or `ifconfig` (Linux/macOS) to verify IP configuration. An **APIPA address** (169.254.x.x) indicates failure to reach DHCP server. **Limited connectivity** means connection to local network works but internet access fails, often indicating router or ISP problems. Test by pinging the default gateway, then external IP like 8.8.8.8.

**Performance Issues**

**Slow transfer speeds** result from network congestion, bad cables, or wireless interference. Use Wi-Fi analyzer to find less congested channels. Test with wired connection to eliminate wireless as the cause. **Intermittent connectivity** can be caused by loose cables, failing network adapter, or Wi-Fi interference from other devices or physical obstacles.

**IP Configuration Problems**

**Incorrect IP address** or **duplicate IP address** requires checking DHCP configuration or manually configuring static IP. **DNS issues** prevent name resolution while IP connectivity works. Test by pinging IP addresses directly. Flush DNS cache with `ipconfig /flushdns` (Windows) or change DNS servers to public options like 8.8.8.8 (Google) or 1.1.1.1 (Cloudflare).

---

## CORE 2 (220-1102)

---

## Domain 1.0: Operating Systems (31% of exam)

### 1.1 Identify basic features of Microsoft Windows editions.

**Windows Home Edition**

Windows Home is the standard consumer edition providing essential features for personal use. It includes Windows Defender antivirus, Windows Firewall, and Windows Update. Home edition lacks business-oriented features like BitLocker encryption, Remote Desktop hosting (can only be a client), Hyper-V virtualization, and Group Policy Editor. Home edition cannot join a Windows domain.

**Windows Pro Edition**

Windows Pro targets professionals and small businesses, adding critical features beyond Home edition. **BitLocker** provides full disk encryption to protect data if a device is lost or stolen. **Remote Desktop** allows the computer to be accessed remotely (Pro can host RDP sessions). **Hyper-V** enables running virtual machines directly on Windows. **Group Policy Editor** provides centralized management of system settings. Pro can join **Windows domains** for centralized authentication and management.

**Windows Enterprise Edition**

Windows Enterprise is designed for large organizations and is available only through volume licensing. It includes all Pro features plus advanced capabilities like **AppLocker** (application whitelisting), **DirectAccess** (always-on VPN), **BranchCache** (caching for remote offices), and **Windows To Go** (bootable Windows on USB drives).

**Windows Pro for Workstations**

This edition targets high-end workstations with support for server-grade hardware (up to 4 CPUs, 6TB RAM), **ReFS (Resilient File System)**, persistent memory support, and SMB Direct for high-speed networking.

### 1.2 Given a scenario, use the appropriate Microsoft command-line tool.

**Network Commands**

**`ipconfig`** displays IP configuration. Use `ipconfig /all` for detailed information including MAC addresses and DNS servers. `ipconfig /release` releases DHCP lease, `ipconfig /renew` requests new lease, and `ipconfig /flushdns` clears local DNS cache.

**`ping`** tests connectivity using ICMP echo requests. Use `ping 8.8.8.8` to test internet connectivity or `ping hostname` to test name resolution. **`tracert`** (Windows) or `traceroute` (Linux/macOS) traces the route packets take to a destination, showing each hop. **`netstat`** displays active network connections. Use `netstat -an` to show all connections and listening ports in numerical format. **`nslookup`** queries DNS servers to resolve names to IP addresses or perform reverse lookups.

**System Repair Commands**

**`sfc`** (System File Checker) scans and repairs corrupted Windows system files. Run `sfc /scannow` from an elevated command prompt. **`chkdsk`** checks disk for errors. Use `chkdsk /f` to fix errors and `chkdsk /r` to locate bad sectors and recover readable information.

**Disk Management Commands**

**`diskpart`** is a powerful command-line tool for managing disks, partitions, and volumes. Common commands include `list disk`, `select disk`, `clean`, `create partition primary`, and `format`. **`format`** formats drives with specified file systems.

**Group Policy Commands**

**`gpupdate`** forces Group Policy refresh. Use `gpupdate /force` to reapply all policies immediately. **`gpresult`** displays Resultant Set of Policy (RSoP) showing which policies are applied to user and computer.

**File Management Commands**

**`xcopy`** copies files and directory trees with various options. **`robocopy`** (Robust File Copy) is an advanced file copy tool with features like resumable copying, mirroring, and logging. **`copy`** performs basic file copying.

**User and Process Management**

**`net user`** manages local user accounts (create, delete, modify). **`net use`** maps network drives. **`tasklist`** displays running processes. **`taskkill`** terminates processes using `/PID` (process ID) or `/IM` (image name).

### 1.3/1.4/1.5 Given a scenario, use features, tools, and utilities of the Microsoft Windows OS.

**Control Panel Utilities**

**Control Panel** is the legacy centralized location for Windows settings. Key utilities include **Programs and Features** (uninstall applications), **Devices and Printers** (manage hardware), **Network and Sharing Center** (network configuration), **Power Options** (power management), **System** (computer information and settings), and **Administrative Tools** (advanced system tools).

**Settings App**

The modern **Settings** app (Windows 10/11) gradually replaces Control Panel. Key sections include **System** (display, notifications, power), **Devices** (Bluetooth, printers), **Network & Internet** (Wi-Fi, VPN, proxy), **Personalization** (themes, colors), **Apps** (installed applications), **Accounts** (user accounts, sync), **Time & Language** (region, keyboard), **Gaming**, **Privacy & Security**, and **Update & Security** (Windows Update, recovery, backup).

**Administrative Tools**

**Task Manager** (Ctrl+Shift+Esc) monitors system performance, displays running processes and applications, shows startup programs, and allows ending unresponsive tasks. **Performance** tab shows real-time CPU, memory, disk, and network usage.

**Disk Management** (`diskmgmt.msc`) manages disks and partitions, initializes new disks, creates and formats volumes, assigns drive letters, extends or shrinks volumes, and converts between basic and dynamic disks.

**Event Viewer** (`eventvwr.msc`) displays system event logs including Application, Security, and System logs. Critical for troubleshooting, showing errors, warnings, and informational messages with detailed descriptions and error codes.

**Device Manager** (`devmgmt.msc`) shows all hardware devices, displays device status (working, error, disabled), updates and rolls back drivers, enables/disables devices, and shows resource conflicts.

**Services** (`services.msc`) manages Windows services (background processes), allows starting/stopping services, configuring startup type (Automatic, Manual, Disabled), and viewing service dependencies.

**Computer Management** (`compmgmt.msc`) consolidates multiple tools including Task Scheduler, Event Viewer, Shared Folders, Device Manager, Disk Management, and Services.

### 1.6 Given a scenario, configure Microsoft Windows networking features.

**Network Interface Configuration**

Configure IP addresses, subnet masks, default gateways, and DNS servers through **Network Connections** or **Settings > Network & Internet**. Choose between **DHCP** (automatic configuration) or **static IP** (manual configuration). Configure **alternate DNS** servers for redundancy.

**Network Locations**

Windows uses network profiles to determine firewall and sharing settings. **Private network** (trusted, like home or office) enables network discovery and file sharing. **Public network** (untrusted, like coffee shops) disables discovery and restricts sharing for security.

**Firewall Configuration**

**Windows Defender Firewall** controls inbound and outbound traffic. **Windows Defender Firewall with Advanced Security** creates custom rules for specific applications, ports, or protocols. Configure separate rules for Domain, Private, and Public profiles.

**Network Sharing**

Enable **File and Printer Sharing** to share resources on the network. Configure **Network Discovery** to see other computers and devices. Set up **HomeGroup** (deprecated in newer Windows versions) or **Workgroup** for peer-to-peer networking.

### 1.7 Given a scenario, apply application installation and configuration concepts.

**Installation Methods**

Applications install from **Microsoft Store** (UWP apps, automatic updates), **executable files** (.exe), **Windows Installer packages** (.msi), or **package managers** (Chocolatey, winget). Some applications offer **portable versions** that run without installation.

**Installation Scope**

**User-specific installation** installs for current user only, storing files in user profile. **System-wide installation** installs for all users, requiring administrator privileges, storing files in Program Files.

**Compatibility**

Use **Compatibility Mode** to run older applications designed for previous Windows versions. Configure through application properties or Compatibility Troubleshooter. **Run as Administrator** provides elevated privileges when needed.

### 1.8/1.9 Explain common OS types and perform installations/upgrades.

**Operating System Types**

**Windows** dominates desktop/laptop market with familiar interface and broad software compatibility. **macOS** is Apple's Unix-based OS for Mac computers, known for design and integration with Apple ecosystem. **Linux** is open-source with many distributions (Ubuntu, Fedora, Debian, Mint, CentOS) offering flexibility and customization. **ChromeOS** is Google's cloud-centric OS for Chromebooks. **Android** and **iOS** are mobile operating systems.

**Installation Methods**

**Bootable media** (USB drive or DVD) allows clean installation or in-place upgrade. Create using Media Creation Tool (Windows) or third-party tools. **Network boot (PXE)** boots and installs OS over network, common in enterprise environments using WDS (Windows Deployment Services) or similar.

**Installation Types**

**Clean install** erases drive and installs fresh OS copy. All data is lost unless backed up. Provides cleanest, fastest system. **In-place upgrade** upgrades existing OS to newer version while preserving files, applications, and settings. **Repair installation** reinstalls OS while keeping personal files and some settings.

**Partitioning**

**MBR (Master Boot Record)** supports drives up to 2TB with maximum 4 primary partitions. **GPT (GUID Partition Table)** supports drives larger than 2TB with up to 128 partitions, required for UEFI boot.

### 1.10/1.11 Identify common features and tools of macOS and Linux.

**macOS Features**

**Finder** is the file manager for browsing files and folders. **Spotlight** provides system-wide search for files, applications, and information. **Mission Control** manages windows, desktops, and full-screen apps. **Time Machine** provides automated backup to external drives. **FileVault** offers full disk encryption. **Keychain** manages passwords and certificates. **Gatekeeper** verifies application signatures before allowing execution. **Terminal** provides command-line interface using bash or zsh shells. **Disk Utility** manages disks, partitions, and performs repairs.

**Linux Features**

**Distributions** include Ubuntu (user-friendly), Fedora (cutting-edge), Debian (stable), CentOS/RHEL (enterprise), and Mint (Windows-like). **Desktop environments** include GNOME, KDE Plasma, XFCE, and Cinnamon.

**Shell and Terminal** provide command-line interface, typically using bash or zsh. **Package managers** install and update software: `apt` (Debian/Ubuntu), `yum`/`dnf` (Red Hat/Fedora), `pacman` (Arch).

**Common Linux Commands**

`ls` lists files, `cd` changes directory, `pwd` prints working directory, `cp` copies files, `mv` moves/renames files, `rm` removes files, `mkdir` creates directories, `chmod` changes permissions, `chown` changes ownership, `sudo` executes commands with elevated privileges, `grep` searches text, `find` searches for files, `ps` lists processes, `top` shows system resources, `df` shows disk space, `du` shows directory sizes.

**File System**

Linux uses hierarchical file system starting at root (`/`). Common directories: `/home` (user files), `/etc` (configuration), `/var` (variable data, logs), `/usr` (user programs), `/bin` (essential binaries), `/sbin` (system binaries), `/tmp` (temporary files).

---

## Domain 2.0: Security (25% of exam)

### 2.1 Summarize various security measures and their purposes.

**Logical Security Measures**

**Multi-Factor Authentication (MFA)** requires two or more verification methods: something you know (password), something you have (phone, token), or something you are (biometrics). MFA significantly reduces unauthorized access risk.

**Access Control Lists (ACLs)** and **permissions** control who can access resources. **NTFS permissions** (Windows) include Read, Write, Modify, Full Control. **Linux permissions** use read (r), write (w), execute (x) for owner, group, and others.

**Principle of Least Privilege** grants users only minimum permissions necessary to perform their job. Reduces damage from compromised accounts or insider threats.

**Software Firewalls** (host-based) control traffic for individual machines. Windows Defender Firewall filters inbound and outbound connections based on rules. **VPNs (Virtual Private Networks)** create encrypted tunnels over public networks for secure remote access.

**Physical Security Measures**

**Biometric authentication** uses fingerprints, facial recognition, iris scans, or voice recognition for access control. **Badge systems** and **smart cards** provide physical access control and can integrate with logical access. **Cable locks** (Kensington locks) physically secure laptops and other devices to fixed objects. **Privacy screens** narrow viewing angles to prevent shoulder surfing.

**Environmental Controls**

**Bollards** are physical barriers preventing vehicle access to buildings. **Access control vestibules** (mantraps) are two-door systems preventing tailgating. **Security guards** provide human oversight and response. **Video surveillance** (CCTV) monitors and records activity for security and investigation.

### 2.2 Compare and contrast wireless security protocols and authentication methods.

**Wireless Security Protocols**

**WEP (Wired Equivalent Privacy)** is deprecated and insecure, using weak RC4 encryption easily cracked in minutes. Never use WEP. **WPA (Wi-Fi Protected Access)** improved on WEP using TKIP encryption but is also considered insecure and vulnerable to attacks.

**WPA2** became the long-time standard using strong AES encryption. **WPA2-Personal** (PSK - Pre-Shared Key) uses a shared password for all users, suitable for home and small office. **WPA2-Enterprise** uses 802.1X authentication with RADIUS server, providing individual user credentials for corporate environments.

**WPA3** is the latest standard offering improved security. **SAE (Simultaneous Authentication of Equals)** replaces PSK, providing forward secrecy and protection against offline dictionary attacks. WPA3-Personal uses 128-bit encryption, while WPA3-Enterprise uses 192-bit encryption for high-security networks.

**Authentication Methods**

**Pre-Shared Key (PSK)** uses a shared password known to all users. Simple but less secure for business environments. **802.1X/RADIUS** provides enterprise authentication where each user has unique credentials authenticated against a central RADIUS server. Supports various authentication methods (EAP-TLS, PEAP, EAP-TTLS).

**Additional Security Measures**

**MAC filtering** allows or denies devices based on MAC addresses. Easily bypassed through MAC spoofing, providing minimal security. **SSID broadcast** can be disabled to hide network name, but provides only security through obscurity and is easily discovered.

### 2.3 Given a scenario, detect, remove, and prevent malware using the appropriate tools and methods.

**Malware Types**

**Viruses** attach to files and require user execution to spread. **Worms** self-replicate and spread without user interaction across networks. **Trojans** disguise themselves as legitimate software to trick users into installation. **Ransomware** encrypts files and demands payment for decryption keys. **Spyware** monitors user activity and steals information. **Adware** displays unwanted advertisements. **Rootkits** hide malware presence by operating at kernel level. **Keyloggers** record keystrokes to capture passwords and sensitive data.

**Malware Removal Process**

1. **Identify and research** malware symptoms (slow performance, pop-ups, redirects, strange processes).

1. **Quarantine** infected system by disconnecting from network to prevent spread.

1. **Disable System Restore** (Windows) to prevent malware restoration.

1. **Remediate** by updating anti-malware software and running full system scan in Safe Mode if necessary.

1. **Schedule scans** and enable real-time protection.

1. **Re-enable System Restore** and create clean restore point.

1. **Educate users** on safe computing practices to prevent reinfection.

**Prevention**

Keep operating systems and applications updated with latest security patches. Use reputable **anti-virus/anti-malware** software with real-time protection. Enable **firewalls**. Be cautious of email attachments and downloads. Avoid clicking suspicious links. Use **User Account Control (UAC)** to prevent unauthorized changes. Maintain regular **backups** for recovery.

### 2.4 Explain common social-engineering attacks, threats, and vulnerabilities.

**Social Engineering Attacks**

**Phishing** uses fraudulent emails appearing to be from legitimate sources to trick users into revealing credentials or clicking malicious links. **Spear phishing** targets specific individuals or organizations with personalized attacks. **Whaling** targets high-level executives. **Vishing** (voice phishing) uses phone calls to manipulate victims. **Smishing** (SMS phishing) uses text messages.

**Physical Social Engineering**

**Tailgating** (piggybacking) involves following authorized persons into secure areas without providing credentials. **Shoulder surfing** observes users entering passwords or viewing sensitive information. **Dumpster diving** searches trash for discarded documents containing sensitive information.

**Other Tactics**

**Impersonation** involves pretending to be someone else (IT support, executive, vendor). **Pretexting** creates fabricated scenarios to trick victims. **Baiting** offers something enticing (free USB drive) that contains malware.

**Defense**

User education and awareness training are most effective defenses. Verify requests through separate communication channels. Follow security policies. Use shredders for sensitive documents. Implement badge requirements and visitor logs. Be skeptical of unsolicited requests for information.

### 2.5/2.6 Given a scenario, manage and configure basic security settings in the Microsoft Windows OS.

**User and Group Management**

Create user accounts through **Computer Management** or **Settings**. Assign users to groups: **Administrators** (full control), **Users** (standard users), **Guests** (limited access). Follow **principle of least privilege** by granting only necessary permissions.

**NTFS Permissions**

**NTFS permissions** control access to files and folders: **Read**, **Write**, **Read & Execute**, **Modify**, **Full Control**. Permissions are cumulative (most permissive wins) except **Deny** which always overrides **Allow**.

**Share Permissions**

**Share permissions** apply only to network access: **Read**, **Change**, **Full Control**. When combining share and NTFS permissions, most restrictive applies. Best practice: set share permissions to **Full Control** for Everyone, then use NTFS permissions for granular control.

**Encryption**

**BitLocker** provides full disk encryption for entire volumes. Requires **TPM (Trusted Platform Module)** for automatic unlocking or can use password/USB key. Protects data if device is lost or stolen. **EFS (Encrypting File System)** encrypts individual files and folders, tied to user account.

**User Account Control (UAC)**

UAC prompts for administrator approval before allowing changes to system. Prevents unauthorized modifications. Configure UAC level in Control Panel (from always notify to never notify).

**Windows Defender**

**Windows Defender Antivirus** provides real-time protection against malware. **Windows Defender Firewall** controls network traffic. **Windows Security** app consolidates security features including virus protection, firewall, device security, and account protection.

### 2.7 Explain common methods for securing mobile and embedded devices.

**Mobile Device Security**

**Screen locks** use passcodes, PINs, patterns, fingerprints, or facial recognition to prevent unauthorized access. **Biometric authentication** provides convenient security. **Full device encryption** protects data at rest (enabled by default on modern iOS and Android).

**Remote Management**

**Remote wipe** allows erasing all data from lost or stolen devices via Find My iPhone, Android Device Manager, or MDM solutions. **Remote lock** secures device remotely. **Location tracking** helps locate lost devices.

**Updates and Patches**

Keep mobile OS and applications updated to protect against vulnerabilities. Enable automatic updates when possible. Remove unused applications to reduce attack surface.

**App Security**

Download apps only from official stores (App Store, Google Play). Review app permissions before installing. Be cautious of apps requesting excessive permissions. Enable **app verification** to scan for malware.

**Network Security**

Avoid connecting to unsecured public Wi-Fi. Use VPN on public networks. Disable Bluetooth when not in use. Be cautious of NFC-based attacks.

### 2.8 Given a scenario, use common data destruction and disposal methods.

**Software-Based Destruction**

**Standard formatting** removes file system structures but data remains recoverable. **Low-level formatting** overwrites entire drive but is time-consuming. **Drive wiping** software overwrites data multiple times using standards like DoD 5220.22-M (7 passes) or Gutmann method (35 passes). Tools include DBAN (Darik's Boot and Nuke), Eraser, and manufacturer utilities.

**Physical Destruction**

**Degaussing** uses powerful electromagnets to destroy data on magnetic media (HDDs, tapes). Does not work on SSDs or flash media. **Drilling** physically damages platters or chips. **Shredding** uses industrial shredders to destroy drives into small pieces. **Incineration** completely destroys media through burning. **Pulverizing** crushes drives into powder.

**Considerations**

Choose method based on data sensitivity and compliance requirements (HIPAA, PCI-DSS, GDPR). For highly sensitive data, use physical destruction. For drives being reused, use software wiping. Maintain chain of custody documentation. Use certified destruction services for compliance.

### 2.9 Given a scenario, configure appropriate security settings on SOHO wireless and wired networks.

**Wireless Security Configuration**

**Change default credentials** immediately on new routers. Default usernames/passwords are publicly known and easily exploited. Use strong, unique administrator password.

**Enable WPA2 or WPA3** encryption with strong passphrase (minimum 12 characters, mix of letters, numbers, symbols). **Disable WPS (Wi-Fi Protected Setup)** as it's vulnerable to brute-force attacks.

**Change default SSID** to something that doesn't identify router make/model. **Disable SSID broadcast** for minor additional security (easily bypassed). **Enable MAC filtering** to allow only specific devices, though MAC addresses can be spoofed.

**Firmware Updates**

Regularly check for and install **router firmware updates** to patch security vulnerabilities. Enable automatic updates if available.

**Network Segmentation**

Use **guest network** for visitors, isolated from main network. Configure separate VLAN for IoT devices to limit potential compromise impact.

**Wired Network Security**

**Disable unused ports** on switches. Use **port security** to limit devices per port. Implement **802.1X** for port-based authentication. Place network equipment in secure locations.

### 2.10 Given a scenario, install and configure browsers and relevant security settings.

**Browser Security Settings**

**Pop-up blockers** prevent unwanted pop-up windows that may contain malware or phishing attempts. **Content filters** block access to malicious or inappropriate websites.

**Privacy Settings**

**Clear browsing data** regularly including cache, cookies, and history. **Private browsing mode** (Incognito, InPrivate) doesn't save history, cookies, or site data after session closes. Note: doesn't provide anonymity from ISP or websites.

**Extensions and Add-ons**

Install only trusted extensions from official stores. Review permissions before installing. Remove unused extensions. Be cautious of extensions requesting excessive permissions.

**Security Features**

Enable **phishing and malware protection** (SmartScreen, Safe Browsing). Keep browser updated to latest version for security patches. **Disable autofill** for sensitive information. Use **password manager** instead of browser-saved passwords for better security.

**Certificate Management**

Browsers verify SSL/TLS certificates for HTTPS sites. Never proceed to sites with certificate errors when entering sensitive information. Check for padlock icon in address bar.

---

## Domain 3.0: Software Troubleshooting (22% of exam)

### 3.1 Given a scenario, troubleshoot common Windows OS problems.

**Performance Issues**

**Slow system performance** requires checking Task Manager for applications consuming excessive CPU, memory, or disk resources. Check **Resource Monitor** for detailed analysis. Run **Disk Cleanup** to remove temporary files. **Defragment** HDDs (not SSDs). Check for malware. Disable unnecessary startup programs.

**Boot Problems**

**"OS Not Found"** or **"Invalid Boot Disk"** errors indicate boot drive failure, incorrect BIOS boot order, or corrupted boot files. Access **Windows Recovery Environment** to use **Startup Repair** or manual tools like `bootrec /fixmbr`, `bootrec /fixboot`, and `bootrec /rebuildbcd`.

**Blue Screen of Death (BSOD)**

BSOD indicates critical system failure. Note the **stop code** (e.g., SYSTEM_SERVICE_EXCEPTION, PAGE_FAULT_IN_NONPAGED_AREA). Common causes include faulty drivers, bad RAM, failing hard drive, or overheating. Check **Event Viewer** for details. Run **Windows Memory Diagnostic** to test RAM. Update or roll back recently changed drivers. Check for overheating.

**Application Issues**

**Application crashes** require reinstalling the application, checking Event Viewer for error details, running as administrator, or using compatibility mode. **Application won't install** may require checking system requirements, freeing disk space, or running installer as administrator.

**Update Problems**

**Windows Update failures** require running **Windows Update Troubleshooter**, manually downloading updates, or resetting Windows Update components. Check available disk space.

### 3.2 Given a scenario, troubleshoot common personal computer (PC) security issues.

**Malware Symptoms**

**Pop-ups** indicate adware infection. Run anti-malware scan. Use pop-up blocker. **Browser redirection** sends browser to unwanted websites, indicating malware. Scan for malware, reset browser settings, check proxy settings.

**Security Warnings**

**Invalid certificate errors** may indicate man-in-the-middle attack or misconfigured/expired certificate. Don't proceed to sites with certificate errors when entering sensitive information. **Antivirus alerts** should be investigated immediately. Don't ignore or disable antivirus.

**Access Issues**

**System lockout** after failed login attempts requires administrator to unlock account. **Unauthorized access** or **changed settings** indicate possible compromise. Change passwords, scan for malware, review account activity.

**Email Security**

**Spam** requires using spam filters, not opening suspicious emails, not clicking links in unsolicited emails. **Phishing emails** attempt to steal credentials. Verify sender, check for suspicious links, don't provide credentials via email.

### 3.3 Given a scenario, use best practice procedures for malware removal.

**Seven-Step Malware Removal Process**

1. **Identify and research malware symptoms**: Gather information about unusual behavior, error messages, or performance issues. Research symptoms to identify malware type.

1. **Quarantine the infected system**: Disconnect from network (wired and wireless) to prevent spread to other systems. Disable System Restore to prevent malware restoration.

1. **Disable System Restore**: In Windows, disable System Restore to prevent malware from hiding in restore points. Will re-enable after cleaning.

1. **Remediate the infected systems**: Update anti-malware software signatures. Boot into Safe Mode if necessary. Run full system scan. Remove or quarantine detected threats. Delete temporary files.

1. **Schedule scans and run updates**: Configure automatic scans. Enable real-time protection. Update operating system and applications to patch vulnerabilities.

1. **Enable System Restore and create a restore point**: Re-enable System Restore. Create new, clean restore point for future recovery.

1. **Educate the end user**: Explain how infection occurred. Teach safe computing practices (don't click suspicious links, verify email senders, keep software updated, use strong passwords).

### 3.4/3.5 Given a scenario, troubleshoot common mobile OS and application issues.

**Performance Problems**

**Slow performance** requires closing background apps, clearing app cache (Settings > Apps), restarting device, checking for OS and app updates, or factory reset as last resort. **Apps freezing or crashing** requires force-closing app, clearing cache, reinstalling app, or checking for updates.

**Battery Issues**

**Short battery life** requires checking battery usage in settings to identify power-hungry apps. Reduce screen brightness, disable background app refresh, disable location services when not needed, check for weak cellular signal forcing radio to work harder, or replace old battery.

**Connectivity Problems**

**Cannot connect to Wi-Fi** requires toggling Wi-Fi off/on, forgetting network and reconnecting, restarting device, resetting network settings, or checking router. **Bluetooth won't pair** requires ensuring devices are in pairing mode, forgetting device and re-pairing, restarting both devices, or checking for interference.

**Display Issues**

**Touchscreen unresponsive** requires cleaning screen, removing screen protector, restarting device, or replacing digitizer if hardware failure. **Screen rotation not working** requires checking rotation lock setting, restarting device, or calibrating sensors.

**Application Issues**

**Apps won't install** requires checking available storage space, checking compatibility with OS version, clearing Play Store/App Store cache, or checking parental controls. **Apps won't update** requires checking internet connection, freeing storage space, or manually updating.

**Security Issues**

**Unexpected pop-ups** or **redirects** indicate malware. Install and run mobile security app, uninstall recently installed apps, factory reset if necessary. **Excessive data usage** may indicate malware or misbehaving app. Check data usage by app in settings.

---

## Domain 4.0: Operational Procedures (22% of exam)

### 4.1 Given a scenario, implement best practices associated with documentation and support systems information management.

**Documentation Types**

**Network diagrams** provide visual representations of network topology, showing devices, connections, and IP addressing. **Asset management** tracks all hardware and software assets including purchase dates, warranty information, assigned users, and specifications. **Knowledge base articles** document solutions to common problems, procedures, and how-to guides for consistent support.

**Ticketing Systems**

**Help desk ticketing** tracks support requests from submission through resolution. Includes priority levels, assignment, status updates, and resolution documentation. Provides accountability and metrics.

**Inventory Management**

Maintain accurate inventory of hardware (computers, monitors, printers, network equipment) and software licenses. Track asset lifecycle from procurement through disposal. Use asset tags for identification.

**Policies and Procedures**

Document **standard operating procedures (SOPs)** for common tasks. Maintain **acceptable use policies (AUPs)** defining permitted use of IT resources. Document **security policies** and **incident response procedures**.

### 4.2 Explain basic change-management best practices.

**Change Management Process**

**Change management** minimizes risk and disruption from IT changes through formal processes. Steps include:

1. **Request for change**: Document proposed change, reason, scope, and expected impact.

1. **Purpose of change**: Clearly state why change is necessary and expected benefits.

1. **Scope of change**: Define what systems, users, and services are affected.

1. **Risk analysis**: Identify potential risks and mitigation strategies.

1. **Change board approval**: Submit to change advisory board (CAB) for review and approval.

1. **End-user acceptance**: Communicate changes to affected users, provide training if necessary.

1. **Backout plan**: Document procedures to revert to previous state if change fails.

1. **Implementation**: Execute change during approved maintenance window.

1. **Testing**: Verify change works as expected without unintended consequences.

1. **Documentation**: Record what was changed, when, by whom, and results.

**Maintenance Windows**

Schedule changes during **planned downtime** or low-usage periods to minimize impact. Communicate maintenance windows to users in advance.

### 4.3 Given a scenario, implement workstation backup and recovery methods.

**Backup Types**

**Full backup** copies all selected data. Provides complete backup but takes longest time and most storage. **Incremental backup** copies only data changed since last backup of any type. Fastest backup, smallest size, but slower restoration requiring full backup plus all incrementals. **Differential backup** copies data changed since last full backup. Faster than full, slower than incremental. Restoration requires only full backup plus latest differential.

**Backup Strategies**

Follow **3-2-1 rule**: 3 copies of data, on 2 different media types, with 1 copy offsite. Schedule regular automated backups. Test restoration procedures periodically to verify backups work.

**Backup Media**

Options include external hard drives, network attached storage (NAS), tape drives, cloud storage (OneDrive, Google Drive, Dropbox), or dedicated backup services.

**Windows Backup Tools**

**File History** backs up files in user libraries, desktop, favorites, and contacts. **System Image Backup** creates complete copy of system drive for bare-metal recovery. **System Restore** creates restore points with system files and settings (not personal files).

**Recovery Options**

**System Restore** reverts system files and settings to previous point in time. **System Image Recovery** restores entire system from image backup. **Reset This PC** reinstalls Windows with option to keep or remove personal files.

### 4.4 Given a scenario, use common safety procedures.

**Electrical Safety**

**Disconnect power** before working inside computers or other electrical equipment. Use **surge protectors** and **UPS (Uninterruptible Power Supply)** to protect equipment. Be aware of **trip hazards** from cables. Never work on equipment with wet hands or in wet conditions.

**ESD (Electrostatic Discharge) Protection**

Static electricity can damage sensitive electronic components. Use **anti-static wrist strap** connected to grounded surface. Use **anti-static mat** for workspace. Touch grounded metal object before handling components. Store components in anti-static bags. Avoid working on carpet. Maintain 20-70% humidity.

**Physical Safety**

Use proper **lifting techniques** (bend knees, not back) for heavy equipment. Get help for heavy items. Be aware of **pinch points** in equipment. Wear **safety glasses** when appropriate. Keep work area clean and organized.

**Fire Safety**

Know location of **fire extinguishers**. Use **Class C extinguisher** for electrical fires. Never use water on electrical fires. Know evacuation routes. Don't block fire exits or extinguishers.

**Equipment Handling**

Handle components carefully to avoid damage. Use proper tools for disassembly. Keep track of screws and small parts. Avoid touching circuit board traces or component pins.

### 4.5 Summarize environmental impacts and local environmental controls.

**Environmental Controls**

**Temperature** should be maintained between 64-81°F (18-27°C) for computer equipment. **Humidity** should be 20-80% to prevent static discharge (too low) or condensation (too high). **HVAC systems** maintain proper temperature and humidity in server rooms and data centers.

**Power Management**

**UPS (Uninterruptible Power Supply)** provides battery backup during power outages and protects against surges and sags. **Surge protectors** guard against voltage spikes. **Power distribution units (PDUs)** manage power to multiple devices in racks.

**Material Safety**

**MSDS (Material Safety Data Sheets)** or **SDS (Safety Data Sheets)** provide information on hazardous materials including handling, storage, disposal, and emergency procedures. Required for chemicals like cleaning solvents, thermal paste, and compressed air.

**Proper Disposal**

**E-waste recycling** properly disposes of electronic equipment containing hazardous materials (lead, mercury, cadmium). Use certified recycling centers. **Battery disposal** requires special handling due to toxic materials. **Toner cartridge recycling** through manufacturer programs. Follow local regulations for disposal.

### 4.6 Explain the importance of prohibited content/activity and privacy, licensing, and policy concepts.

**Acceptable Use Policy (AUP)**

AUP defines permitted and prohibited uses of IT resources. Typically prohibits illegal activities, harassment, accessing inappropriate content, installing unauthorized software, and sharing credentials. Users acknowledge AUP before accessing systems.

**Privacy and Data Protection**

Protect **PII (Personally Identifiable Information)** including names, addresses, Social Security numbers, financial information, and medical records. Comply with regulations like **GDPR** (General Data Protection Regulation), **HIPAA** (Health Insurance Portability and Accountability Act), and **PCI-DSS** (Payment Card Industry Data Security Standard).

**Software Licensing**

Ensure all software is properly licensed. License types include **personal/home** (single user), **enterprise/volume** (multiple users/devices), **open source** (free with various restrictions), and **subscription** (ongoing payment). Avoid software piracy. Track licenses through asset management.

**Incident Response**

Follow **incident response policy** for security incidents. Steps include identification, containment, eradication, recovery, and lessons learned. Report incidents to appropriate personnel. Preserve evidence for investigation.

**Data Classification**

Classify data by sensitivity: **Public** (no harm if disclosed), **Internal** (for internal use only), **Confidential** (limited access, harm if disclosed), **Restricted** (highest sensitivity, very limited access). Apply appropriate security controls based on classification.

### 4.7 Given a scenario, use proper communication techniques and professionalism.

**Communication Best Practices**

**Active listening**: Pay full attention, don't interrupt, ask clarifying questions, repeat back to confirm understanding. **Avoid jargon**: Use clear, simple language appropriate for user's technical level. Explain technical concepts in terms users understand.

**Professional Demeanor**

**Be patient and empathetic**: Understand users may be frustrated. Remain calm and professional. **Set expectations**: Provide realistic timelines for resolution. **Follow up**: Keep users informed of progress. Confirm resolution with user.

**Customer Service**

**Be respectful**: Treat all users with courtesy regardless of technical knowledge. **Maintain confidentiality**: Don't discuss user issues with unauthorized persons. **Take ownership**: See issues through to resolution even if escalation is needed.

**Difficult Situations**

**Avoid arguing**: Stay professional even if user is upset. **Don't be defensive**: Focus on solving the problem, not assigning blame. **Know when to escalate**: Recognize situations requiring management involvement.

**Documentation**

Document all interactions in ticketing system. Include problem description, steps taken, and resolution. Use clear, professional language.

### 4.8 Identify the basics of scripting.

**Scripting Languages**

**Batch files (.bat, .cmd)**: Legacy Windows scripting using command-line commands. Simple automation of repetitive tasks. **PowerShell (.ps1)**: Modern, powerful Windows scripting language with object-oriented approach. Can manage Windows, Active Directory, Exchange, and more.

**Shell scripts (.sh)**: Linux/macOS scripting using bash, sh, or zsh. Automate system administration tasks. **Python (.py)**: General-purpose programming language commonly used for scripting, automation, and system administration. Cross-platform.

**JavaScript (.js)**: Primarily for web development but also used for automation (Node.js). **VBScript (.vbs)**: Legacy Windows scripting, largely replaced by PowerShell.

**Basic Scripting Concepts**

**Variables** store data for use in scripts. **Loops** repeat actions (for, while). **Conditionals** make decisions (if/then/else). **Comments** document code. **Functions** encapsulate reusable code.

**Use Cases**

Automate repetitive tasks (user account creation, file management, backups). Schedule tasks with Task Scheduler (Windows) or cron (Linux). Batch processing of multiple items. System configuration and deployment.

### 4.9 Given a scenario, use remote access technologies.

**Remote Desktop Protocol (RDP)**

**RDP** provides graphical remote access to Windows computers over TCP port 3389. Windows Pro and above can host RDP sessions. Use **Remote Desktop Connection** (mstsc.exe) to connect. Enable **Network Level Authentication (NLA)** for security. Configure firewall to allow RDP traffic.

**Virtual Network Computing (VNC)**

**VNC** is cross-platform remote access technology. Server runs on remote computer, viewer connects from local computer. Various implementations (RealVNC, TightVNC, UltraVNC). Less secure than RDP by default, use SSH tunneling for encryption.

**Secure Shell (SSH)**

**SSH** provides secure command-line remote access over TCP port 22. Standard for Linux/Unix administration. Windows 10/11 includes built-in SSH client. Use **PuTTY** on older Windows versions. Supports public key authentication for passwordless login.

**Third-Party Remote Access Tools**

**TeamViewer**, **AnyDesk**, **LogMeIn**, **GoToMyPC** provide easy remote access through firewalls using cloud relay servers. Useful for supporting remote users. Some are free for personal use, require licenses for commercial use.

**Remote Assistance**

**Windows Remote Assistance** allows helping users by viewing and controlling their screen with permission. Initiated by user requesting help. **Quick Assist** (Windows 10/11) provides similar functionality with simpler setup.

**VPN (Virtual Private Network)**

VPN creates encrypted tunnel to corporate network for remote access to internal resources. Common protocols include **OpenVPN**, **L2TP/IPsec**, **IKEv2**, and **WireGuard**. Provides security on untrusted networks.

---

**End of Study Guide**

This study guide covers all exam objectives for CompTIA A+ 220-1101 and 220-1102 certifications. Use this as a comprehensive reference while preparing for your certification exams. Good luck!

