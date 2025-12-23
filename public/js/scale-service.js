/**
 * Scale Integration Service
 * 
 * Provides universal scale integration for POS systems
 * Supports: Manual Entry, Web Serial (USB), Web Bluetooth (BLE), WebSocket (Backend Service)
 * 
 * @author MiddleWorld Farms
 * @license MIT
 */

class ScaleService {
    constructor(config = {}) {
        this.config = {
            integration: config.integration || 'manual', // manual, web_serial, web_bluetooth, websocket
            autoPopulate: config.autoPopulate || false,
            autoConnect: config.autoConnect || false,
            protocol: config.protocol || 'generic',
            baudRate: config.baudRate || 9600,
            websocketUrl: config.websocketUrl || 'ws://localhost:8765',
            reconnectInterval: config.reconnectInterval || 5000,
            bluetoothServiceUUID: config.bluetoothServiceUUID || null,
            ...config
        };

        this.connected = false;
        this.currentWeight = 0;
        this.isStable = false;
        this.device = null;
        this.port = null;
        this.reader = null;
        this.websocket = null;
        this.bluetoothDevice = null;
        this.listeners = {
            weightUpdate: [],
            connectionChange: [],
            error: []
        };

        // Auto-connect if enabled
        if (this.config.autoConnect) {
            this.connect();
        }
    }

    /**
     * Connect to scale based on integration type
     */
    async connect() {
        try {
            switch (this.config.integration) {
                case 'web_serial':
                    return await this.connectWebSerial();
                case 'web_bluetooth':
                    return await this.connectWebBluetooth();
                case 'websocket':
                    return await this.connectWebSocket();
                case 'manual':
                default:
                    this.notifyConnectionChange(false, 'Manual entry mode - no scale connection required');
                    return false;
            }
        } catch (error) {
            this.notifyError(`Connection failed: ${error.message}`);
            return false;
        }
    }

    /**
     * Disconnect from scale
     */
    async disconnect() {
        try {
            if (this.reader) {
                await this.reader.cancel();
                this.reader = null;
            }
            if (this.port) {
                await this.port.close();
                this.port = null;
            }
            if (this.websocket) {
                this.websocket.close();
                this.websocket = null;
            }
            if (this.bluetoothDevice && this.bluetoothDevice.gatt.connected) {
                await this.bluetoothDevice.gatt.disconnect();
                this.bluetoothDevice = null;
            }
            
            this.connected = false;
            this.currentWeight = 0;
            this.isStable = false;
            this.notifyConnectionChange(false, 'Disconnected');
            return true;
        } catch (error) {
            this.notifyError(`Disconnect failed: ${error.message}`);
            return false;
        }
    }

    /**
     * Web Serial API - USB Scales (Chrome/Edge)
     */
    async connectWebSerial() {
        if (!('serial' in navigator)) {
            throw new Error('Web Serial API not supported. Use Chrome or Edge browser.');
        }

        // Request port from user
        this.port = await navigator.serial.requestPort();
        
        // Open port with configured settings
        await this.port.open({
            baudRate: this.config.baudRate,
            dataBits: 8,
            stopBits: 1,
            parity: 'none',
            flowControl: 'none'
        });

        this.connected = true;
        this.notifyConnectionChange(true, 'USB scale connected via Web Serial API');

        // Start reading data
        this.startSerialReading();

        return true;
    }

    /**
     * Read data from serial port
     */
    async startSerialReading() {
        const decoder = new TextDecoderStream();
        this.port.readable.pipeTo(decoder.writable);
        this.reader = decoder.readable.getReader();

        let buffer = '';

        try {
            while (true) {
                const { value, done } = await this.reader.read();
                if (done) break;

                buffer += value;
                const lines = buffer.split('\n');
                buffer = lines.pop(); // Keep incomplete line in buffer

                for (const line of lines) {
                    this.parseSerialData(line.trim());
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.notifyError(`Serial reading error: ${error.message}`);
            }
        }
    }

    /**
     * Parse scale data based on protocol
     */
    parseSerialData(data) {
        if (!data) return;

        let weight = null;
        let stable = false;

        switch (this.config.protocol) {
            case 'mettler_toledo':
                // Mettler Toledo format: "S S   12.34 kg\r"
                const mtMatch = data.match(/([S\s])([S\s])\s+([\d.]+)\s*(\w+)/);
                if (mtMatch) {
                    stable = mtMatch[1] === 'S' && mtMatch[2] === 'S';
                    weight = parseFloat(mtMatch[3]);
                }
                break;

            case 'fairbanks':
                // Fairbanks format: typically "WT,+012.34,KG"
                const fbMatch = data.match(/WT,([+-]?[\d.]+),(\w+)/i);
                if (fbMatch) {
                    weight = parseFloat(fbMatch[1]);
                    stable = true; // Fairbanks usually sends only stable weights
                }
                break;

            case 'ohaus':
                // Ohaus format: "12.34 kg S"
                const ohMatch = data.match(/([\d.]+)\s*(\w+)\s*([S]?)/);
                if (ohMatch) {
                    weight = parseFloat(ohMatch[1]);
                    stable = ohMatch[3] === 'S';
                }
                break;

            case 'cas':
                // CAS format: similar to generic
                const casMatch = data.match(/([+-]?[\d.]+)/);
                if (casMatch) {
                    weight = parseFloat(casMatch[1]);
                    stable = !data.includes('?'); // '?' indicates unstable
                }
                break;

            case 'generic':
            default:
                // Generic: extract first number
                const genericMatch = data.match(/([+-]?[\d.]+)/);
                if (genericMatch) {
                    weight = parseFloat(genericMatch[1]);
                    // Check for stability indicators (S, ST, STABLE)
                    stable = /\b(S|ST|STABLE)\b/i.test(data);
                }
                break;
        }

        if (weight !== null && !isNaN(weight)) {
            this.updateWeight(weight, stable);
        }
    }

    /**
     * Web Bluetooth API - Bluetooth Scales (Limited Support)
     */
    async connectWebBluetooth() {
        if (!('bluetooth' in navigator)) {
            throw new Error('Web Bluetooth API not supported. Try Chrome on desktop or Android.');
        }

        if (!this.config.bluetoothServiceUUID) {
            throw new Error('Bluetooth Service UUID not configured in settings');
        }

        // Request device
        this.bluetoothDevice = await navigator.bluetooth.requestDevice({
            filters: [
                { services: [this.config.bluetoothServiceUUID] }
            ]
        });

        // Connect to GATT server
        const server = await this.bluetoothDevice.gatt.connect();
        const service = await server.getPrimaryService(this.config.bluetoothServiceUUID);
        
        // Get characteristic (assumes weight characteristic is first)
        const characteristics = await service.getCharacteristics();
        if (characteristics.length === 0) {
            throw new Error('No characteristics found on Bluetooth scale');
        }

        const weightCharacteristic = characteristics[0];
        
        // Subscribe to notifications
        await weightCharacteristic.startNotifications();
        weightCharacteristic.addEventListener('characteristicvaluechanged', (event) => {
            const value = event.target.value;
            const weight = this.parseBluetoothValue(value);
            if (weight !== null) {
                this.updateWeight(weight, true); // Assume stable for BLE
            }
        });

        this.connected = true;
        this.notifyConnectionChange(true, `Bluetooth scale connected: ${this.bluetoothDevice.name}`);

        return true;
    }

    /**
     * Parse Bluetooth characteristic value
     */
    parseBluetoothValue(dataView) {
        // Common format: 4-byte float (little-endian)
        if (dataView.byteLength >= 4) {
            return dataView.getFloat32(0, true);
        }
        
        // Alternative: 2-byte integer (grams)
        if (dataView.byteLength >= 2) {
            const grams = dataView.getUint16(0, true);
            return grams / 1000; // Convert to kg
        }

        return null;
    }

    /**
     * WebSocket - Backend Service (Most Flexible)
     */
    async connectWebSocket() {
        return new Promise((resolve, reject) => {
            try {
                this.websocket = new WebSocket(this.config.websocketUrl);

                this.websocket.onopen = () => {
                    this.connected = true;
                    this.notifyConnectionChange(true, `Connected to scale service at ${this.config.websocketUrl}`);
                    resolve(true);
                };

                this.websocket.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        if (data.weight !== undefined) {
                            this.updateWeight(
                                parseFloat(data.weight), 
                                data.stable !== undefined ? data.stable : true
                            );
                        }
                    } catch (error) {
                        this.notifyError(`WebSocket message parse error: ${error.message}`);
                    }
                };

                this.websocket.onerror = (error) => {
                    this.notifyError(`WebSocket error: ${error.message || 'Connection failed'}`);
                    reject(error);
                };

                this.websocket.onclose = () => {
                    this.connected = false;
                    this.notifyConnectionChange(false, 'WebSocket connection closed');
                    
                    // Auto-reconnect if enabled
                    if (this.config.autoConnect) {
                        setTimeout(() => {
                            this.connectWebSocket().catch(err => 
                                console.error('Auto-reconnect failed:', err)
                            );
                        }, this.config.reconnectInterval);
                    }
                };

                // Timeout after 5 seconds
                setTimeout(() => {
                    if (!this.connected) {
                        reject(new Error('Connection timeout'));
                    }
                }, 5000);

            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Update weight and notify listeners
     */
    updateWeight(weight, stable = false) {
        this.currentWeight = weight;
        this.isStable = stable;
        this.notifyWeightUpdate(weight, stable);
    }

    /**
     * Get current weight
     */
    getWeight() {
        return {
            weight: this.currentWeight,
            stable: this.isStable,
            connected: this.connected
        };
    }

    /**
     * Zero/tare the scale
     */
    async zero() {
        if (this.config.integration === 'websocket' && this.websocket && this.connected) {
            this.websocket.send(JSON.stringify({ command: 'zero' }));
            return true;
        }
        
        // For other integrations, we can't send commands, so just reset local weight
        this.currentWeight = 0;
        this.notifyWeightUpdate(0, false);
        return false;
    }

    /**
     * Event Listeners
     */
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    }

    notifyWeightUpdate(weight, stable) {
        this.listeners.weightUpdate.forEach(callback => {
            try {
                callback({ weight, stable });
            } catch (error) {
                console.error('Weight update listener error:', error);
            }
        });
    }

    notifyConnectionChange(connected, message) {
        this.listeners.connectionChange.forEach(callback => {
            try {
                callback({ connected, message });
            } catch (error) {
                console.error('Connection change listener error:', error);
            }
        });
    }

    notifyError(message) {
        this.listeners.error.forEach(callback => {
            try {
                callback({ message });
            } catch (error) {
                console.error('Error listener error:', error);
            }
        });
    }

    /**
     * Check browser compatibility
     */
    static checkCompatibility() {
        return {
            webSerial: 'serial' in navigator,
            webBluetooth: 'bluetooth' in navigator,
            webSocket: 'WebSocket' in window,
            recommended: function() {
                if (this.webSerial) return 'web_serial';
                if (this.webSocket) return 'websocket';
                if (this.webBluetooth) return 'web_bluetooth';
                return 'manual';
            }
        };
    }
}

// Export for use in modules or make globally available
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScaleService;
} else {
    window.ScaleService = ScaleService;
}
