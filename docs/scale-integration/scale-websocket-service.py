#!/usr/bin/env python3
"""
Scale WebSocket Service - Python Example

This service reads from a USB scale and broadcasts weight data via WebSocket
Compatible with any scale that outputs data over USB serial connection

Installation:
    pip install pyserial websockets asyncio

Usage:
    python3 scale-websocket-service.py

Configuration:
    Edit the CONFIG dictionary below to match your scale
"""

import asyncio
import json
import re
import serial
import websockets
from datetime import datetime
from typing import Optional, Dict, Any

# ===== CONFIGURATION =====
CONFIG = {
    # Serial port settings
    'serial': {
        'port': '/dev/ttyUSB0',      # Linux: /dev/ttyUSB0, Windows: COM3, Mac: /dev/cu.usbserial
        'baudrate': 9600,             # Common: 9600, 4800, 19200
        'bytesize': 8,
        'stopbits': 1,
        'parity': 'N',                # N=None, E=Even, O=Odd
        'timeout': 1
    },
    
    # WebSocket server settings
    'websocket': {
        'host': 'localhost',
        'port': 8765
    },
    
    # Scale protocol
    'protocol': 'generic',  # Options: generic, mettler_toledo, fairbanks, ohaus, cas
    
    # Stability detection
    'stability': {
        'threshold': 0.01,     # kg - weight must be stable within this range
        'duration': 0.5        # seconds - how long weight must be stable
    }
}


class ScaleParsers:
    """Parsers for different scale protocols"""
    
    @staticmethod
    def generic(data: str) -> Optional[Dict[str, Any]]:
        """Generic parser - extracts first number found"""
        match = re.search(r'([+-]?[\d.]+)', data)
        if match:
            return {
                'weight': float(match.group(1)),
                'stable': bool(re.search(r'\b(S|ST|STABLE)\b', data, re.IGNORECASE))
            }
        return None
    
    @staticmethod
    def mettler_toledo(data: str) -> Optional[Dict[str, Any]]:
        """Mettler Toledo format: 'S S   12.34 kg'"""
        match = re.match(r'([S\s])([S\s])\s+([\d.]+)\s*(\w+)', data)
        if match:
            return {
                'weight': float(match.group(3)),
                'stable': match.group(1) == 'S' and match.group(2) == 'S',
                'unit': match.group(4)
            }
        return None
    
    @staticmethod
    def fairbanks(data: str) -> Optional[Dict[str, Any]]:
        """Fairbanks format: 'WT,+012.34,KG'"""
        match = re.search(r'WT,([+-]?[\d.]+),(\w+)', data, re.IGNORECASE)
        if match:
            return {
                'weight': float(match.group(1)),
                'stable': True,  # Fairbanks usually sends only stable weights
                'unit': match.group(2)
            }
        return None
    
    @staticmethod
    def ohaus(data: str) -> Optional[Dict[str, Any]]:
        """Ohaus format: '12.34 kg S'"""
        match = re.match(r'([\d.]+)\s*(\w+)\s*([S]?)', data)
        if match:
            return {
                'weight': float(match.group(1)),
                'stable': match.group(3) == 'S',
                'unit': match.group(2)
            }
        return None
    
    @staticmethod
    def cas(data: str) -> Optional[Dict[str, Any]]:
        """CAS format"""
        match = re.search(r'([+-]?[\d.]+)', data)
        if match:
            return {
                'weight': float(match.group(1)),
                'stable': '?' not in data  # '?' indicates unstable
            }
        return None


class ScaleWebSocketService:
    """Main service class"""
    
    def __init__(self, config: dict):
        self.config = config
        self.serial_port: Optional[serial.Serial] = None
        self.connected_clients = set()
        self.current_weight = 0.0
        self.last_weight = 0.0
        self.stable_timestamp: Optional[float] = None
        
        # Get parser function
        parser_name = config['protocol']
        self.parser = getattr(ScaleParsers, parser_name, ScaleParsers.generic)
    
    def open_serial_port(self):
        """Open serial connection to scale"""
        try:
            self.serial_port = serial.Serial(
                port=self.config['serial']['port'],
                baudrate=self.config['serial']['baudrate'],
                bytesize=self.config['serial']['bytesize'],
                stopbits=self.config['serial']['stopbits'],
                parity=self.config['serial']['parity'],
                timeout=self.config['serial']['timeout']
            )
            print(f"✓ Serial port opened: {self.config['serial']['port']}")
            return True
        except serial.SerialException as e:
            print(f"✗ Failed to open serial port: {e}")
            return False
    
    def handle_scale_data(self, data: str):
        """Process data from scale"""
        if not data:
            return
        
        parsed = self.parser(data)
        if not parsed or 'weight' not in parsed:
            return
        
        weight = parsed['weight']
        if weight is None or not isinstance(weight, (int, float)):
            return
        
        self.current_weight = float(weight)
        
        # Determine stability
        weight_diff = abs(self.current_weight - self.last_weight)
        is_stable = weight_diff <= self.config['stability']['threshold']
        
        if is_stable:
            if self.stable_timestamp is None:
                self.stable_timestamp = asyncio.get_event_loop().time()
        else:
            self.stable_timestamp = None
        
        self.last_weight = self.current_weight
        
        # Prepare message
        message = {
            'weight': self.current_weight,
            'stable': self.is_stable(),
            'unit': parsed.get('unit', 'kg'),
            'timestamp': datetime.now().isoformat()
        }
        
        # Broadcast to all clients
        asyncio.create_task(self.broadcast(message))
    
    def is_stable(self) -> bool:
        """Check if current weight is stable"""
        if self.stable_timestamp is None:
            return False
        return (asyncio.get_event_loop().time() - self.stable_timestamp) >= self.config['stability']['duration']
    
    async def broadcast(self, message: dict):
        """Send message to all connected clients"""
        if not self.connected_clients:
            return
        
        message_str = json.dumps(message)
        await asyncio.gather(
            *[client.send(message_str) for client in self.connected_clients],
            return_exceptions=True
        )
    
    async def handle_client(self, websocket, path):
        """Handle WebSocket client connection"""
        print(f"✓ Client connected from {websocket.remote_address}")
        self.connected_clients.add(websocket)
        
        try:
            # Send current weight immediately
            await websocket.send(json.dumps({
                'weight': self.current_weight,
                'stable': self.is_stable(),
                'connected': True
            }))
            
            # Listen for commands from client
            async for message in websocket:
                try:
                    data = json.loads(message)
                    await self.handle_command(data, websocket)
                except json.JSONDecodeError:
                    print(f"Invalid JSON from client: {message}")
        
        except websockets.exceptions.ConnectionClosed:
            pass
        finally:
            self.connected_clients.remove(websocket)
            print(f"✗ Client disconnected from {websocket.remote_address}")
    
    async def handle_command(self, data: dict, websocket):
        """Handle commands from client"""
        command = data.get('command')
        
        if command in ('zero', 'tare'):
            print(f"Zero/tare command received")
            # Send tare command to scale (scale-specific)
            # Most scales accept 'T' or 'Z' for tare/zero
            if self.serial_port and self.serial_port.is_open:
                self.serial_port.write(b'T\r\n')
        
        elif command == 'ping':
            await websocket.send(json.dumps({'pong': True}))
    
    async def read_serial_loop(self):
        """Continuously read from serial port"""
        if not self.serial_port:
            return
        
        print("✓ Reading from scale...")
        
        while True:
            try:
                if self.serial_port.in_waiting > 0:
                    line = self.serial_port.readline()
                    try:
                        data = line.decode('utf-8', errors='ignore').strip()
                        self.handle_scale_data(data)
                    except UnicodeDecodeError:
                        pass
                
                await asyncio.sleep(0.01)  # Small delay to prevent CPU spinning
            
            except serial.SerialException as e:
                print(f"Serial error: {e}")
                await asyncio.sleep(1)
    
    async def start(self):
        """Start the service"""
        print("=== Scale WebSocket Service ===\n")
        
        # Open serial port
        if not self.open_serial_port():
            print("Exiting due to serial port error")
            return
        
        # Start WebSocket server
        ws_host = self.config['websocket']['host']
        ws_port = self.config['websocket']['port']
        
        print(f"✓ WebSocket server starting on ws://{ws_host}:{ws_port}")
        
        async with websockets.serve(self.handle_client, ws_host, ws_port):
            print(f"\n✓ Service started successfully!")
            print(f"  Scale: {self.config['serial']['port']} @ {self.config['serial']['baudrate']} baud")
            print(f"  WebSocket: ws://{ws_host}:{ws_port}")
            print(f"  Protocol: {self.config['protocol']}")
            print(f"\nWaiting for connections...\n")
            
            # Run serial reading loop
            await self.read_serial_loop()
    
    def stop(self):
        """Stop the service"""
        print("\nStopping service...")
        if self.serial_port and self.serial_port.is_open:
            self.serial_port.close()
            print("✓ Serial port closed")


def auto_detect_serial_port() -> Optional[str]:
    """Auto-detect serial port with connected scale"""
    import serial.tools.list_ports
    
    ports = list(serial.tools.list_ports.comports())
    
    if not ports:
        return None
    
    print("Available serial ports:")
    for i, port in enumerate(ports):
        print(f"  [{i}] {port.device} - {port.manufacturer or 'Unknown'}")
    
    # Look for known scale manufacturers
    scale_keywords = ['mettler', 'ohaus', 'avery', 'cas', 'fairbanks', 'adam']
    for port in ports:
        if port.manufacturer and any(kw in port.manufacturer.lower() for kw in scale_keywords):
            return port.device
    
    # Fallback to first USB serial device
    for port in ports:
        if 'usb' in port.device.lower() or 'serial' in port.device.lower():
            return port.device
    
    return ports[0].device


if __name__ == '__main__':
    import sys
    
    # Auto-detect serial port if needed
    if not CONFIG['serial']['port'] or CONFIG['serial']['port'] == '/dev/ttyUSB0':
        detected_port = auto_detect_serial_port()
        if detected_port:
            print(f"Auto-detected serial port: {detected_port}\n")
            CONFIG['serial']['port'] = detected_port
        else:
            print("No serial port detected. Please configure CONFIG['serial']['port'] manually.")
            sys.exit(1)
    
    # Create and start service
    service = ScaleWebSocketService(CONFIG)
    
    try:
        asyncio.run(service.start())
    except KeyboardInterrupt:
        service.stop()
        print("✓ Service stopped")
