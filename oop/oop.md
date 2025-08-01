# Object-Oriented Programming (OOP) in Home Management System Backend

This document provides a comprehensive overview of how Object-Oriented Programming (OOP) principles are applied in the backend of the Home Management System project. It serves as a living guide for current and future developers to understand, extend, and maintain the codebase using OOP best practices.

---

## Table of Contents
1. [Overview of OOP in This Project](#overview)
2. [Core OOP Concepts Used](#core-concepts)
   - [Classes and Objects](#classes-objects)
   - [Encapsulation](#encapsulation)
   - [Inheritance](#inheritance)
   - [Polymorphism](#polymorphism)
   - [Abstraction](#abstraction)
3. [Key Classes and Their Roles](#key-classes)
   - [User and Subclasses](#user-subclasses)
   - [Other Domain Classes](#other-domain-classes)
4. [OOP Patterns and Best Practices](#oop-patterns)
5. [Extending OOP in the Future](#extending-oop)
6. [References](#references)

---

## 1. <a name="overview"></a>Overview of OOP in This Project

The backend is architected using PHP's OOP features to promote modularity, maintainability, security, and reusability. All major entities (users, providers, customers, admins, messages) are represented as classes, allowing for clear separation of concerns and easy extension.

## 2. <a name="core-concepts"></a>Core OOP Concepts Used

### a. <a name="classes-objects"></a>Classes and Objects
- **Classes** define blueprints for entities (e.g., `User`, `Provider`, `Customer`, `Admin`, `Message`).
- **Objects** are instances of these classes, representing real users, providers, messages, etc.

### b. <a name="encapsulation"></a>Encapsulation
- Properties (fields) are marked as `private` or `protected`, restricting direct access from outside the class.
- Public methods (getters/setters) are provided for controlled access and modification.
- Example: The `User` class encapsulates user data, and subclasses access user properties via protected inheritance.

### c. <a name="inheritance"></a>Inheritance
- Common functionality is defined in base classes (e.g., `User`).
- Specialized classes (`Admin`, `Provider`, `Customer`) extend `User`, inheriting properties and methods.
- This reduces code duplication and centralizes shared logic (e.g., authentication, loading user data).

### d. <a name="polymorphism"></a>Polymorphism
- Subclasses can override or extend parent methods to provide specialized behavior.
- Example: Each user type (`Admin`, `Provider`, `Customer`) can have custom methods or override inherited ones for their specific needs.

### e. <a name="abstraction"></a>Abstraction
- Complex operations (e.g., database access, authentication) are abstracted into methods within classes.
- The rest of the codebase interacts with these abstractions rather than raw SQL or procedural logic.

## 3. <a name="key-classes"></a>Key Classes and Their Roles

### a. User and Subclasses
- **User**: Central class for all user types. Handles loading user data, authentication, and exposes protected properties.
- **Admin, Provider, Customer**: Extend `User`, inherit core logic, and expose user data via getters. Each can implement additional logic as needed.
- All subclasses use constructor inheritance and `loadById()` methods for loading and exposing user data.

### b. Other Domain Classes
- **Message**: Handles contact form messages. Encapsulates message properties and methods for saving, retrieving, and deleting messages.
- **DBConnector**: Encapsulates database connection logic, providing a reusable and secure way to connect to the database.

## 4. <a name="oop-patterns"></a>OOP Patterns and Best Practices
- **Single Responsibility Principle**: Each class has a clear, focused responsibility (e.g., `User` for user logic, `Message` for messages).
- **Encapsulation**: Sensitive data is protected, and only safe operations are exposed.
- **Reusability**: Shared logic is centralized, minimizing duplication.
- **Extensibility**: New user types or features can be added by extending existing classes.
- **Security**: Encapsulation and abstraction help prevent accidental or malicious misuse of sensitive operations.

## 5. <a name="extending-oop"></a>Extending OOP in the Future
- To add new features, create new classes or extend existing ones.
- Follow the established pattern of encapsulating data and logic.
- Document new OOP features here for future maintainers.

## 6. <a name="references"></a>References
- [PHP OOP Documentation](https://www.php.net/manual/en/language.oop5.php)
- [Project source code: `/class/` and `/api/` folders]
- [Design patterns and best practices](https://refactoring.guru/design-patterns/php)

---

**This document is a living guide. Update it whenever new OOP features or classes are added to the project.**
