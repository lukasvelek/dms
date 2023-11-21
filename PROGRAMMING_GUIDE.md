# DMS (Document Management System) - Programming guide
## Creating widgets for home dashboard
### About
Widgets are defined in `WidgetComponent` in `DMS\Components`. List is defined in `createHomeDashboardWidget()` in `$widgetNames` array. Array keys are the names of the methods and widget names used in the database table and array values are the user-friendly names that are displayed in GUI.

### Adding new widgets
To add a new widget a new value has to be added to the `$widgetNames` array located in `DMS\Components\WidgetComponent::createHomeDashboardWidget()`. Array key is the name (further metioned as _name_) and the array value is the user-friendly name/text (further mentioned as _text_).

The _name_ is also the method name that contains widget's HTML code. But it has an underscore symbol before the name. It contains HTML code either in an array or a string and the final widget code has to be passed to `__getTemplate()` method (signature of this method: `__getTemplate(string $title, string $widgetCode): string`). The `$title` (first argument) has to be the _text_ defined in `$widgetNames` array. The `$widgetCode` (second argument) has to be the code inner widget code itself.

The widget method can be private because it is called only from the `WidgetComponent`.

E.g. A new widget called _userInfo_ would have entry in array: `'userInfo' => 'User information'` and a new method named `_userInfo()`.