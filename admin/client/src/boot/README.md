# Boot process

## appBoot

This is where routes, reducers, and other functions registered by core and thirdparty code
are aggregated. It's responsible for bootstrapping the main client application before
any React code is executed.

## Hooking into appBoot

In order to include routes and reducers in the bootstrapping process, you need to register
them _before_ `appBoot` is called, when the `window.onload` event is fired.

We can use the
[DOMContentLoaded](https://developer.mozilla.org/en-US/docs/Web/Events/DOMContentLoaded)
event as an opportunity to do this, as it's fired after the initial HTML document is
loaded and parsed, and before the `window.onload` event.

```
document.addEventListener('DOMContentLoaded', () => {
  // Register things.
});
```

## Registering routes

The [RouteRegister](../lib/RouteRegister.js) can be used to register routes which will
be applied to Page.js when `appBoot` is called.

__controller.js__
```
import routeRegister from 'lib/RouteRegister';

document.addEventListener('DOMContentLoaded', () => {
  routeRegister.add('/some/route', (ctx, next) => {
    // Do stuff.
  });
});
```

## Registering reducers

The [ReducerRegister](../lib/ReducerRegister.js) can be used to register reducers with
the main application Redux store. This store is passed into every route callback on the
`ctx.store` property.

__controller.js__
```
import reducerRegister from 'lib/ReducerRegister';
import routeRegister from 'lib/RouteRegister';
import ProductReducer from '../state/product/ProductReducer';

document.addEventListener('DOMContentLoaded', () => {
  reducerRegister.add('product', ProductReducer);

  routeRegister.add('/products', (ctx, next) => {
    ctx.store.getState().product // -> { products: [] }
  });
});
```
