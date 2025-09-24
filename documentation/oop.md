# Object-Oriented Programming (OOP) Documentation for Home Management System

## What is OOP and Why Use It?

OOP (Object-Oriented Programming) is like organizing your code into real-world objects. Instead of writing all code in one big file, you create separate "classes" that represent different things - like Users, Messages, or Bookings. Think of classes as blueprints, and objects as actual items built from those blueprints.

**Why your project uses OOP:**
- **Organization**: Each class handles one specific thing (Users, Messages, etc.)
- **Reusability**: Code can be shared between similar classes
- **Security**: Data is protected inside classes
- **Easy to Maintain**: Changes in one class don't break others
- **Easy to Extend**: Adding new features is simpler

## Four Main OOP Principles in Your Project

### 1. **Encapsulation** 🔒
**What it means:** Keeping data safe inside classes, like putting valuables in a safe.

**How your project uses it:**
- Database connections (`$conn`) are `protected` - only the class and its children can access them
- User passwords are hashed and never exposed directly
- Input data is sanitized before being stored

**Example from your code:**
```php
class User {
    protected $conn;  // Only this class and its children can access this
    
    public function login($email, $password) {
        // Public method that safely handles login
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Input sanitization
    }
}
```

### 2. **Inheritance** 👨‍👩‍👧‍👦
**What it means:** Child classes inherit features from parent classes, like children inheriting traits from parents.

**How your project uses it:**
- `User` is the parent class with common user functionality
- `Admin`, `Provider`, `Customer` are child classes that inherit from `User`
- Each child gets all the parent's methods plus their own special methods

**Your inheritance structure:**
```
User (parent)
├── Admin (child) - can manage all users and providers
├── Provider (child) - can manage their services and bookings  
└── Customer (child) - can book services and leave reviews
```

**Example from your code:**
```php
class Admin extends User {  // Admin inherits everything from User
    public function addProvider($data) {
        // Special method only Admin has
    }
    // Admin automatically gets login(), register(), etc. from User
}
```

### 3. **Polymorphism** 🎭
**What it means:** Different classes can have methods with the same name but different behavior.

**How your project uses it:**
- All user types can `updateProfile()`, but each does it differently
- `Provider::updateProfile()` updates both `users` and `provider` tables
- `Customer::updateProfile()` only updates the `users` table

**Example from your code:**
```php
// Customer profile update - simpler
class Customer extends User {
    public function updateProfile($data) {
        // Only updates users table
        $sql = "UPDATE users SET...";
    }
}

// Provider profile update - more complex
class Provider extends User {
    public function updateProfile($data) {
        // Updates both users AND provider tables
        $this->conn->beginTransaction();
        // Update users table
        // Update provider table  
        $this->conn->commit();
    }
}
```

### 4. **Abstraction** 🎨
**What it means:** Hiding complex details and showing only what's necessary, like a car dashboard hiding the engine complexity.

**How your project uses it:**
- `DBConnector` hides database connection complexity
- `PHPMailerService` hides email sending complexity
- `AbstractMessage` defines what message classes should do without showing how

**Example from your code:**
```php
abstract class AbstractMessage {
    // Defines what ALL message classes must have
    abstract public function saveMessage($name, $email, $phone, $subject, $message);
    abstract public function getAllMessages($page = 1, $limit = 10);
}

class Message extends AbstractMessage {
    // Must implement the abstract methods
    public function saveMessage($name, $email, $phone, $subject, $message) {
        // Actual implementation here
    }
}
```

## Your Class Structure Explained

### 1. **Core Database Class**

#### `DBConnector` - Database Connection Manager
**Location:** `api/db.php`
**Purpose:** Handles all database connections securely

```php
class DBConnector {
    private $host = 'localhost';
    private $db = 'ServiceHub';
    private $user = 'root';
    private $pass = '';
    
    public function connect() {
        // Returns secure PDO connection
        // Sets error handling and security options
    }
}
```

**Why it's good OOP:**
- **Encapsulation**: Database credentials are private
- **Reusability**: All classes use the same connection method
- **Security**: Built-in security options for all connections

### 2. **User Management Classes**

#### `User` - The Parent Class
**Location:** `class/User.php`
**Purpose:** Handles all basic user operations

**Key Methods:**
- `__construct()` - Sets up database connection
- `login($email, $password)` - Authenticates users
- `register($data)` - Registers new users with OTP
- `getUserById($userId)` - Fetches user information
- `forgotPassword($email)` - Handles password reset
- `verifyOtp($data)` - Verifies OTP codes
- `requestProfileUpdateOtp()` - Sends OTP for profile changes

**OOP Features Used:**
- **Protected properties**: `$conn` can be used by child classes
- **Dependency injection**: Can accept existing database connection
- **Input validation**: All inputs are sanitized
- **Error handling**: Returns standardized success/error arrays

#### `Admin` - Administrative User Class
**Location:** `class/Admin.php`
**Purpose:** Handles admin-specific operations

**Special Methods:**
- `addProvider($data)` - Creates new provider accounts
- `getAllProviders()` - Lists all providers for admin dashboard
- `getCustomerDetails()` - Lists all customers for management

**OOP Benefits:**
- **Inheritance**: Gets all User methods automatically
- **Separation of concerns**: Only admin operations here
- **Security**: Admin-only methods are isolated

#### `Provider` - Service Provider Class
**Location:** `class/Provider.php`
**Purpose:** Handles provider-specific operations

**Special Methods:**
- `updateProfile($data)` - Updates both users and provider tables
- `changeProviderStatus($providerId, $status)` - Changes active/inactive status
- `getProviderStatus($providerId)` - Gets current status
- `getDashboardStats($providerId)` - Gets dashboard statistics

**Advanced OOP Features:**
- **Database transactions**: Uses `beginTransaction()` and `commit()` for data integrity
- **Complex inheritance**: Overrides parent methods with provider-specific logic
- **Error handling**: Rollback transactions on failures

#### `Customer` - Customer User Class
**Location:** `class/Customer.php`
**Purpose:** Handles customer-specific operations

**Special Methods:**
- `updateProfile($data)` - Updates customer information
- Validates unique email and NIC
- Handles disable status for admin actions

### 3. **Booking Management Classes**

#### `ServiceBooking` - One-time Service Bookings
**Location:** `class/ServiceBooking.php`
**Purpose:** Handles individual service appointments

**Key Features:**
- Creates service bookings for customers
- Manages booking status (pending, waiting, process, complete, cancel)
- Sends notifications to customers and providers
- Handles cancellations with reasons

#### `SubscriptionBooking` - Recurring Service Bookings
**Location:** `class/SubscriptionBooking.php`
**Purpose:** Handles ongoing service subscriptions

**Key Features:**
- Similar to ServiceBooking but for recurring services
- Manages subscription lifecycle
- Different status handling for ongoing services

### 4. **Review System Classes**

#### `ServiceReview` - Service Feedback
**Location:** `class/ServiceReview.php`
**Purpose:** Handles customer reviews for completed services

#### `SubscriptionReview` - Subscription Feedback  
**Location:** `class/SubscriptionReview.php`
**Purpose:** Handles customer reviews for subscription services

**Common OOP Features:**
- Similar structure for consistency
- Database operations encapsulated
- Input validation and sanitization
- Standardized return formats

### 5. **Communication Classes**

#### `AbstractMessage` - Message Blueprint
**Location:** `class/AbstractMessage.php`
**Purpose:** Defines what all message classes must have

```php
abstract class AbstractMessage {
    protected $conn;
    protected $table = 'message';
    
    abstract public function saveMessage($name, $email, $phone, $subject, $message);
    abstract public function getAllMessages($page = 1, $limit = 10);
}
```

#### `Message` - Contact Form Handler
**Location:** `class/Message.php`
**Purpose:** Implements actual message functionality

**OOP Benefits:**
- **Abstract inheritance**: Must implement required methods
- **Encapsulation**: Database operations are internal
- **Standardization**: All message classes follow same pattern

### 6. **Notification System**

#### `Notification` - System Notifications
**Location:** `class/Notification.php`
**Purpose:** Handles system notifications for admin dashboard

**Features:**
- Creates notifications for important events
- Manages notification visibility
- Admin notification counts

## How Classes Work Together

### Example: User Registration Process

1. **Frontend** sends registration data
2. **API** (`register.php`) creates `User` object
3. **User class** validates data and creates OTP
4. **PHPMailerService** sends OTP email
5. **Database** stores temporary OTP
6. User enters OTP, **User class** verifies and creates account
7. **Notification class** creates admin notification

```php
// API endpoint
$userObj = new User();
$result = $userObj->register($data);

// User class handles everything internally
// - Validation
// - OTP generation  
// - Email sending
// - Database storage
```

### Example: Provider Dashboard

1. **JWT** authenticates provider user
2. **API** creates `Provider` object with database connection
3. **Provider class** queries multiple tables for statistics
4. **Returns** formatted data to frontend

```php
$db = (new DBConnector())->connect();
$provider = new Provider($db);
$stats = $provider->getDashboardStats($providerId);
```

## Security Through OOP

### 1. **Data Protection**
- Database credentials are private in `DBConnector`
- User passwords are hashed, never stored plainly
- Input sanitization in all classes

### 2. **Access Control**
- JWT authentication before class instantiation
- Role-based methods (admin-only, provider-only)
- Protected properties prevent direct access

### 3. **SQL Injection Prevention**
- All classes use prepared statements
- No direct SQL string concatenation
- Input validation before database queries

## Benefits of Your OOP Design

### 1. **Maintainability** ✅
- Each class has a single responsibility
- Changes to one feature don't break others
- Easy to locate and fix bugs

### 2. **Scalability** ✅
- Easy to add new user types (extend User class)
- New booking types can follow existing patterns
- Consistent database handling across all classes

### 3. **Security** ✅
- Data encapsulation prevents accidental exposure
- Standardized input validation
- Protected database operations

### 4. **Code Reusability** ✅
- Common functionality shared through inheritance
- Database connection reused everywhere
- Consistent error handling patterns

### 5. **Team Development** ✅
- Clear separation allows multiple developers
- Standardized patterns are easy to follow
- Documentation through method comments

## Areas for Future Improvement

### 1. **Interface Implementation**
Consider adding interfaces for better standardization:
```php
interface BookingInterface {
    public function createBooking($data);
    public function cancelBooking($id, $reason);
    public function getBookingStatus($id);
}
```

### 2. **Dependency Injection Container**
For better testability and flexibility:
```php
class Container {
    public function get($class) {
        // Auto-inject dependencies
    }
}
```

### 3. **Factory Pattern**
For creating different user types:
```php
class UserFactory {
    public static function create($userType, $dbConn) {
        switch($userType) {
            case 'admin': return new Admin($dbConn);
            case 'provider': return new Provider($dbConn);
            default: return new Customer($dbConn);
        }
    }
}
```

## OOP Best Practices You're Following

1. ✅ **Single Responsibility Principle**: Each class has one main job
2. ✅ **Encapsulation**: Data is protected and accessed through methods
3. ✅ **Inheritance**: Common functionality is shared through parent classes
4. ✅ **Polymorphism**: Same method names with different implementations
5. ✅ **Abstraction**: Complex operations are hidden behind simple interfaces
6. ✅ **Constructor Dependency Injection**: Classes accept their dependencies
7. ✅ **Protected Properties**: Child classes can access parent data safely
8. ✅ **Method Documentation**: All methods have clear PHPDoc comments
9. ✅ **Error Handling**: Consistent return formats across all classes
10. ✅ **Input Validation**: All user inputs are sanitized before processing

## File Structure Summary

```
backend/home-management-system-Backend/
├── class/
│   ├── User.php                    # Base user functionality
│   ├── Admin.php                   # Admin-specific methods (extends User)
│   ├── Provider.php                # Provider-specific methods (extends User)
│   ├── Customer.php                # Customer-specific methods (extends User)
│   ├── AbstractMessage.php         # Abstract message blueprint
│   ├── Message.php                 # Contact form messages (extends AbstractMessage)
│   ├── Notification.php            # System notifications
│   ├── ServiceBooking.php          # One-time service bookings
│   ├── SubscriptionBooking.php     # Recurring service bookings
│   ├── ServiceReview.php           # Service reviews and ratings
│   ├── SubscriptionReview.php      # Subscription reviews and ratings
│   └── phpmailer.php               # Email service wrapper
└── api/
    ├── db.php                      # DBConnector class
    ├── auth.php                    # JWT authentication functions
    └── [API endpoints]             # Use classes for business logic
```

Your OOP implementation provides a solid, secure, and maintainable foundation for the Home Management System. The clear separation of concerns, proper inheritance hierarchy, and consistent patterns make it easy to understand, extend, and maintain.
