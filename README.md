# Document Management System (DMS)
This website application is used for managing documents. It can manage documents, run processes on them, manage users, groups and folders. Allow or deny rights to users and groups to perform actions or see panels.

It also enables users to manage metadata and their values.

## Changelog
### v1.11
- added document locks
- added document duplication action
    - configurable
- added exceptions
- added login attempts overview
- added user login blocking
- updated login page
- updated background services
    - they are now being run fully independently as a different process
- updated document report generator
    - now as the service progresses user can see how many percent is finished
- fixed bugs

### v1.10
- added database indexes
- added option to manage ribbon rights for a user or a group
- updated grid list render speed (SQL queries are now performed more efficiently)
- updated speed of some operations when working with hundreds or thousands of entities (mostly documents)
- updated redirect when a new folder is created (now user is redirected to the parent folder instead of being redirected to the newly created folder)
- deprecated panels (ribbon predecessor)
- fixed not working user relogin
- fixed deprecated warnings
- fixed a bug where if the user selects more than 100 documents and wants to perform a bulk action on them, no bulk actions are displayed
- fixed bugs and potential bugs

### v1.9
- added calendar support
- added ajax grid page controls
    - now switching between grid pages is done using ajax, so it does not refresh the application
- added current user subpanel
- implemented calendar to view scheduled services
- updated file storage
    - now there can be several file storages
    - also two types of file storage are introduced
        - storage for files
        - storage for generated reports
- updated link generator
- updated code documentation
- updated document filter
- updated FileManagerService service
- updated grid loading across the application
- updated design
- fixed bugs and potential bugs
- fixed a bug where the document report limit would not show integer values but rather float values
- fixed ajax slowdown (AJAX JS sleep constant update: 250ms -> 0ms)

### v1.8
- added archive section
- added background document report generator service
- added service autorun
- updated backend grid renderer
- updated user authentication security
- updated backend database SQL query builder (v1.1 => v2.0)
- updated documents grid loading speed (tested on a table with 8,000,000 entries)
- updated document reporting system
- updated code documentation
- fixed bugs and potential bugs
- fixed a bug where no flashmessage would display if the session has timed out

### v1.7
- added support for readonly custom metadata
- added support for default custom metadata value
- added support for fixed set of allowed database tables for which custom metadata can be created
- added user removing
- added group removing
- updated user/group to ajax
- updated caching system
- separated user rights
- separated gropu rights

### v1.6
- added customizable ribbons (toppanel and subpanel links)
- added `date updated` to selected entities (currently: users, documents and processes)
- added support for custom filters
- added support for several datetime formats
    - users are now able to select their default
- updated user authorization
- fixed a bug with missing or not showing flash messages

### v1.5
- added notification manager service
- added support for external enums
- added form and metadata support for external enums
- added new services (Document archivator, Declined document remover)
- updated core code
- updated debug tools
- fixed bugs and potential bugs
- optimized application

### v1.4
- added document filters
- added new after shredding action
- added support for flash messages
- added support for sending emails
- added password policy service
- added document grid pages
- added debug tools
- added grid pages with adjustable size
- updated ajax
- updated core code
- updated app database installation
- removed unused code
- merged unnecessarily split JS files into one
- fixed bugs and potential bugs
- optimized application

### v1.3
- added customizable home dashboard widgets
- added shredding
- added shredding suggestion service
- added process comments
- added core code comments
- added a new notification
- updated core functions
    - updated QueryBuilder
    - updated application loader
- fixed a bug where a notification link macro was displayed on login screen

### v1.2
- added support for electronic files
- added file manager service
- added document comments
- added process authors
- added ajax
- updated design

### v1.1
- added service configuration in-system edit
- added user prompts
- added document editing
- updated core functions
- updated logger
- updated processes

### v1.0
- document management
- metadata management
- users / groups management
- processes
- services
- right management