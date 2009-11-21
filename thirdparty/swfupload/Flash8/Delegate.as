class Delegate
{
   static function create(obj:Object, func:Function):Function
   {
      return function() { return func.apply(obj, arguments); };
   }
 
}