# Scale Integration Guide

This POS system supports multiple methods for integrating digital scales, giving you flexibility to choose the option that best fits your hardware, operating system, and browser compatibility.

## 🎯 Quick Decision Guide

| Your Setup | Recommended Method | Browser Requirements |
|------------|-------------------|---------------------|
| **USB scale + Desktop (Windows/Mac/Linux)** | Web Serial API | Chrome or Edge |
| **Bluetooth scale + Desktop** | Web Bluetooth | Chrome or Edge (desktop) |
| **iPad/iOS device** | WebSocket Service* | Any browser |
| **Any scale + Production environment** | WebSocket Service | Any browser |
| **Manual entry (no scale)** | Manual Entry | Any browser |

\* Requires running a small backend service on your network

---

## 📋 Integration Methods

### 1. Manual Entry (Default)
**Best for:** Getting started, iPad users without backend service

#### Pros:
- ✅ Works on **all devices** and browsers
- ✅ No additional hardware required
- ✅ Simple and reliable
- ✅ No configuration needed

#### Cons:
- ❌ Requires typing weight manually
- ❌ Slower checkout process

#### Setup:
1. Navigate to **Settings → POS**
2. Select **Manual Entry** as integration type
3. Save settings

That's it! When weighing items, you'll manually enter the weight value.

---

### 2. Web Serial API (USB Scales)
**Best for:** Desktop POS terminals with USB scales

#### Pros:
- ✅ Direct USB connection - no backend service needed
- ✅ Works with most retail scales
- ✅ Real-time weight updates
- ✅ Auto-detect scale protocol

#### Cons:
- ❌ **Chrome/Edge only** (not Firefox or Safari)
- ❌ **Desktop only** (not supported on iPad/iOS)
- ❌ Requires HTTPS (already configured on port 8444)

#### Supported Scales:
- ✅ Mettler Toledo
- ✅ Fairbanks
- ✅ Ohaus
- ✅ Avery Weigh-Tronix
- ✅ CAS
- ✅ Most generic USB retail scales

#### Setup:
1. **Connect your USB scale** to your computer
2. Navigate to **Settings → POS → Scale Integration**
3. Select **Web Serial API (USB Scales)**
4. Choose your scale brand or "Generic/Auto-detect"
5. Set baud rate (usually 9600 - check your scale manual)
6. Enable "Auto-populate weight" and "Auto-connect" if desired
7. Save settings
8. Go to **POS Terminal**
9. Click the **Scale** button
10. Click **Connect to Scale**
11. Select your scale from the browser dialog

Your scale is now connected! Weight will update automatically.

#### Troubleshooting:
- **"No devices found"**: Check USB connection, try different USB port
- **"Access denied"**: Close other programs that might be using the scale
- **Garbled data**: Try different baud rate (check scale manual)
- **Not supported**: Use WebSocket method instead

---

### 3. Web Bluetooth (BLE Scales)
**Best for:** Bluetooth-enabled scales on compatible devices

#### Pros:
- ✅ Wireless connection
- ✅ No cables needed
- ✅ Works on some mobile devices

#### Cons:
- ❌ **Very limited browser support**
- ❌ **Not reliable on iOS/iPadOS**
- ❌ Few retail scales have BLE
- ❌ Requires Bluetooth LE (not classic Bluetooth)

#### Compatible Browsers:
- Chrome/Edge (desktop) - ✅ Full support
- Chrome (Android) - ✅ Partial support
- Safari (iOS/macOS) - ❌ Very limited/unstable

#### Supported Scales:
Most retail scales do NOT have Bluetooth LE. Examples that do:
- Dymo M25 postal scale
- Greater Goods Bluetooth scales
- Smart home scales (often not suitable for retail)

#### Setup:
1. Ensure your scale supports **Bluetooth LE** (not classic Bluetooth)
2. Find your scale's **Bluetooth Service UUID** (check manual)
3. Navigate to **Settings → POS → Scale Integration**
4. Select **Web Bluetooth (BLE Scales)**
5. Enter the Bluetooth Service UUID
6. Enable auto-populate if desired
7. Save settings
8. Go to POS Terminal → Click **Scale** → **Connect**
9. Pair your scale when prompted

**Note:** Due to limited compatibility, we recommend Web Serial or WebSocket instead.

---

### 4. WebSocket Service (Backend) ⭐ **RECOMMENDED FOR PRODUCTION**
**Best for:** Production environments, iPad users, any scale/OS combination

#### Pros:
- ✅ **Works with ANY scale** (USB, Bluetooth, RS-232, etc.)
- ✅ **Works on ALL devices** (iPad, Android, desktop)
- ✅ **Works in ALL browsers** (Safari, Firefox, Chrome)
- ✅ Most flexible and reliable
- ✅ Can run on a separate computer/Raspberry Pi
- ✅ Example services provided (Node.js & Python)

#### Cons:
- ⚠️ Requires running a small backend service
- ⚠️ Slightly more complex setup

#### How It Works:
```
Scale (USB/BT) → Backend Service → WebSocket → POS System (Any Browser)
                 (Node.js/Python)
```

### Setup (WebSocket Method):

#### Step 1: Choose Your Backend Service

We provide two ready-to-use services:

##### **Option A: Node.js Service** (Recommended)
```bash
# 1. Install Node.js (if not already installed)
# Download from: https://nodejs.org/

# 2. Navigate to the service directory
cd /opt/sites/admin.middleworldfarms.org/docs/scale-integration/

# 3. Install dependencies
npm install serialport ws

# 4. Edit configuration in scale-websocket-service.js
# Set your serial port path (e.g., COM3 on Windows, /dev/ttyUSB0 on Linux)
nano scale-websocket-service.js

# 5. Run the service
node scale-websocket-service.js

# Service will auto-detect your scale and start running
```

##### **Option B: Python Service**
```bash
# 1. Install Python 3 (if not already installed)

# 2. Install dependencies
pip3 install pyserial websockets

# 3. Navigate to service directory
cd /opt/sites/admin.middleworldfarms.org/docs/scale-integration/

# 4. Edit configuration in scale-websocket-service.py
# Set your serial port path
nano scale-websocket-service.py

# 5. Run the service
python3 scale-websocket-service.py
```

#### Step 2: Configure POS Settings

1. Navigate to **Settings → POS → Scale Integration**
2. Select **WebSocket Service (Advanced)**
3. Enter WebSocket URL: `ws://localhost:8765` (or IP of service computer)
4. Set reconnect interval (5000ms default is fine)
5. Enable auto-populate and auto-connect
6. Save settings

#### Step 3: Test Connection

1. Ensure backend service is running
2. Go to POS Terminal
3. Click **Scale** button
4. Should show "Connected" automatically
5. Place item on scale - weight should update in real-time

### Running as System Service (Production):

#### Linux/Mac (systemd):
Create `/etc/systemd/system/scale-service.service`:
```ini
[Unit]
Description=POS Scale WebSocket Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/sites/admin.middleworldfarms.org/docs/scale-integration
ExecStart=/usr/bin/node scale-websocket-service.js
Restart=always

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl daemon-reload
sudo systemctl enable scale-service
sudo systemctl start scale-service
```

#### Windows (NSSM):
```powershell
# Download NSSM from nssm.cc
nssm install ScaleService "C:\Program Files\nodejs\node.exe" "C:\path\to\scale-websocket-service.js"
nssm start ScaleService
```

---

## 🔧 Configuration Options

### Settings → POS → Scale Integration

| Setting | Description | Default |
|---------|-------------|---------|
| **Integration Type** | Method for connecting to scale | Manual Entry |
| **Auto-populate weight** | Automatically fill weight field when stable | Off |
| **Auto-connect on load** | Connect to scale when POS opens | Off |
| **Scale Protocol** | Manufacturer-specific data format | Generic |
| **Baud Rate** | Serial communication speed | 9600 |
| **WebSocket URL** | Address of backend service | ws://localhost:8765 |
| **Reconnect Interval** | How often to retry connection | 5000ms |

---

## 🛠️ Supported Scale Protocols

### Generic (Auto-detect)
Works with most scales that output weight as a simple number.

### Mettler Toledo
Format: `S S   12.34 kg`
- First `S` = Stable weight indicator
- Second `S` = Zero balance indicator

### Fairbanks
Format: `WT,+012.34,KG`
- CSV-style format
- Always stable reading

### Ohaus
Format: `12.34 kg S`
- Weight, unit, stability flag

### CAS
Format: Similar to generic with `?` for unstable readings

---

## 📊 Scale Communication Details

### Common Serial Settings:
- **Baud Rate**: 9600 (most common), 4800, 19200, 38400
- **Data Bits**: 8
- **Stop Bits**: 1
- **Parity**: None
- **Flow Control**: None

### Finding Your Scale's Settings:
1. Check the scale manual
2. Look for "RS-232 settings" or "Serial output"
3. Try 9600 baud with generic protocol first
4. Most scales have a setup menu to configure output

---

## 💡 Tips & Best Practices

### For Market Stalls:
- Use **USB scale** with **Web Serial** on a small Windows tablet/laptop
- Or use **iPad** with **WebSocket service** running on a Raspberry Pi

### For Multi-POS Setup:
- Run **WebSocket service** on a central server
- Multiple POS terminals connect to same service
- Requires separate scale per terminal (or manual switching)

### Security:
- WebSocket service should run on **local network only**
- Don't expose port 8765 to the internet
- Use `localhost` for same-device setup
- Use local IP (e.g., `192.168.1.100`) for network setup

### Reliability:
- **Production**: Use WebSocket method for most reliable operation
- Enable **auto-reconnect** to handle temporary disconnections
- Set **auto-populate** only if scale readings are very stable

---

## 🐛 Troubleshooting

### Scale Not Connecting (Web Serial)
```
Problem: Browser doesn't show any devices
Solution:
  1. Ensure using Chrome or Edge (not Firefox/Safari)
  2. Check USB cable connection
  3. Try different USB port
  4. Close other software using the scale (Point of Sale software, scale utilities)
  5. Restart browser
```

### Scale Readings Incorrect
```
Problem: Shows garbled numbers or wrong values
Solution:
  1. Check baud rate matches your scale (try 4800 if 9600 doesn't work)
  2. Try different scale protocol (Mettler Toledo, Fairbanks, etc.)
  3. Check scale is set to kg output (not pounds)
  4. Verify scale manual for output format
```

### WebSocket Won't Connect
```
Problem: "Connection failed" or timeout
Solution:
  1. Verify backend service is running (check terminal/console)
  2. Check WebSocket URL is correct (ws://localhost:8765)
  3. If using network address, ensure firewall allows port 8765
  4. Test service: Open browser and visit http://localhost:8765 (should see error but confirms it's running)
  5. Check service logs for errors
```

### iPad Not Working
```
Problem: Can't connect scale on iPad
Solution:
  1. Web Serial NOT supported on iPad - use WebSocket method
  2. Web Bluetooth very limited on iOS - use WebSocket method
  3. Run WebSocket service on a computer/Raspberry Pi
  4. Connect iPad to same WiFi network
  5. Use network IP instead of localhost (e.g., ws://192.168.1.100:8765)
```

---

## 📚 Example Scales & Configuration

### Example 1: Mettler Toledo PS60
```
Integration: Web Serial (USB)
Protocol: Mettler Toledo
Baud Rate: 9600
Data Format: "S S   12.34 kg"
```

### Example 2: Fairbanks Scales
```
Integration: Web Serial (USB)
Protocol: Fairbanks
Baud Rate: 9600
Data Format: "WT,+012.34,KG"
```

### Example 3: Generic USB Scale (Amazon/eBay)
```
Integration: Web Serial (USB)
Protocol: Generic
Baud Rate: 9600
Data Format: "12.34" (simple number)
```

### Example 4: iPad Setup with Any Scale
```
Integration: WebSocket Service
Backend: Raspberry Pi running Python service
Scale: Any USB scale connected to Raspberry Pi
WebSocket URL: ws://192.168.1.50:8765
```

---

## 🚀 Advanced: Custom Protocol

If your scale uses a unique format not covered above, you can edit the service files:

### Node.js Service
Edit `scale-websocket-service.js`, add to `scaleParsers`:
```javascript
custom_scale: (data) => {
    // Your parsing logic here
    const match = data.match(/YOUR_REGEX_PATTERN/);
    if (match) {
        return {
            weight: parseFloat(match[1]),
            stable: true,
            unit: 'kg'
        };
    }
    return null;
}
```

### Python Service
Edit `scale-websocket-service.py`, add to `ScaleParsers` class:
```python
@staticmethod
def custom_scale(data: str) -> Optional[Dict[str, Any]]:
    """Your custom scale format"""
    match = re.search(r'YOUR_REGEX_PATTERN', data)
    if match:
        return {
            'weight': float(match.group(1)),
            'stable': True,
            'unit': 'kg'
        }
    return None
```

---

## 📞 Support & Resources

### Hardware Compatibility
- Most USB retail scales work with Web Serial or WebSocket
- Scales with RS-232 output work with USB-to-Serial adapters
- Bluetooth scales require BLE (Bluetooth Low Energy)

### Need Help?
- Check scale manual for serial output settings
- Test with generic protocol first
- Enable browser console (F12) to see debug messages
- Check service logs for WebSocket issues

### Contributing
Found a scale that works? Tested a specific model? 
Please contribute configurations back to the project!

---

## 📝 License

This scale integration system is part of the open-source POS platform.
Licensed under MIT - free to use, modify, and distribute.

---

**Happy weighing! 🎯⚖️**
