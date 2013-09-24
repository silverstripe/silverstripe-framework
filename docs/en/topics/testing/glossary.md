# Glossary

**Assertion:** A predicate statement that must be true when a test runs.

**Behat:** A behaviour-driven testing library used with SilverStripe as a higher-level
alternative to the `FunctionalTest` API, see [http://behat.org](http://behat.org).

**Test Case:** The atomic class type in most unit test frameworks. New unit tests are created by inheriting from the
base test case.

**Test Suite:** Also known as a 'test group', a composite of test cases, used to collect individual unit tests into
packages, allowing all tests to be run at once.

**Fixture:** Usually refers to the runtime context of a unit test - the environment and data prerequisites that must be
in place in order to run the test and expect a particular outcome. Most unit test frameworks provide methods that can be
used to create fixtures for the duration of a test - `setUp` - and clean them up after the test is done - `tearDown'.

**Refactoring:** A behavior preserving transformation of code. If you change the code, while keeping the actual
functionality the same, it is refactoring. If you change the behavior or add new functionality it's not.

**Smell:** A code smell is a symptom of a problem. Usually refers to code that is structured in a way that will lead to
problems with maintenance or understanding.

**Spike:** A limited and throwaway sketch of code or experiment to get a feel for how long it will take to implement a
certain feature, or a possible direction for how that feature might work.

**Test Double:** Also known as a 'Substitute'. A general term for a dummy object that replaces a real object with the
same interface. Substituting objects is useful when a real object is difficult or impossible to incorporate into a unit
test.

**Fake Object**: A substitute object that simply replaces a real object with the same interface, and returns a
pre-determined (usually fixed) value from each method.

**Mock Object:** A substitute object that mimics the same behavior as a real object (some people think of mocks as
"crash test dummy" objects). Mocks differ from other kinds of substitute objects in that they must understand the
context of each call to them, setting expectations of which, and what order, methods will be invoked and what parameters
will be passed.

**Test-Driven Development (TDD):** A style of programming where tests for a new feature are constructed before any code
is written. Code to implement the feature is then written with the aim of making the tests pass. Testing is used to
understand the problem space and discover suitable APIs for performing specific actions.

**Behavior Driven Development (BDD):** An extension of the test-driven programming style, where tests are used primarily
for describing the specification of how code should perform. In practice, there's little or no technical difference - it
all comes down to language. In BDD, the usual terminology is changed to reflect this change of focus, so *Specification*
is used in place of *Test Case*, and *should* is used in place of *expect* and *assert*.
