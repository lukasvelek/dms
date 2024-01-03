# External enums
## What are they used for?
External enums are used for metadata. These are statically defined metadata values that can not be changed within the application.

## How to create an external enum
External enum is essentially a PHP class. Each enum class must implement `DMS\Enums\IExternalEnum` interface and contain requested methods.

The enum has to also be defined in `DMS\Components\ExternalEnumComponent` in method `initEnums()`.