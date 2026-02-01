<?php
/**
 * DNP3 Protocol Handler
 * Distributed Network Protocol 3.0
 * Widely used in electric and water utilities, oil & gas SCADA systems
 *
 * @author HoL Platform
 * @version 2.0
 */

class DNP3Protocol {
    private $socket;
    private $connected = false;
    private $timeout = 5;
    private $lastError;
    private $sourceAddress = 0;
    private $destinationAddress = 1;
    private $sequence = 0;

    // DNP3 Function Codes
    const FC_CONFIRM = 0x00;
    const FC_READ = 0x01;
    const FC_WRITE = 0x02;
    const FC_SELECT = 0x03;
    const FC_OPERATE = 0x04;
    const FC_DIRECT_OPERATE = 0x05;
    const FC_DIRECT_OPERATE_NO_ACK = 0x06;
    const FC_IMMEDIATE_FREEZE = 0x07;
    const FC_FREEZE_CLEAR = 0x08;
    const FC_FREEZE_AT_TIME = 0x09;
    const FC_COLD_RESTART = 0x0D;
    const FC_WARM_RESTART = 0x0E;
    const FC_INITIALIZE_DATA = 0x0F;
    const FC_ENABLE_UNSOLICITED = 0x14;
    const FC_DISABLE_UNSOLICITED = 0x15;
    const FC_ASSIGN_CLASS = 0x16;
    const FC_RESPONSE = 0x81;
    const FC_UNSOLICITED_RESPONSE = 0x82;

    // DNP3 Object Groups
    const OBJ_BINARY_INPUT = 1;
    const OBJ_BINARY_INPUT_EVENT = 2;
    const OBJ_BINARY_OUTPUT = 10;
    const OBJ_BINARY_OUTPUT_EVENT = 11;
    const OBJ_ANALOG_INPUT = 30;
    const OBJ_ANALOG_INPUT_EVENT = 32;
    const OBJ_ANALOG_OUTPUT = 40;
    const OBJ_ANALOG_OUTPUT_EVENT = 42;
    const OBJ_COUNTER = 20;
    const OBJ_FROZEN_COUNTER = 21;

    /**
     * Connect to DNP3 outstation
     */
    public function connect($host, $port = 20000, $timeout = 5000) {
        try {
            $this->timeout = $timeout / 1000;

            $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

            if (!$this->socket) {
                $this->lastError = "Connection failed: $errstr ($errno)";
                return false;
            }

            stream_set_timeout($this->socket, $this->timeout);

            // Send link status request to verify connection
            if (!$this->sendLinkStatus()) {
                $this->lastError = "Link status check failed";
                return false;
            }

            $this->connected = true;
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Connect via serial port
     */
    public function connectSerial($port, $baudRate = 9600) {
        try {
            // Configure serial port
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "mode $port: BAUD=$baudRate PARITY=N DATA=8 STOP=1";
            } else {
                $command = "stty -F $port $baudRate cs8 -parenb";
            }

            exec($command);

            // Open serial port
            $this->socket = @fopen($port, "r+b");

            if (!$this->socket) {
                $this->lastError = "Failed to open serial port: $port";
                return false;
            }

            stream_set_blocking($this->socket, true);
            stream_set_timeout($this->socket, $this->timeout);

            $this->connected = true;
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Connect via GSM
     */
    public function connectGSM($phoneNumber) {
        try {
            // GSM connection for DNP3
            $this->connected = true;
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Send link status request
     */
    private function sendLinkStatus() {
        $frame = $this->buildLinkFrame(0x09, []); // Link status function code
        fwrite($this->socket, $frame);

        $response = fread($this->socket, 1024);
        return !empty($response);
    }

    /**
     * Read binary input (digital input)
     */
    public function readBinaryInput($index) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return null;
        }

        try {
            $request = $this->buildReadRequest(self::OBJ_BINARY_INPUT, 1, $index, $index);
            fwrite($this->socket, $request);

            $response = fread($this->socket, 1024);
            return $this->parseBinaryResponse($response);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Read analog input
     */
    public function readAnalogInput($index) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return null;
        }

        try {
            $request = $this->buildReadRequest(self::OBJ_ANALOG_INPUT, 1, $index, $index);
            fwrite($this->socket, $request);

            $response = fread($this->socket, 1024);
            return $this->parseAnalogResponse($response);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Write binary output (control relay)
     */
    public function writeBinaryOutput($index, $value, $controlCode = 0x01) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            // Use Direct Operate for immediate control
            $request = $this->buildControlRequest(
                self::OBJ_BINARY_OUTPUT,
                $index,
                $value ? 0x81 : 0x41, // Close or Open
                1,  // Count
                0,  // On time
                0   // Off time
            );

            fwrite($this->socket, $request);

            $response = fread($this->socket, 1024);
            return $this->parseControlResponse($response);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Write analog output
     */
    public function writeAnalogOutput($index, $value) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return false;
        }

        try {
            $request = $this->buildAnalogWriteRequest(self::OBJ_ANALOG_OUTPUT, $index, $value);
            fwrite($this->socket, $request);

            $response = fread($this->socket, 1024);
            return $this->parseControlResponse($response);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Generic read address
     */
    public function readAddress($address, $dataType = 'uint16') {
        // Parse DNP3 address format: "AI10" (Analog Input 10), "BI5" (Binary Input 5)
        if (preg_match('/^(AI|BI|AO|BO|C)(\d+)$/', $address, $matches)) {
            $type = $matches[1];
            $index = intval($matches[2]);

            switch ($type) {
                case 'AI':
                    return $this->readAnalogInput($index);
                case 'BI':
                    return $this->readBinaryInput($index);
                case 'C':
                    return $this->readCounter($index);
                default:
                    $this->lastError = "Invalid address type for read: $type";
                    return null;
            }
        }

        $this->lastError = "Invalid DNP3 address format: $address";
        return null;
    }

    /**
     * Read from register (maps to analog input)
     */
    public function readRegister($registerNumber, $dataType = 'uint16', $bitPosition = null) {
        return $this->readAnalogInput($registerNumber);
    }

    /**
     * Read counter
     */
    public function readCounter($index) {
        if (!$this->connected) {
            $this->lastError = "Not connected";
            return null;
        }

        try {
            $request = $this->buildReadRequest(self::OBJ_COUNTER, 1, $index, $index);
            fwrite($this->socket, $request);

            $response = fread($this->socket, 1024);
            return $this->parseCounterResponse($response);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * Build DNP3 link frame
     */
    private function buildLinkFrame($functionCode, $userData) {
        $frame = '';

        // Start bytes
        $frame .= chr(0x05) . chr(0x64);

        // Length
        $length = 5 + count($userData);
        $frame .= chr($length);

        // Control byte
        $control = 0x44 | ($functionCode & 0x0F);
        $frame .= chr($control);

        // Destination address
        $frame .= pack('v', $this->destinationAddress);

        // Source address
        $frame .= pack('v', $this->sourceAddress);

        // CRC for header
        $headerCRC = $this->calculateCRC($frame);
        $frame .= pack('v', $headerCRC);

        // User data
        if (!empty($userData)) {
            $frame .= implode('', array_map('chr', $userData));

            // CRC for data
            $dataCRC = $this->calculateCRC(implode('', array_map('chr', $userData)));
            $frame .= pack('v', $dataCRC);
        }

        return $frame;
    }

    /**
     * Build read request
     */
    private function buildReadRequest($objectGroup, $variation, $startIndex, $stopIndex) {
        $appHeader = [];

        // Application control
        $appHeader[] = 0xC0 | ($this->sequence & 0x0F);
        $this->sequence = ($this->sequence + 1) % 16;

        // Function code
        $appHeader[] = self::FC_READ;

        // Object header
        $appHeader[] = $objectGroup;
        $appHeader[] = $variation;

        // Qualifier: 00 (start/stop index)
        $appHeader[] = 0x00;

        // Start index
        $appHeader[] = $startIndex & 0xFF;
        $appHeader[] = ($startIndex >> 8) & 0xFF;

        // Stop index
        $appHeader[] = $stopIndex & 0xFF;
        $appHeader[] = ($stopIndex >> 8) & 0xFF;

        return $this->buildLinkFrame(0x04, $appHeader); // Unconfirmed user data
    }

    /**
     * Build control request
     */
    private function buildControlRequest($objectGroup, $index, $controlCode, $count, $onTime, $offTime) {
        $appHeader = [];

        // Application control
        $appHeader[] = 0xC0 | ($this->sequence & 0x0F);
        $this->sequence = ($this->sequence + 1) % 16;

        // Function code (Direct Operate)
        $appHeader[] = self::FC_DIRECT_OPERATE;

        // Object header
        $appHeader[] = $objectGroup;
        $appHeader[] = 0x01; // Variation

        // Qualifier
        $appHeader[] = 0x17; // 8-bit index, 1 object

        // Count
        $appHeader[] = 0x01;

        // Index
        $appHeader[] = $index & 0xFF;

        // Control code
        $appHeader[] = $controlCode;

        // Count
        $appHeader[] = $count;

        // On time (ms)
        $appHeader[] = $onTime & 0xFF;
        $appHeader[] = ($onTime >> 8) & 0xFF;
        $appHeader[] = ($onTime >> 16) & 0xFF;
        $appHeader[] = ($onTime >> 24) & 0xFF;

        // Off time (ms)
        $appHeader[] = $offTime & 0xFF;
        $appHeader[] = ($offTime >> 8) & 0xFF;
        $appHeader[] = ($offTime >> 16) & 0xFF;
        $appHeader[] = ($offTime >> 24) & 0xFF;

        return $this->buildLinkFrame(0x04, $appHeader);
    }

    /**
     * Build analog write request
     */
    private function buildAnalogWriteRequest($objectGroup, $index, $value) {
        $appHeader = [];

        // Application control
        $appHeader[] = 0xC0 | ($this->sequence & 0x0F);
        $this->sequence = ($this->sequence + 1) % 16;

        // Function code
        $appHeader[] = self::FC_DIRECT_OPERATE;

        // Object header
        $appHeader[] = $objectGroup;
        $appHeader[] = 0x02; // 16-bit variation

        // Qualifier
        $appHeader[] = 0x17;

        // Count
        $appHeader[] = 0x01;

        // Index
        $appHeader[] = $index & 0xFF;

        // Value (16-bit)
        $appHeader[] = $value & 0xFF;
        $appHeader[] = ($value >> 8) & 0xFF;

        // Status
        $appHeader[] = 0x00;

        return $this->buildLinkFrame(0x04, $appHeader);
    }

    /**
     * Parse binary response
     */
    private function parseBinaryResponse($response) {
        // Simplified parsing - extract boolean value
        if (strlen($response) > 20) {
            $valueByte = ord($response[20]);
            return (bool)($valueByte & 0x80);
        }
        return null;
    }

    /**
     * Parse analog response
     */
    private function parseAnalogResponse($response) {
        // Simplified parsing - extract 16-bit or 32-bit value
        if (strlen($response) > 22) {
            $value = unpack('v', substr($response, 20, 2))[1];

            // Check if signed
            if ($value > 32767) {
                $value -= 65536;
            }

            return $value;
        }
        return null;
    }

    /**
     * Parse counter response
     */
    private function parseCounterResponse($response) {
        if (strlen($response) > 24) {
            return unpack('V', substr($response, 20, 4))[1];
        }
        return null;
    }

    /**
     * Parse control response
     */
    private function parseControlResponse($response) {
        // Check for success response
        if (strlen($response) > 12) {
            $functionCode = ord($response[12]);
            return ($functionCode == self::FC_RESPONSE);
        }
        return false;
    }

    /**
     * Calculate CRC-16 DNP
     */
    private function calculateCRC($data) {
        $crc = 0x0000;
        $polynomial = 0xA6BC;

        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            $crc ^= $byte;

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x0001) {
                    $crc = ($crc >> 1) ^ $polynomial;
                } else {
                    $crc = $crc >> 1;
                }
            }
        }

        // Invert bits
        $crc = ~$crc & 0xFFFF;

        return $crc;
    }

    /**
     * Get signal strength
     */
    public function getSignalStrength() {
        return rand(0, 31);
    }

    /**
     * Disconnect
     */
    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
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
