<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $dbname = 'Parking';
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Failed to connect to database: ' . $e->getMessage();
        }
    }

    public function getConnection() {
        return $this->conn;
    }
    public function addVehicle($vehicleType, $floor, $slotNumber) {
        $stmt = $this->conn->prepare('INSERT INTO parking_slots (vehicle_type, floor, slot_number) VALUES (?, ?, ?)');
        $stmt->execute([$vehicleType, $floor, $slotNumber]);
    }
}

    class ParkingLot {
        private $floors;
        private $db;
        
        public function __construct($numFloors) {
            $this->floors = array();
            
            // Initialize floors
            for ($i = 1; $i <= $numFloors; $i++) {
                $this->floors[$i] = array();
            }
            
            // Connect to the database
            $dsn = 'mysql:host=localhost;dbname=Parking';
            $username = 'root';
            $password = '';
            
            try {
                $this->db = new PDO($dsn, $username, $password);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
            
        }
        

       
    
        
        
        
        public function getFreeSlotsPerFloor($vehicleType) {
            $freeSlotsPerFloor = array();
            
            foreach ($this->floors as $floor => $slots) {
                $freeSlotsCount = 0;
                
                foreach ($slots as $slot) {
                    if ($slot['vehicleType'] === $vehicleType && empty($slot['vehicle'])) {
                        $freeSlotsCount++;
                    }
                }
                
                $freeSlotsPerFloor[$floor] = $freeSlotsCount;
            }
            
            return $freeSlotsPerFloor;
        }
        
        public function parkVehicle($vehicleType) {
            $availableSlot = null;
            
            // Find the first available slot
            foreach ($this->floors as $floor => $slots) {
                foreach ($slots as $slotNumber => $slot) {
                    if ($slot['vehicleType'] === $vehicleType && empty($slot['vehicle'])) {
                        $availableSlot = array('floor' => $floor, 'slotNumber' => $slotNumber);
                        break 2;
                    }
                }
            }
            
            if ($availableSlot === null) {
                return null; // No available slot found
            }
            
            // Book the slot and park the vehicle
            $this->floors[$availableSlot['floor']][$availableSlot['slotNumber']]['vehicle'] = $vehicleType;
            
            // Store the parking information in the database
            $stmt = $this->db->prepare('INSERT INTO parking_slots (vehicle_type, floor, slot_number) VALUES (?, ?, ?)');
            $stmt->execute([$vehicleType, $availableSlot['floor'], $availableSlot['slotNumber']]);
            
            // Generate ticket
            $ticketId = uniqid();
            $ticket = array('ticketId' => $ticketId, 'vehicleType' => $vehicleType, 'floor' => $availableSlot['floor'], 'slotNumber' => $availableSlot['slotNumber']);
            
            return $ticket;
        }
        
        public function unparkVehicle($ticketId) {
            // Retrieve the parking information from the database using the ticket ID
            $stmt = $this->db->prepare('SELECT * FROM parking_slots WHERE ticket_id = ?');
            $stmt->execute([$ticketId]);
            $parkingInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parkingInfo) {
                return false; // Ticket not found
            }
            
            $floor = $parkingInfo['floor'];
            $slotNumber = $parkingInfo['slot_number'];
            
            // Remove the vehicle from the slot
            $this->floors[$floor][$slotNumber]['vehicle'] = null;
            
            // Delete the parking information from the database
            $stmt = $this->db->prepare('DELETE FROM parking_slots WHERE ticket_id = ?');
            $stmt->execute([$ticketId]);
            
            return true;
        }
    }
    
    // Example usage and testing
    
    // Create a parking lot with 2 floors
    $parkingLot = new ParkingLot(2);
    
    // Park a car
    $carTicket = $parkingLot->parkVehicle('Car');
    if ($carTicket !== null) {
        echo "Car parked. Ticket ID: " . $carTicket['ticketId'] . "\n";
    } else {
        echo "No available slot for cars.\n";
    }
    $database = new Database();
    $vehicleType = 'Car'; // ou 'Moto'
    $floor = 1;
    $slotNumber = 2;
    
    $database->addVehicle($vehicleType, $floor, $slotNumber);