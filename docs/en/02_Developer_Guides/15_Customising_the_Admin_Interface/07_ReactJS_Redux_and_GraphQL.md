title: React, Redux, and GraphQL
summary: Learn how to extend and customise the technologies we use for application state and client-rendered UI.

# Introduction to the "React" layer

Some admin modules render their UI with React, a popular Javascript library created by Facebook. 
For these sections, rendering happens via client side scripts that create and inject HTML 
declaratively using data structures. 

There are some several members of this ecosystem that all work together to provide a dyanamic UI. They include:
* [ReactJS](https://facebook.github.io/react/) - A Javascript UI library
* [Redux](http://redux.js.org/) - A state manager for Javascript
* [GraphQL](http://graphql.org/) - A query language for your API
* [Apollo](https://www.apollodata.com/) - A framework for using GraphQL in your application

All of these pillars of the frontend application can be customised, giving you more control over how the admin interface looks, feels, and behaves.

First, a brief summary of what each of these are:

## React

React's job is to render UI. Its UI elements are known as "components" and represent the fundamental building block of a React-rendered interface. A React component expressed like this:

```js
<PhotoItem size={200} caption="Angkor Wat" onSelect={openLightbox}>
    <img src="path/to/image.jpg" />
</PhotoItem>
```

Might actually render HTML that looks like this:
```html
<div class="photo-item">
    <div class="photo" style="width:200px;height:200px;">
        <img src="path/to/image.jpg">
    </div>
    <div class="photo-caption">
        <h3><a>Angkor Wat/a></h3>
    </div>
</div>
```

This syntax is known as JSX. It is transpiled at build time into native Javascript calls
to the React API. While optional, it is recommended to express components this way.

### Recommended: React Dev Tools

The [React Dev Tools](https://chrome.google.com/webstore/detail/react-developer-tools/fmkadmapgofadopljbjfkapdkoienihi?hl=en) extension available for Chrome and Firefox is critical to debugging a React UI. It will let you browse the React UI much like the DOM, showing the tree of rendered components and their current props and state in real time.

## Redux

Redux is a state management tool with a tiny API that affords the developer highly predictable behaviour. All of the application state is stored in a single object, and the only way to mutate that object is by calling an action, which is just a simple object that describes what happened. A function known as a _reducer_ mutates the state based on that action and returns a new reference with the updated state.

The following example is taken from the [Redux Github page](https://github.com/reactjs/redux):

```js
// reducer
function counter(state = 0, action) {
  switch (action.type) {
  case 'INCREMENT':
    return state + 1
  case 'DECREMENT':
    return state - 1
  default:
    return state
  }
}

let store = createStore(counter)
store.subscribe(() =>
  console.log(store.getState())
)
// Call an action
store.dispatch({ type: 'INCREMENT' })
// 1
```

### Recommended: Redux Devtools

It's important to be able to view the state of the React application when you're debugging and
building the interface.

To be able to view the state, you'll need to be in a dev environment 
and have the [Redux Devtools](https://github.com/zalmoxisus/redux-devtools-extension)
installed on Google Chrome or Firefox, which can be found by searching with your favourite search
engine.


## GraphQL and Apollo

[GraphQL](http://graphql.org/learn/) is a strictly-typed query language that allows you to describe what data you want to fetch from your API. Because it is based on types, it is self-documenting and predictable. Further, it's structure lends itself nicely to fetching nested objects. Here is an example of a simple GraphQL query:

```
query GetUser($ID: Int!) {
    user {
        name
        email
        blogPosts {
            title
            comments(Limit: 5) {
                author
                comment
            }
        }

    }
}
```

The above query is almost self-descriptive. It gets a user by ID, returns his or her name and email address, along with the title of any blog posts he or she has written, and the first five comments for each of those. The result of that query is, very predictably, JSON that takes on the same structure.

```js
{
    "user": {
        "name": "Test user",
        "email": "me@example.com",
        "blogPosts": [
            {
                "title": "How to be awesome at GraphQL",
                "comments": [
                    {
                        "author": "Uncle Cheese",
                        "comment": "Nice stuff, bro"
                    }
                ]
            }
        ]
    }
}
```

On its own, GraphQL offers nothing functional, as it's just a query language. You still need a service that will invoke queries and map their results to UI. For that, SilverStripe uses an implementation of [Apollo](http://dev.apollodata.com/) that works with React.

## For more information

This documentation will stop short of explaining React, Redux, and GraphQL/Apollo in-depth, as there is much better
documentation available all over the web. We recommend:
* [The Official React Tutorial](https://facebook.github.io/react/tutorial/tutorial.html)
* [Build With React](http://buildwithreact.com/tutorial)
* [Getting Started with Redux](https://egghead.io/courses/getting-started-with-redux)
* [The React Apollo docs](http://dev.apollodata.com/react/)

# The Injector API

Much like SilverStripe's [Injector API](../../extending/injector) in PHP,
the client-side framework has its own implementation of dependency injection 
known as `Injector`. Using Injector, you can register new services, and 
transform existing services.

Injector is broken up into three sub-APIs:
* `Injector.component` for React UI components
* `Injector.reducer` for Redux state management
* `Injector.form` for forms rendered via `FormSchema`.

The frontend Injector works a bit differently than its backend counterpart. Instead of _overriding_ a service with your own implementation, you _enhance_ an existing service with your own concerns. This pattern is known as [middleware](https://en.wikipedia.org/wiki/Middleware).

Middleware works a lot like a decorator. It doesn't alter the original API of the service,
but it can augment it with new features and concerns. This has the inherent advantage of allowing all thidparty code to have an influence over the behaviour, state, and UI of a component.

## A simple middleware example

Let's say you have an application that features error logging. By default, the error logging service simply outputs to `console.error`. But you want to customise it to send errors to a thirdparty service. For this, you could use middleware to augment the default functionality of the logger.

_LoggingService.js_
```js
const LoggingService = (error) => console.error(error);

export default LoggingService;
```

Now, let's add some middleware to that service. The signature of middleware is: 
```js
(next) => (args) => next(args)
```
Where `next()` is the next customisation in the "chain" of middleware. Before invoking the next implementation, you can add whatever customisations you need. Here's how we would use middleware to enhance `LoggingService`.

```js
import thirdPartyLogger from 'third-party-logger';
const addLoggingMiddleware = (next) => (error) => {
    if (error.type === LoggingService.CRITICAL) {
        thirdpartyLogger.send(error.message);
    }
    return next(error);
}
```

Then, we would create a new logging service that merges both implementations.
```js
import LoggingService from './LoggingService';
import addLoggingMiddleware from './addLoggingMiddleware';

const MyNewLogger = addLoggingMiddleware(LoggingService);
```

We haven't overriden any functionality. `LoggingService(error)` will still invoke `console.error`, once all the middleware has run. But what if we did want to kill the original functionality?

```js
import LoggingService from './LoggingService';
import thirdPartyLogger from 'third-party-logger';

const addLoggingMiddleware = (next) => (error) => {
    // Critical errors go to a thirdparty service
    if (error.type === LoggingService.CRITICAL) {
        thirdPartyLogger.send(error.message);
    }
    // Other errors get logged, but not to our thirdparty
    else if (error.type === LoggingService.ERROR) {
        next(error);
    } 
    // Minor errors are ignored
    else {
        // Do nothing!
    }
}
```


## Registering new services to the Injector

If you've created a module using React, it's a good idea to afford other developers an 
API to enhance those components, forms, and state. To do that, simply register them with `Injector`.

__my-public-module/js/main.js__
```js
import Injector from 'lib/Injector';

Injector.component.register('MyComponent', MyComponent);
Injector.reducer.register('myCustom', MyReducer);
```

Services can then be fetched using their respective `.get()` methods.

```js
const MyComponent = Injector.component.get('MyComponent');
```

<div class="notice" markdown="1">
Because of the unique structure of the `form` middleware, you cannot register new services to `Injector.form`.
</div>


<div class="alert" markdown="1">
Overwriting components by calling `register()` multiple times for the same
service name is discouraged, and will throw an error. Should you really need to do this,
you can pass `{ force: true }` as the third argument to the `register()` function.
</div>


## Transforming services using middleware

Now that the services are registered, other developers can customise your services with `Injector.transform()`.

__someone-elses-module/js/main.js__

```js
Injector.transform(
    'my-transformation',
    (updater) => {
        updater.component('MyComponent', MyCustomComponent);
        updater.reducer('myCustom', MyCustomReducer);

    }
);

```

Much like the configuration layer, we need to specify a name for this transformation. This will help other modules negotiate their priority over the injector in relation to yours.

The second parameter of the `transform` argument is a callback which receives an `updater`object. It contains four functions: `component()`, `reducer()`, `form.alterSchema()` and `form.addValidation()`. We'll cover all of these in detail functions in detail further into the document, but briefly, these update functions allow you to mutate the DI container with a wrapper for the service. Remember, this function does not _replace_
the service -- it enhances it with new functionality.

### Helpful tip: Name your component middleware

Since multiple enhancements can be applied to the same component, it will be really
useful for debugging purposes to reveal the names of each enhancement on the `displayName` of
 the component. This will really help you when viewing the rendered component tree in 
 [React Dev Tools](https://chrome.google.com/webstore/detail/react-developer-tools/fmkadmapgofadopljbjfkapdkoienihi?hl=en).
 
 For this, you can use the third parameter of the `updater.component` function. It takes an arbitrary
 name for the enhancement you're applying.
 
 __module-a/js/main.js__
 ```js
 (updater) => updater.component('TextField', CharacterCounter, 'CharacterCounter')
 ```
 __module-b/js/main.js__
 ```js
 (updater) => updater.component('TextField', TextLengthChecker, 'TextLengthChecker')
 ```


## Controlling the order of transformations

Sometimes, it's critical to ensure that your customisation happens after another one has been executed. To afford you control over the ordering of transforms, Injector allows `before` and `after` attributes as metadata for the transformation.

__my-module/js/main.js__

```js
Injector.transform(
    'my-transformation',
    (updater) => {
        updater.component('MyComponent', MyCustomComponent);
        updater.reducer('myCustom', MyCustomReducer);

    },
    { after: 'another-module' }
);

```

`before` and `after` also accept arrays of constraints.

```js
Injector.transform(
  'my-transformation', 
  (updater) => updater.component('MyComponent', MyCustomComponent);
  { before: ['my-transformation', 'some-other-transformation'] }
);
```

### Using the * flag

If you really want to be sure your customisation gets loaded first or last, you can use 
`*` as your `before` or `after` reference. 

```js
Injector.transform(
  'my-transformation', 
  (updater) => updater.component('MyComponent', FinalTransform),
  { after: '*' }
);
```
<div class="info" markdown="1">
This flag can only be used once per transformation.
The following are not allowed:
* `{ before: ['*', 'something-else'] }`
* `{ after: '*', before: 'something-else' }`
</div>

## Injector context

Because so much of UI design depends on context, dependency injection in the frontend is not necessarily universal. Instead, services are fetched with context.

_example_:
```js
const CalendarComponent = Injector.get('Calendar', 'AssetAdmin.FileEditForm.StartDate');
```

Likewise, services can be applied for specific contexts.

```js
Injector.transform('my-transform', (updater) => {
    // Applies to all text fields in AssetAdmin
    updater.component('TextField.AssetAdmin', MyTextField);

    // Applies to all text fields in AssetAdmin editform
    updater.component('TextField.AssetAdmin.FileEditForm', MyTextField);

    // Applies to any textfield named "Title" in AssetAdmin
    updater.component('TextField.AssetAdmin.*.Title', MyTextField);

    // Applies to any textfield named "Title" in any admin
    updater.component('TextField.*.*.Title', MyTextField);
})
```

 To apply context-based transformations, you'll need to know the context of the component you want to customise. To learn this,
 open your React Developer Tools (see above) window and inspect the component name. The
 context of the component is displayed between two square brackets, appended to the original name, for example:
 `TextField[TextField.AssetAdmin.FileEditForm.Title]`. The context description is hierarchical, starting
 with the most general category (in this case, "Admin") and working its way down to the most specific
 category (Name = 'Title'). You can use Injector to hook into the level of specificity that you want.


# Customising React components with Injector

When middleware is used to customise a React component, it is known as a [higher order component](https://facebook.github.io/react/docs/higher-order-components.html).

Using the `PhotoItem` example above, let's create a customised `PhotoItem` that allows a badge, perhaps indicating that it is new to the gallery.

```js
const enhancedPhoto = (PhotoItem) => (props) => {
    const badge = props.isNew ? 
      <div className="badge">New!</div> : 
      null;

    return (
        <div>
            {badge}
            <PhotoItem {...props} />
        </div>
    );
}

const EnhancedPhotoItem = enhancedPhoto(PhotoItem);

<EnhancedPhotoItem isNew={true} size={300} />
```

Alternatively, this component could be expressed with an ES6 class, rather than a simple
function.

```js
const enhancedPhoto = (PhotoItem) => {
    return class EnhancedPhotoItem extends React.Component {
        render() {
            const badge = this.props.isNew ? 
              <div className="badge">New!</div> : 
              null;

            return (
                <div>
                    {badge}
                    <PhotoItem {...this.props} />
                </div>
            );

        }
    }
}
```

When components are stateless, using a simple function in lieu of a class is recommended.


## Using dependencies within your React component

If your component has dependencies, you can add them via the injector using the `inject()`
higher order component. The function accepts the following arguments:

```js
inject([dependencies], mapDependenciesToProps)(Component)
```
* **[dependencies]**: An array of dependencies (or a string, if just one)
* **mapDependenciesToProps**: (optional) All dependencies are passed into this function as params. The function
is expected to return a map of props to dependencies. If this parameter is not specified,
the prop names and the service names will mirror each other.

The result is a function that is ready to apply to a component.
 
 ```js
const MyInjectedComponent = inject(
  ['Dependency1', 'Dependency2']
)(MyComponent);
// MyComponent now has access to props.Dependency1 and props.Dependency2
```
Here is its usage with a bit more context:

__my-module/js/components/Gallery.js__
```js
import React from 'react';
import { inject } from 'lib/Injector';

class Gallery extends React.Component 

{
  render() {
    const { SearchComponent, ItemComponent } = this.props;
    return (
      <div>  
         <SearchComponent />
        {this.props.items.map(item => (
          <ItemComponent title={item.title} image={item.image} />
        ))}
      </div>
    );
  }
}

export default inject(
  Gallery, 
  ['GalleryItem', 'SearchBar'], 
  (GalleryItem, SearchBar) => ({
    ItemComponent: GalleryItem,
    SearchComponent: SearchBar
  })
 );
```

## Using the injector directly within your component

On rare occasions, you may just want direct access to the injector in your component. If
your dependency requirements are dynamic, for example, you won't be able to explicitly
declare them in `inject()`. In cases like this, use `withInjector()`. This higher order
component puts the `Injector` instance in `context`.

```js
class MyGallery extends React.Component 
{
  render () {
    <div>
      {this.props.items.map(item => {
        const Component = this.context.injector.get(item.type);
        return <Component title={item.title} image={item.image} />
      })}
    </div>
  }
}

export default withInjector(MyGallery);
```

## Using Injector to customise forms

Forms in the React layer are built declaratively, using the `FormSchema` API. A component called `FormBuilderLoader` is given a URL to a form schema definition, and it populates itself with fields (both structural and data-containing) and actions to create the UI for the form. Each form is required to have an `identifier` property, which is used to create context for Injector when field components are fetched. This affords developers the opportunity provide very surgical customisations.

### Updating the form schema

Most behavioural and aesthetic customisations will happen via a mutation of the form schema. For this, we'll use the `updater.form.alterSchema()` function.

```js
Injector.transform(
    'my-custom-form',
    (updater) => {
        updater.form.alterSchema(
            'AssetAdmin.*',
            (form) =>
        form.updateField('Title', {
            myCustomProp: true
        })
        .getState()
        )
    }
);
```

The `alterSchema()` function takes a callback, with an instance of `FormStateManager` (`form` in the above example) as a parameter. `FormStateMangaer` allows you to declaratively update the form schema API using several helper methods, including:

* `updateField(fieldName:string, updates:object)`
* `updateFields({ myFieldName: updates:object })`
* `mutateField(fieldName:string, callback:function)`
* `setFieldComponent(fieldName:string, componentName:string)`
* `setFieldClass(fieldName:string, cssClassName:string, active:boolean)`
* `addFieldClass(fieldName:string, cssClassName:string)`
* `removeFieldClass(fieldName:string, cssClassName:string)`

<div class="info" markdown="1">
For a complete list of props that are available to update on a `Field` object,
see http://redux-form.com/6.8.0/docs/api/Field.md/#props-you-can-pass-to-field-
</div>

<div class="notice" markdown="1">
It is critical that you end series of mutation calls with `getState()`.
</div>

In addition to mutation methods, several readonly methods are available on `FormSchemaManager` to read the current form state, including:

* `getValues()`: Returns a map of field names to their current values
* `getValue(fieldName:string)`: Returns the value of the given field
* `isDirty()`: Returns true if the form has been mutated from its original state
* `isPristine()`: Returns true if the form is in its original state
* `isValid()`: Returns true if the form has no validation errors
* `isInvalid()`: Returns true if the form has validation errors

### Adding validation to a form

Validation for React-rendered forms is handled by the [redux-form](http://redux-form.com) package. You can inject your own middleware to add custom validation rules using the `updater.form.addValidation()` function.

```js
Injector.transform(
  'my-validation',
  (updater) => {
    updater.form.addValidation(
      'AssetAdmin.*',
      (values, validator) => {
      	if (values.PostalCode.length !== 5) {
      		validator.addError('PostalCode', 'Invalid postal code');
      	}
      }
    )
  }
);
```

The `addValidation()` function takes a callback, with an instance of `FormValidationManager` (`validator` in the above example) as a parameter. `FormValidationMangaer` allows you to manage the validation result using several helper methods, including:

* `addError(fieldName:string, message:string)`
* `addErrors(fieldName:string, messages:Array)`
* `hasError(fieldName:string)`
* `clearErrors(fieldName:string)`
* `getErrors(fieldName:string)`
* `reset(void)`


## Using Injector to customise Redux state data

Before starting this tutorial, you should become familiar with the concepts of [Immutability](https://www.sitepoint.com/immutability-javascript/) and [Redux](http://reduxjs.org).

The examples use [Spread in object literals](http://redux.js.org/docs/recipes/UsingObjectSpreadOperator.html) which is at this moment in Stage 3 Proposal. If you're more comfortable with using
 the `Object.assign()` API that shouldn't present any problems and should work the same.

For example:
```js
  newProps = { ...oldProps, name: 'New name' };
```
is the same as
```js
  newProps = Object.assign(
    {},
    oldProps,
    { name: 'New name' }
  );
```

To start customising, you'll need to transform an existing registered reducer, you can find what reducers are registered by importing Injector and running `Injector.reducer.getAll()`

```js
Injector.transform('customisationName', (updater) => {
  updater.reducer('assetAdmin', MyReducerTransformer);
});
```

As you can see, we use the `reducer()` function on the `update` object to augment Redux state transformations.

### Using Redux dev tools

It is important to learn the basics of [Redux dev tools](https://chrome.google.com/webstore/detail/redux-devtools/lmhkpmbekcpmknklioeibfkpmmfibljd?hl=en), so that you can find out what ACTIONS and payloads to intercept and modify in your Transformer should target.

Most importantly, it helps to understand the "Action" sub-tab on the right panel (bottom if your dev tools is small), as this will be the data your Transformer will most likely receive, pending other transformers that may run before/after your one.

### Structuring a transformer

We use currying to supply utilities which your transformer may require to handle the transformation.
- `originalReducer` - reducer callback which the transformer is customising, this will need to be called in most cases. This will also callback other transformations down the chain of execution. Not calling this will break the chain.
- `getGlobalState` - A function that gets the state of the global Redux store. There may be data outside the current scope in the reducer which you may need to help determine the transformation.
- `state` - current state of the current scope. This is what should be used to form the new state.
- `type` - the action to fire, like in any reducer in Redux. This helps determine if your transformer should do anything.
- `payload` - the new data sent with the action to mutate the Redux store.

```js
const MyReducerTransformer = (originalReducer) => (globalState) => (state, { type, payload }) => {
  switch (type) {
    case 'EXISTING_ACTION': {
      // recommended to call and return the originalReducer with the payload changed by the transformer
      /* return action to call here; */
    }
    
    case 'OVERRIDE_EXISTING_ACTION': {
      // could omit the originalReducer to enforce your change or cancel the originalREducer's change
    }

    default: {
      // it is important to return the originalReducer with original redux parameters.
      return originalReducer(state, { type, payload });
    }
  }
}
```

### A basic transformation

This example we will illustrate modifying the payload to get different data saved into the original reducer.

We will rename anything in the breadcrumbs that is displaying "Files" to display "Custom Files" instead.

```js
const MyReducerTransformer = (originalReducer) => (getGlobalState) => (state, { type, payload }) => {
  switch (type) {
    case 'SET_BREADCRUMBS': {
      return originalReducer(state, {
        type,
        payload: {
          breadcrumbs: payload.breadcrumbs.map((crumb) => (
            (crumb.text === 'Files')
              ? { ...crumb, text: 'Custom Files' }
              : crumb
          )),
        },
      });
    }
  }
};
```

### Using the globalState

Accessing the globalState is easy, as it is passed in as part of the curried functions definition.

```js
export default (originalReducer) => (getGlobalState) => (state, { type, payload }) => {
  const baseUrl = globalState.config.baseUrl;
  
  switch (type) {
    /* ... cases here ... */
  }
}
```

### Setting a different initial state

We can easily define a new initial state by providing the `state` param with a default value.
It is recommended to keep the call for the original initialState for your initialState then override values, so that you do not lose any potentially critical data that would have originally been set.

```js
const MyReducerTransformer = (originalReducer) => () => (state, { type, payload }) => {
  if (typeof state === 'undefined') {
    return {
      ...originalReducer(state, { type, payload }),
      myCustom: 'initial state here',
    };
  }
};
```

### Cancelling an action

There are valid reasons to break the chain of reducer transformations, such as cancelling the Redux store update.
However, like an original reducer in redux, you will still need to return the original state.

```js
export default (originalReducer) => (getGlobalState) => (state, { type, payload }) => {
  switch (type) {
    case 'CANCEL_THIS_ACTION': {
      return state;
    }
  }
};
```

### Calling a different action

You could manipulate the action called by the originalReducer, there isn't an example available but this block of
 code will present the theory of how it can be achieved.

```js
 default (originalReducer) => (getGlobalState) => (state, { type, payload }) => {
  switch (type) {
    case 'REMOVE_ERROR': {
      // we'd like to archive errors instead of removing them
      return originalReducer(state, {
        type: 'ARCHIVE_ERROR',
        payload,
      });
    }
  }
};
```

## Using Injector to customise GraphQL queries

(coming soon)