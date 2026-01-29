<?php
/**
 * Modbus Protocol Handler
 * Implements Modbus TCP and Modbus RTU protocols
 * Industry standard for PLC and industrial device communication
 *
 * @author IOC Platform
 * @version 2.0
 */

class ModbusProtocol {
    private $mode; // 'tcp' or 'rtu'
    private $socket;
    private $serialPort;
    private $connected = false;
    private $transactionId = 0;
    private $timeout = 5;
    private $lastError;

    // Modbus function codes
    const FC_READ_COILS = 0x01;
    const FC_READ_DISCRETE_INPUTS = 0x02;
    const FC_READ_HOLDING_REGISTERS = 0x03;
    const FC_READ_INPUT_REGISTERS = 0x04;
    const FC_WRITE_SINGLE_COIL = 0x05;
    const FC_WRITE_SINGLE_REGISTER = 0x06;
    const FC_WRITE_MULTIPLE_COILS = 0x0F;
    const FC_WRITE_MULTIPLE_REGISTERS = 0x10;

    public function __construct($mode = 'tcp') {
        $this->mode = $mode;
    }

    /**
     * Connect to Modbus TCP device
     */
    public function connect($host, $port = 502, $timeout = 5000) {
        try {
            $this->timeout = $timeout / 1000; // Convert to seconds

            if ($this->mode === 'tcp') {
                $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

                if (!$this->socket) {
                    $this->lastError = "Connection failed: $errstr ($errno)";
                    return false;
                }

                stream_set_timeout($this->socket, $this->timeout);
                $this->connected = true;
                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Connect to Modbus RTU device via serial port
     */
    public function connectSerial($port, $baudRate = 9600, $parity = 'N', $dataBits = 8, $stopBits = 1) {
        try {
            if ($this->mode !== 'rtu') {
                $this->lastError = "Not in RTU mode";
                return false;
            }

            // Configure serial port (Linux/Unix)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows serial port configuration
                $command = "mode $port: BAUD=$baudRate PARITY=$parity DATA=$dataBits STOP=$stopBits";
            } else {
                // Linux serial port configuration
                $command = "stty -F $port $baudRate cs$dataBits";
                if ($parity === 'N') {
                    $command .= " -parenb";
                } else if ($parity === 'E') {
                    $command .= " parenb -parodd";
                } else if ($parity === 'O') {
                    $command .= " parenb parodd";
                }
            }

            exec($command);

            // Open serial port
            $this->serialPort = @fopen($port, "r+b");

            if (!$this->serialPort) {
                $this->lastError = "Failed to open serial port: $port";
                return false;
            }

            stream_set_blocking($this->serialPort, true);
            stream_set_timeout($this->serialPort, $this->timeout);

            $this->connected = true;
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Connect via GSM modem
     */
    public function connectGSM($phoneNumber) {
        try {
            // GSM modem AT commands would go here
            // This is a simplified implementation
            // In production, would use specific GSM modem libraries

            $this->connected = true;
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Read holding registers (FC03)
     */
    public function readHoldingRegisters($unitId, $startAddress, $quantity = 1) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $request = $this->buildRequest($unitId, self::FC_READ_HOLDING_REGISTERS, [
                'address' => $startAddress,
                'quantity' => $quantity
            ]);

            $response = $this->sendRequest($request);

            if ($response === false) {
                return false;
            }

            return $this->parseRegisterResponse($response, $quantity);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Read input registers (FC04)
     */
    public function readInputRegisters($unitId, $startAddress, $quantity = 1) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $request = $this->buildRequest($unitId, self::FC_READ_INPUT_REGISTERS, [
                'address' => $startAddress,
                'quantity' => $quantity
            ]);

            $response = $this->sendRequest($request);

            if ($response === false) {
                return false;
            }

            return $this->parseRegisterResponse($response, $quantity);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Read coils (FC01)
     */
    public function readCoils($unitId, $startAddress, $quantity = 1) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $request = $this->buildRequest($unitId, self::FC_READ_COILS, [
                'address' => $startAddress,
                'quantity' => $quantity
            ]);

            $response = $this->sendRequest($request);

            if ($response === false) {
                return false;
            }

            return $this->parseCoilResponse($response, $quantity);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Write single register (FC06)
     */
    public function writeSingleRegister($unitId, $address, $value) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $request = $this->buildRequest($unitId, self::FC_WRITE_SINGLE_REGISTER, [
                'address' => $address,
                'value' => $value
            ]);

            $response = $this->sendRequest($request);

            return $response !== false;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Write single coil (FC05)
     */
    public function writeSingleCoil($unitId, $address, $value) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $coilValue = $value ? 0xFF00 : 0x0000;

            $request = $this->buildRequest($unitId, self::FC_WRITE_SINGLE_COIL, [
                'address' => $address,
                'value' => $coilValue
            ]);

            $response = $this->sendRequest($request);

            return $response !== false;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Generic read function based on address format
     */
    public function readAddress($address, $dataType = 'uint16') {
        // Parse address format (e.g., "40001", "30001", "00001", "10001")
        // 4xxxx = Holding Registers (FC03)
        // 3xxxx = Input Registers (FC04)
        // 0xxxx = Coils (FC01)
        // 1xxxx = Discrete Inputs (FC02)

        $addressInt = intval($address);
        $unitId = 1; // Default unit ID

        if ($addressInt >= 40000 && $addressInt < 50000) {
            // Holding Register
            $regAddress = $addressInt - 40001;
            $values = $this->readHoldingRegisters($unitId, $regAddress, 1);
        } else if ($addressInt >= 30000 && $addressInt < 40000) {
            // Input Register
            $regAddress = $addressInt - 30001;
            $values = $this->readInputRegisters($unitId, $regAddress, 1);
        } else if ($addressInt < 10000) {
            // Coil
            $values = $this->readCoils($unitId, $addressInt, 1);
        } else {
            $this->lastError = "Invalid address format";
            return null;
        }

        if ($values === false || empty($values)) {
            return null;
        }

        return $this->convertDataType($values[0], $dataType);
    }

    /**
     * Read from register number
     */
    public function readRegister($registerNumber, $dataType = 'uint16', $bitPosition = null) {
        $unitId = 1;
        $values = $this->readHoldingRegisters($unitId, $registerNumber, 1);

        if ($values === false || empty($values)) {
            return null;
        }

        $value = $values[0];

        // Extract bit if bit position specified
        if ($bitPosition !== null && $bitPosition >= 0 && $bitPosition < 16) {
            $value = ($value >> $bitPosition) & 1;
        }

        return $this->convertDataType($value, $dataType);
    }

    /**
     * Convert raw value to specified data type
     */
    private function convertDataType($value, $dataType) {
        switch ($dataType) {
            case 'int16':
                return $value > 32767 ? $value - 65536 : $value;
            case 'uint16':
                return $value;
            case 'bool':
                return (bool)$value;
            case 'float':
                // For float, need to read 2 registers
                return floatval($value);
            default:
                return $value;
        }
    }

    /**
     * Build Modbus request packet
     */
    private function buildRequest($unitId, $functionCode, $params) {
        if ($this->mode === 'tcp') {
            return $this->buildModbusTCPRequest($unitId, $functionCode, $params);
        } else {
            return $this->buildModbusRTURequest($unitId, $functionCode, $params);
        }
    }

    /**
     * Build Modbus TCP request
     */
    private function buildModbusTCPRequest($unitId, $functionCode, $params) {
        $this->transactionId++;

        // MBAP Header
        $packet = pack('n', $this->transactionId);  // Transaction ID
        $packet .= pack('n', 0);                     // Protocol ID
        $packet .= pack('n', 0);                     // Length (placeholder)
        $packet .= pack('C', $unitId);               // Unit ID

        // PDU
        $packet .= pack('C', $functionCode);

        // Function-specific data
        switch ($functionCode) {
            case self::FC_READ_HOLDING_REGISTERS:
            case self::FC_READ_INPUT_REGISTERS:
            case self::FC_READ_COILS:
            case self::FC_READ_DISCRETE_INPUTS:
                $packet .= pack('n', $params['address']);
                $packet .= pack('n', $params['quantity']);
                break;

            case self::FC_WRITE_SINGLE_REGISTER:
            case self::FC_WRITE_SINGLE_COIL:
                $packet .= pack('n', $params['address']);
                $packet .= pack('n', $params['value']);
                break;
        }

        // Update length field
        $length = strlen($packet) - 6;
        $packet = substr($packet, 0, 4) . pack('n', $length) . substr($packet, 6);

        return $packet;
    }

    /**
     * Build Modbus RTU request
     */
    private function buildModbusRTURequest($unitId, $functionCode, $params) {
        $packet = pack('C', $unitId);
        $packet .= pack('C', $functionCode);

        // Function-specific data
        switch ($functionCode) {
            case self::FC_READ_HOLDING_REGISTERS:
            case self::FC_READ_INPUT_REGISTERS:
            case self::FC_READ_COILS:
            case self::FC_READ_DISCRETE_INPUTS:
                $packet .= pack('n', $params['address']);
                $packet .= pack('n', $params['quantity']);
                break;

            case self::FC_WRITE_SINGLE_REGISTER:
            case self::FC_WRITE_SINGLE_COIL:
                $packet .= pack('n', $params['address']);
                $packet .= pack('n', $params['value']);
                break;
        }

        // Add CRC16
        $crc = $this->calculateCRC16($packet);
        $packet .= pack('v', $crc);

        return $packet;
    }

    /**
     * Send request and receive response
     */
    private function sendRequest($request) {
        try {
            $handle = $this->mode === 'tcp' ? $this->socket : $this->serialPort;

            if (!$handle) {
                $this->lastError = "No connection";
                return false;
            }

            // Send request
            $written = fwrite($handle, $request);

            if ($written === false || $written != strlen($request)) {
                $this->lastError = "Write failed";
                return false;
            }

            // Wait for response
            if ($this->mode === 'rtu') {
                usleep(50000); // 50ms delay for RTU
            }

            // Read response
            $response = '';
            $startTime = microtime(true);

            while ((microtime(true) - $startTime) < $this->timeout) {
                $chunk = fread($handle, 1024);
                if ($chunk === false) {
                    break;
                }
                $response .= $chunk;

                if (strlen($response) >= 12) {
                    break; // Minimum response received
                }

                usleep(10000); // 10ms between reads
            }

            if (empty($response)) {
                $this->lastError = "No response received";
                return false;
            }

            return $response;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Parse register read response
     */
    private function parseRegisterResponse($response, $quantity) {
        if ($this->mode === 'tcp') {
            $offset = 9; // Skip MBAP header + function code
        } else {
            $offset = 2; // Skip unit ID + function code
        }

        $byteCount = ord($response[$offset]);
        $values = [];

        for ($i = 0; $i < $quantity; $i++) {
            $pos = $offset + 1 + ($i * 2);
            if ($pos + 1 < strlen($response)) {
                $values[] = unpack('n', substr($response, $pos, 2))[1];
            }
        }

        return $values;
    }

    /**
     * Parse coil read response
     */
    private function parseCoilResponse($response, $quantity) {
        if ($this->mode === 'tcp') {
            $offset = 9;
        } else {
            $offset = 2;
        }

        $byteCount = ord($response[$offset]);
        $values = [];

        for ($i = 0; $i < $quantity; $i++) {
            $byteIndex = floor($i / 8);
            $bitIndex = $i % 8;
            $pos = $offset + 1 + $byteIndex;

            if ($pos < strlen($response)) {
                $byte = ord($response[$pos]);
                $values[] = (bool)(($byte >> $bitIndex) & 1);
            }
        }

        return $values;
    }

    /**
     * Calculate CRC16 for Modbus RTU
     */
    private function calculateCRC16($data) {
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]);

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x0001) {
                    $crc = ($crc >> 1) ^ 0xA001;
                } else {
                    $crc = $crc >> 1;
                }
            }
        }

        return $crc;
    }

    /**
     * Get signal strength (for GSM connections)
     */
    public function getSignalStrength() {
        // Would query GSM modem for signal strength
        // AT+CSQ command
        return rand(0, 31); // Simulated for now
    }

    /**
     * Disconnect
     */
    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }

        if ($this->serialPort) {
            fclose($this->serialPort);
            $this->serialPort = null;
        }

        $this->connected = false;
    }

    /**
     * Get last error
     */
    public function getLastError() {
        return $this->lastError;
    }

    public function __destruct() {
        $this->disconnect();
    }
}
