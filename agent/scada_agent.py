#!/usr/bin/env python3
"""
SCADA Agent - Lightweight PLC/RTU Data Collection Agent
Collects data from PLCs and RTUs and submits to SCADA central server

Requirements:
- pymodbus (for Modbus communication)
- requests (for API communication)
- python 3.7+

Install: pip install pymodbus requests
"""

import sys
import time
import json
import logging
import socket
import platform
import configparser
from datetime import datetime
from typing import List, Dict, Any
import requests

try:
    from pymodbus.client import ModbusTcpClient
    from pymodbus.constants import Endian
    from pymodbus.payload import BinaryPayloadDecoder
except ImportError:
    print("ERROR: pymodbus not installed. Run: pip install pymodbus")
    sys.exit(1)


class SCADAAgent:
    """Lightweight SCADA agent for PLC/RTU data collection"""

    VERSION = "1.0.0"

    def __init__(self, config_file="agent_config.ini"):
        """Initialize the SCADA agent"""
        self.config_file = config_file
        self.config = None
        self.api_url = None
        self.api_key = None
        self.plc_ip = None
        self.plc_port = 502
        self.poll_interval = 5
        self.heartbeat_interval = 60
        self.batch_size = 100
        self.running = False
        self.last_heartbeat = 0
        self.tags = []

        # Setup logging
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler('scada_agent.log'),
                logging.StreamHandler()
            ]
        )
        self.logger = logging.getLogger('SCADAAgent')

        # Load configuration
        self.load_config()

    def load_config(self):
        """Load configuration from INI file"""
        self.logger.info(f"Loading configuration from {self.config_file}")

        self.config = configparser.ConfigParser()
        self.config.read(self.config_file)

        # API Configuration
        self.api_url = self.config.get('api', 'url', fallback='http://localhost/networkscanscada/scada_agent_api.php')
        self.api_key = self.config.get('api', 'api_key', fallback='')

        if not self.api_key:
            self.logger.error("API key not configured! Set api_key in [api] section")
            sys.exit(1)

        # PLC Configuration
        self.plc_ip = self.config.get('plc', 'ip_address', fallback='192.168.1.100')
        self.plc_port = self.config.getint('plc', 'port', fallback=502)
        self.plc_id = self.config.getint('plc', 'plc_id', fallback=1)

        # Agent Configuration
        self.poll_interval = self.config.getint('agent', 'poll_interval', fallback=5)
        self.heartbeat_interval = self.config.getint('agent', 'heartbeat_interval', fallback=60)
        self.batch_size = self.config.getint('agent', 'batch_size', fallback=100)

        self.logger.info(f"Configuration loaded: API={self.api_url}, PLC={self.plc_ip}:{self.plc_port}")

    def get_system_info(self) -> Dict[str, Any]:
        """Get system information for heartbeat"""
        return {
            'hostname': socket.gethostname(),
            'platform': platform.system(),
            'platform_release': platform.release(),
            'platform_version': platform.version(),
            'architecture': platform.machine(),
            'processor': platform.processor(),
            'python_version': platform.python_version()
        }

    def send_heartbeat(self):
        """Send heartbeat to server"""
        try:
            payload = {
                'agent_version': self.VERSION,
                'hostname': socket.gethostname(),
                'system_info': self.get_system_info(),
                'cpu_usage': 0.0,  # Could use psutil for real metrics
                'memory_usage': 0.0,
                'disk_usage': 0.0,
                'uptime_seconds': int(time.time() - self.last_heartbeat)
            }

            response = requests.post(
                f"{self.api_url}?action=heartbeat",
                headers={'X-API-Key': self.api_key},
                json=payload,
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.logger.info("Heartbeat sent successfully")
                    self.last_heartbeat = time.time()
                else:
                    self.logger.warning(f"Heartbeat failed: {data.get('message')}")
            else:
                self.logger.error(f"Heartbeat HTTP error: {response.status_code}")

        except Exception as e:
            self.logger.error(f"Heartbeat error: {e}")

    def fetch_tags_from_server(self) -> List[Dict]:
        """Fetch list of tags to monitor from server"""
        try:
            response = requests.get(
                f"{self.api_url}?action=get_tags&plc_id={self.plc_id}",
                headers={'X-API-Key': self.api_key},
                timeout=10
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    tags = data.get('data', {}).get('tags', [])
                    self.logger.info(f"Fetched {len(tags)} tags from server")
                    return tags

            self.logger.error("Failed to fetch tags from server")
            return []

        except Exception as e:
            self.logger.error(f"Error fetching tags: {e}")
            return []

    def read_modbus_tag(self, client: ModbusTcpClient, tag: Dict) -> Dict:
        """Read a single tag from Modbus"""
        try:
            address = tag['modbus_address']
            data_type = tag['data_type']
            tag_name = tag['tag_name']

            # Determine register type and read
            if tag['tag_type'] in ['AI', 'AO']:
                # Analog - Holding Registers (address 40001+)
                result = client.read_holding_registers(address - 40001, 2, slave=1)

                if result.isError():
                    return {
                        'tag_name': tag_name,
                        'value': None,
                        'quality': 'bad',
                        'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'error': str(result)
                    }

                # Decode based on data type
                decoder = BinaryPayloadDecoder.fromRegisters(
                    result.registers,
                    byteorder=Endian.Big,
                    wordorder=Endian.Big
                )

                if data_type == 'float32':
                    value = decoder.decode_32bit_float()
                elif data_type == 'uint16':
                    value = decoder.decode_16bit_uint()
                elif data_type == 'int16':
                    value = decoder.decode_16bit_int()
                elif data_type == 'uint32':
                    value = decoder.decode_32bit_uint()
                else:
                    value = decoder.decode_16bit_uint()

            elif tag['tag_type'] in ['DI']:
                # Digital Input - Input Status (address 10001+)
                result = client.read_discrete_inputs(address - 10001, 1, slave=1)

                if result.isError():
                    return {
                        'tag_name': tag_name,
                        'value': None,
                        'quality': 'bad',
                        'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'error': str(result)
                    }

                value = 1.0 if result.bits[0] else 0.0

            elif tag['tag_type'] in ['DO']:
                # Digital Output - Coils (address 00001+)
                result = client.read_coils(address - 1, 1, slave=1)

                if result.isError():
                    return {
                        'tag_name': tag_name,
                        'value': None,
                        'quality': 'bad',
                        'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                        'error': str(result)
                    }

                value = 1.0 if result.bits[0] else 0.0

            else:
                value = 0.0

            return {
                'tag_name': tag_name,
                'value': float(value),
                'quality': 'good',
                'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }

        except Exception as e:
            self.logger.error(f"Error reading tag {tag['tag_name']}: {e}")
            return {
                'tag_name': tag['tag_name'],
                'value': None,
                'quality': 'bad',
                'timestamp': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                'error': str(e)
            }

    def submit_data(self, readings: List[Dict]):
        """Submit tag readings to server"""
        if not readings:
            return

        try:
            payload = {
                'type': 'tag_data',
                'plc_id': self.plc_id,
                'readings': readings
            }

            response = requests.post(
                f"{self.api_url}?action=submit_data",
                headers={
                    'X-API-Key': self.api_key,
                    'Content-Type': 'application/json'
                },
                json=payload,
                timeout=30
            )

            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    self.logger.info(f"Submitted {len(readings)} readings successfully")
                    result = data.get('data', {})
                    if result.get('errors', 0) > 0:
                        self.logger.warning(f"Submission had {result['errors']} errors")
                else:
                    self.logger.error(f"Submission failed: {data.get('message')}")
            else:
                self.logger.error(f"Submission HTTP error: {response.status_code}")

        except Exception as e:
            self.logger.error(f"Error submitting data: {e}")

    def run(self):
        """Main agent loop"""
        self.logger.info(f"SCADA Agent v{self.VERSION} starting...")
        self.logger.info(f"PLC: {self.plc_ip}:{self.plc_port}")
        self.logger.info(f"Poll Interval: {self.poll_interval}s")

        # Send initial heartbeat
        self.send_heartbeat()

        # Fetch tags from server
        self.tags = self.fetch_tags_from_server()

        if not self.tags:
            self.logger.error("No tags configured. Exiting.")
            return

        self.logger.info(f"Monitoring {len(self.tags)} tags")

        self.running = True

        # Create Modbus client
        client = ModbusTcpClient(self.plc_ip, port=self.plc_port, timeout=5)

        try:
            while self.running:
                loop_start = time.time()

                # Connect to PLC
                if not client.connect():
                    self.logger.error(f"Failed to connect to PLC {self.plc_ip}:{self.plc_port}")
                    time.sleep(self.poll_interval)
                    continue

                # Read all tags
                readings = []
                for tag in self.tags:
                    reading = self.read_modbus_tag(client, tag)
                    readings.append(reading)

                    # Submit in batches
                    if len(readings) >= self.batch_size:
                        self.submit_data(readings)
                        readings = []

                # Submit remaining readings
                if readings:
                    self.submit_data(readings)

                # Close connection
                client.close()

                # Send heartbeat if needed
                if time.time() - self.last_heartbeat > self.heartbeat_interval:
                    self.send_heartbeat()

                # Sleep for remainder of poll interval
                elapsed = time.time() - loop_start
                sleep_time = max(0, self.poll_interval - elapsed)

                if sleep_time > 0:
                    self.logger.debug(f"Sleeping for {sleep_time:.2f}s")
                    time.sleep(sleep_time)

        except KeyboardInterrupt:
            self.logger.info("Received shutdown signal")
        except Exception as e:
            self.logger.error(f"Fatal error: {e}", exc_info=True)
        finally:
            self.running = False
            client.close()
            self.logger.info("Agent stopped")


def main():
    """Main entry point"""
    print(f"SCADA Agent v{SCADAAgent.VERSION}")
    print("=" * 50)

    config_file = sys.argv[1] if len(sys.argv) > 1 else "agent_config.ini"

    agent = SCADAAgent(config_file)
    agent.run()


if __name__ == "__main__":
    main()
