#!/usr/bin/env node

// Scale WebSocket Service - Node.js Example
//
// This service reads from a USB scale and broadcasts weight data via WebSocket
// Compatible with any scale that outputs data over USB serial connection
//
// Installation:
//   npm install serialport ws
//
// Usage:
//   node scale-websocket-service.js
//
// Configuration:
//   Edit the settings below to match your scale

const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const WebSocket = require('ws');

// ===== CONFIGURATION =====
const CONFIG = {
    // Serial port settings
    serial: {
        path: '/dev/ttyUSB0',      // Linux: /dev/ttyUSB0, Windows: COM3, Mac: /dev/cu.usbserial
        baudRate: 9600,             // Common: 9600, 4800, 19200
        dataBits: 8,
        stopBits: 1,
        parity: 'none'
    },
    
    // WebSocket server settings
    websocket: {
        port: 8765,
        host: 'localhost'
    },
    
    // Scale protocol
    protocol: 'generic',  // Options: generic, mettler_toledo, fairbanks, ohaus, cas
    
    // Stability detection
    stability: {
        threshold: 0.01,     // kg - weight must be stable within this range
        duration: 500        // ms - how long weight must be stable
    }
};

// ===== SCALE PARSERS =====
const scaleParsers = {
    generic: (data) => {
        const match = data.match(/([+-]?[\d.]+)/);
        if (match) {
            return {
                weight: parseFloat(match[1]),
                stable: /\b(S|ST|STABLE)\b/i.test(data)
            };
        }
        return null;
    },
    
    mettler_toledo: (data) => {
        // Format: "S S   12.34 kg\r"
        const match = data.match(/([S\s])([S\s])\s+([\d.]+)\s*(\w+)/);
        if (match) {
            return {
                weight: parseFloat(match[3]),
                stable: match[1] === 'S' && match[2] === 'S',
                unit: match[4]
            };
        }
        return null;
    },
    
    fairbanks: (data) => {
        // Format: "WT,+012.34,KG"
        const match = data.match(/WT,([+-]?[\d.]+),(\w+)/i);
        if (match) {
            return {
                weight: parseFloat(match[1]),
                stable: true,
                unit: match[2]
            };
        }
        return null;
    },
    
    ohaus: (data) => {
        // Format: "12.34 kg S"
        const match = data.match(/([\d.]+)\s*(\w+)\s*([S]?)/);
        if (match) {
            return {
                weight: parseFloat(match[1]),
                stable: match[3] === 'S',
                unit: match[2]
            };
        }
        return null;
    },
    
    cas: (data) => {
        const match = data.match(/([+-]?[\d.]+)/);
        if (match) {
            return {
                weight: parseFloat(match[1]),
                stable: !data.includes('?')
            };
        }
        return null;
    }
};

// ===== MAIN SERVICE =====
class ScaleWebSocketService {
    constructor(config) {
        this.config = config;
        this.serialPort = null;
        this.wss = null;
        this.currentWeight = 0;
        this.lastWeight = 0;
        this.stableTimestamp = null;
        this.parser = scaleParsers[config.protocol] || scaleParsers.generic;
    }

    async start() {
        try {
            // Initialize serial port
            console.log(`Opening serial port ${this.config.serial.path}...`);
            this.serialPort = new SerialPort({
                path: this.config.serial.path,
                baudRate: this.config.serial.baudRate,
                dataBits: this.config.serial.dataBits,
                stopBits: this.config.serial.stopBits,
                parity: this.config.serial.parity
            });

            const lineParser = this.serialPort.pipe(new ReadlineParser({ delimiter: '\n' }));

            this.serialPort.on('error', (err) => {
                console.error('Serial port error:', err.message);
                this.broadcast({ error: err.message });
            });

            lineParser.on('data', (data) => {
                this.handleScaleData(data.trim());
            });

            // Initialize WebSocket server
            console.log(`Starting WebSocket server on ws://${this.config.websocket.host}:${this.config.websocket.port}...`);
            this.wss = new WebSocket.Server({
                host: this.config.websocket.host,
                port: this.config.websocket.port
            });

            this.wss.on('connection', (ws) => {
                console.log('Client connected');
                
                // Send current weight immediately
                ws.send(JSON.stringify({
                    weight: this.currentWeight,
                    stable: this.isStable(),
                    connected: true
                }));

                ws.on('message', (message) => {
                    try {
                        const data = JSON.parse(message);
                        this.handleCommand(data);
                    } catch (error) {
                        console.error('Invalid message from client:', message);
                    }
                });

                ws.on('close', () => {
                    console.log('Client disconnected');
                });
            });

            console.log('Scale WebSocket service started successfully!');
            console.log(`Scale: ${this.config.serial.path} @ ${this.config.serial.baudRate} baud`);
            console.log(`WebSocket: ws://${this.config.websocket.host}:${this.config.websocket.port}`);
            console.log('Waiting for connections...\n');

        } catch (error) {
            console.error('Failed to start service:', error);
            process.exit(1);
        }
    }

    handleScaleData(data) {
        if (!data) return;

        const parsed = this.parser(data);
        if (!parsed || isNaN(parsed.weight)) return;

        this.currentWeight = parsed.weight;

        // Determine stability
        const weightDiff = Math.abs(this.currentWeight - this.lastWeight);
        const isStable = weightDiff <= this.config.stability.threshold;

        if (isStable) {
            if (!this.stableTimestamp) {
                this.stableTimestamp = Date.now();
            }
        } else {
            this.stableTimestamp = null;
        }

        this.lastWeight = this.currentWeight;

        // Broadcast to all connected clients
        this.broadcast({
            weight: this.currentWeight,
            stable: this.isStable(),
            unit: parsed.unit || 'kg',
            timestamp: Date.now()
        });
    }

    isStable() {
        if (!this.stableTimestamp) return false;
        return (Date.now() - this.stableTimestamp) >= this.config.stability.duration;
    }

    handleCommand(data) {
        switch (data.command) {
            case 'zero':
            case 'tare':
                console.log('Zero/tare command received');
                // Send tare command to scale (scale-specific)
                // Most scales accept specific commands, e.g., "T\r\n" for tare
                if (this.serialPort && this.serialPort.isOpen) {
                    this.serialPort.write('T\r\n');
                }
                break;
            
            case 'ping':
                this.broadcast({ pong: true });
                break;
        }
    }

    broadcast(data) {
        if (!this.wss) return;

        const message = JSON.stringify(data);
        this.wss.clients.forEach((client) => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    }

    stop() {
        console.log('Stopping service...');
        if (this.serialPort && this.serialPort.isOpen) {
            this.serialPort.close();
        }
        if (this.wss) {
            this.wss.close();
        }
    }
}

// ===== AUTO-DETECT SERIAL PORT =====
async function autoDetectSerialPort() {
    const { SerialPort } = require('serialport');
    const ports = await SerialPort.list();
    
    console.log('Available serial ports:');
    ports.forEach((port, index) => {
        console.log(`  [${index}] ${port.path} - ${port.manufacturer || 'Unknown'}`);
    });
    
    if (ports.length === 0) {
        console.log('  No serial ports found!');
        return null;
    }
    
    // Return first port with known scale manufacturer or first USB serial port
    const scalePort = ports.find(p => 
        p.manufacturer && /mettler|ohaus|avery|cas|fairbanks|adam/i.test(p.manufacturer)
    ) || ports.find(p => /usb|serial/i.test(p.path));
    
    return scalePort ? scalePort.path : ports[0].path;
}

// ===== START SERVICE =====
(async () => {
    console.log('=== Scale WebSocket Service ===\n');
    
    // Auto-detect serial port if not configured
    if (!CONFIG.serial.path || CONFIG.serial.path === '/dev/ttyUSB0') {
        const detectedPort = await autoDetectSerialPort();
        if (detectedPort) {
            console.log(`Auto-detected serial port: ${detectedPort}\n`);
            CONFIG.serial.path = detectedPort;
        } else {
            console.error('No serial port detected. Please configure CONFIG.serial.path manually.');
            process.exit(1);
        }
    }
    
    const service = new ScaleWebSocketService(CONFIG);
    service.start();

    // Graceful shutdown
    process.on('SIGINT', () => {
        console.log('\nShutting down...');
        service.stop();
        process.exit(0);
    });
})();
