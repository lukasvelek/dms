# DMS (Document Management System) - Programming guide
## Creating UI
### About
UI in DMS is concipated of several parts. It starts with a module. Module is a big UI part that containes presenters. Currently there are two modules _AnonymModule_ and _UserModule_.

_AnonymModule_ is a definition of presenters that are allowed to be seen only to non-logged-in users - anonymous users.

_UserModule_ is a definition of presenters that are allowed to be seen only by logged-in users.

Every module consists of presenters. These presenters are essential categories of UI. 

E.g. _UserModule_ has presenters such as _Documents_, _Groups_, _HomePage_, _Metadata_, _Processes_, ...

Every presenter than contains actions. These actions can be show, do, perform or whatever. In native DMS there are two types of actions:
- show
- perform

Show type displays something to the user. Perform type performs a certain action - create, update, delete, start, ...

All these parts can be clearly seen in the URL link:
_`ModuleName:PresenterName:actionName`_. This is the common page link.

### Adding a new module
UI modules are located in _dms/app/modules/_ directory. Each module has its own directory. In that directory the module class is located. This class must implement the `IModule` interface.

E.g. when creating a module for a custom project named _CustomProject_ would have a directory _dms/app/modules/CustomProject/_. In this directory a PHP file with class named _CustomProject.php_ would be. There would also be another directory named _presenters_ that contains all presenters for the module.

### Adding a new presenter
After creating a module, presenters have to be defined. Every presenter must extend the `APresenter` abstract class that contains definitions of necessary methods.

In the presenter there are then defined actions. These actions are then called.

E.g. The _CustomProject_ module would have a presenter named _HomePage_. After defining all the necessities, an action named `showDashboard()` would be created.

This action is then going to be called in the application through link _CustomProject:HomePage:showDashboard_.

Passing parameters to the action is easy and done solely by calling either _$_GET[]_ to get values from the link or _$_POST[]_ to get data from a form.

## Creating services
### About
Services are scripts that when called perform a defined action. Services shouldn't be run from backend but rather from frontend based on user interaction.

Service classes are defined in `DMS\Services` and have to extend `AService` abstract class that implements `IServiceRunnable`. `IServiceRunnable` has a method `run()` that has to be implemented by every implementing class.

Every service has to have an entry in `DMS\Core\ServiceManager::$services` array. Here are the services instantiated and ready to be called.

### Adding new services
To add a new service a service class has to be created. For service classes there is `DMS\Services` namespace (`dms/app/services` folder). The name of the class or the file doesn't really matter because general user won't know what is the real service name.

This class contains all the functions that the service is supposed to do. All necessary code must be available from `run()` method that has to be implemented. Otherwise the application will throw an error and will not work.

After creating the service class, an entry to the `DMS\Core\ServiceManager::$services` array has to be made. For this purpose there is a method `loadServices()`. Here are defined all services.

E.g. A service class named _DeleteOldCommentsService_ has been created. An entry for this class in the `loadServices()` method would be:
`$this->services['Delete old comments'] = new DeleteOldCommentsService(...`_parameters_`...);`

The key in the `$services` array is the text that is displayed to the user in DMS->Settings->Services in column __Name__. The __System name__ is the text defined in the parent constructor of the service class (first argument).

## Creating widgets for home dashboard
### About
Widgets are defined in `WidgetComponent` in `DMS\Components`. List is defined in `createHomeDashboardWidget()` in `$widgetNames` array. Array keys are the names of the methods and widget names used in the database table and array values are the user-friendly names that are displayed in GUI.

### Adding new widgets
To add a new widget a new value has to be added to the `$widgetNames` array located in `DMS\Components\WidgetComponent::createHomeDashboardWidget()`. Array key is the name (further metioned as _name_) and the array value is the user-friendly name/text (further mentioned as _text_).

The _name_ is also the method name that contains widget's HTML code. But it has an underscore symbol before the name. It contains HTML code either in an array or a string and the final widget code has to be passed to `__getTemplate()` method (signature of this method: `__getTemplate(string $title, string $widgetCode): string`). The `$title` (first argument) has to be the _text_ defined in `$widgetNames` array. The `$widgetCode` (second argument) has to be the code inner widget code itself.

The widget method can be private because it is called only from the `WidgetComponent`.

E.g. A new widget called _userInfo_ would have entry in array: `'userInfo' => 'User information'` and a new method named `_userInfo()`.