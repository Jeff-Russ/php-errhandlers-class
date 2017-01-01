# PHP ErrHandlers Class

ErrHandlers is a php static class wrapper around the `set_error_handler()` built-in function.  

## Re-Throwing Errors with ErrHandlers::init()

ErrHandlers provides some ways to automatically re-throw ErrorException object when targeted errors occur. Normally if you try something like this:  

```php
    try {
        echo new stdClass;
        throw new Exception; // isn't thrown since php itself throws an error
    }
    catch(Exception $err) {
        echo $err->getMessage(), PHP_EOL;
    }
```

You will get 

    Catchable fatal error: Object of class stdClass could not be converted to string

The error php is throwing can't be caught unless they are bypassed with `set_error_handler()`. We can capture the error by the integer error code, which is the constant `E_RECOVERABLE_ERROR`. Using ErrHandlers you can do the following:  

```php
include_once 'ErrHandlers.php';

ErrHandlers::init(E_RECOVERABLE_ERROR); // or pass 4096

try {
    echo new stdClass;
}
catch (Exception $err){
    echo $err->getMessage(), PHP_EOL; 
}
```

that echo shows:  

    E_RECOVERABLE_ERROR: Object of class stdClass could not be converted to string

Since the integer value passed to `init()` was found to be a `'Core'` PHP constant (more on this soon), ErrHandlers was able to get the name of the constant and prepend it to a custom message, which added to a ErrorException object and thrown to the catch block.  

__NOTE: ErrHandlers::init() is like a static class constructor and must always be called!__  

We could have also targeted the error by it's message:

```php
ErrHandlers::init("Object of class stdClass could not be converted to string");
```

## ErrHandlers::rethrow()

We could have added the filter before the call to `init()`:  

```php
ErrHandlers::rethrow(E_RECOVERABLE_ERROR); // error code or message
ErrHandlers::init();
```

Both `rethrow()` and `init()` are able to take a variable number of argument and allows mixing of integers codes and string messages.  

__NOTE: ErrHandlers::init() MUST ALWAYS BE CALLED AND IT MUST BE CALLED LAST__ otherwise any changes you make with other methods will not be added to the custom handler.  

# Custom Handlers with ErrHandlers::$callbacks=[]

We've been handling the action to be performed within `ErrHandlers` but you can also provide one or many callbacks which will be executed in succession whenever an error is thrown. If your callback returns something which evaluates to `true`, the rest of the handler is bypassed (returned from with `true`). You can evaluate whether or not you'd like to handle the error by evaluating any of the arguments sent to the callback which are `($errno, $errstr, $errfile, $errline)` but you can call them whatever you like. Example:  

```php
function my_cb($errno, $errstr, $errfile, $errline) {
    if ($errstr==="Object of class stdClass could not be converted to string") {
        echo $errno." Grabbed by message! \n";
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
}
ErrHandlers::$callbacks[] = 'my_cb';
```

You could also use an anonymous function instead of a string. If you want to send in a method, use an array with two elements Example: `ErrHandlers::$callbacks[] = ['MyClass', 'myMethod'];`

`ErrHandlers::$callbacks` is simply a private static array so you can set and unset whatever you wish but __BEWARE__ since there is nothing in place to check if these function/methods are actually valid!  

## ErrHandlers::categories() for Automatic Re-throw Message Labeling

The names of the constants are searched for before the message is appended, mainly visual cue that your bypass is in place. In order to make the search for the constant's string name faster, the search is limited to certain categories of constants. You can execute the following to see the categories and constants loaded for your environment:  

```php
echo json_encode( get_defined_constants(true), JSON_PRETTY_PRINT);
```

By default `'Core'` and `'user'` are searched but you can add/remove with `ErrHandlers::categories()` You could remove the default with this:  

```php
ErrHandlers::categories(['Core'=>false, 'user'=>false]); // remove them
ErrHandlers::categories(['Core'=>true, 'user'=>true]); // add them back
// or simply:
ErrHandlers::categories(['Core', 'user']); 
// or even simpler:
ErrHandlers::categories('Core', 'user'); 
```

### Mixed Setters in ErrHandlers::init()

ErrHandlers::init() actually accepts mixed arguments that basically call both ErrHandlers::categories and ErrHandlers::rethrow():  

```php

ErrHandlers::init([
    'categories'=>['Core', 'user'],
    'rethrow'=> E_RECOVERABLE_ERROR 
]); 
```

In this case, there is a single argument that's an array of two keys, which must be `'categories'` and `'rethrow'`, where the values of each could be single values or sub-arrays.  

## Getters

If you want to see what's going with the categories you can call it in 'getter-mode' by not providing arguments:  

```php
var_dump(ErrHandlers::categories());
```

__ErrHandlers::constName()__  

If you want to see that a category is being searched and returning the right constant name you can test it with __ErrHandlers::constName()__:  

```php
echo ErrHandlers::constName(4096); // echoes: 'E_RECOVERABLE_ERROR'
```

__The 'getter-mode' also works for ErrHandlers::rethrow()__  

```php
var_dump(ErrHandlers::rethrow());
```

Note that the above actually returns an array of two arrays, one for integer error codes and the other for message strings.  

__ErrHandlers::isRethrown()__  

A simple way to check if a code or message is being re-thrown is to pass one or many of them as arguments to pass one or a number of integer and/or string message arguments to `ErrHandlers::isRethrown()`. If any would result in a re-throw, `true` will be returned, else `false`.  



