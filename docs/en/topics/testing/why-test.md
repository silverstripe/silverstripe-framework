# Why Unit Test?

*Note: This is part of the [SilverStripe Testing Guide](/topics/testing/).*

So at this point, you might be thinking, *"that's way too complicated, I don't have time to write unit tests on top of
all the things I'm already doing"*. Fair enough. But, maybe you're already doing things that are close to unit testing
without realizing it. Everyone tests all the time, in various ways. Even if you're just refreshing a URL in a browser to
review the context of your changes, you're testing!

First, ask yourself how much time you're already spending debugging your code. Are you inserting `echo`, `print_r`,
and `die` statements into different parts of your program and watching the details dumping out to screen? **Yes, you
know you are.** So how much time do you spend doing this? How much of your development cycle is dependent on dumping out
the contents of variables to confirm your assumptions about what they contain?

From this position, it may seem that unit testing may take longer and have uncertain outcomes simply because it involves
adding more code. You'd be right, in the sense that we should be striving to write as little code as possible on
projects. The more code there is, the more room there is for bugs, the harder it is to maintain. There's absolutely no
doubt about that. But if you're dumping the contents of variables out to the screen, you are already making assertions
about your code. All unit testing does is separate these assertions into separate runnable blocks of code, rather than
have them scattered inline with your actual program logic.

The practical and immediate advantages of unit testing are twofold. Firstly, they mean you don't have to mix your
debugging and analysis code in with your actual program code (with the need to delete, or comment it out once you're
done). Secondly, they give you a way to capture the questions you ask about your code while you're writing it, and the
ability to run those questions over and over again, with no overhead or interference from other parts of the system.

Unit testing becomes particularly useful when exploring boundary conditions or edge case behavior of your code. You can
write assertions that verify examples of how your methods will be called, and verify that they always return the right
results each time. If you make changes that have the potential to break these expected results, running the unit tests
over and over again will give you immediate feedback of any regressions.

Unit tests also function as specifications. They are a sure way to describe an API and how it works by simply running
the code and demonstrating what parameters each method call expects and each method call returns. You could think of it
as live API documentation that provides real-time information about how the code works.

Unit test assertions are best understood as **pass/fail** statements about the behavior of your code. Ideally, you want
every assertion to pass, and this is usually up by the visual metaphor of green/red signals. When things are all green,
it's all good. Red indicates failure, and provides a direct warning that you need to fix or change your code.

## Getting Started

Everyone has a different set of ideas about what makes good code, and particular preferences towards a certain style of
logic. At the same time, frameworks and programming languages provide clear conventions and design idioms that guide
code towards a certain common style.

If all this ranting and raving about the importance of testing hasn't made got you thinking that you want to write tests
then we haven't done our job well enough! But the key question still remains - *"where do I start?"*.

To turn the key in the lock and answer this question, we need to look at how automated testing fits into the different
aspects of the SilverStripe platform. There are some significant differences in goals and focus between different layers
of the system and interactions between the core, and various supporting modules.

### SilverStripe Core

In open source core development, we are focussing on a large and (for the most part) stable system with existing well
defined behavior. Our overarching goal is that we do not want to break or change this existing behavior, but at the same
time we want to extend and improve it.

Testing the SilverStripe framework should focus on [characterization](http://en.wikipedia.org/wiki/Characterization_Test).
We should be writing tests that illustrate the way that the API works, feeding commonly used methods with a range of
inputs and states and verifying that these methods respond with clear and predictable results.

Especially important is documenting and straighten out edge case behavior, by pushing various objects into corners and
twisting them into situations that we know are likely to manifest with the framework in the large.

### SilverStripe Modules

Modules usually encapsulate a smaller, and well defined subset of behavior or special features added on top of the core
platform. A well constructed module will contain a reference suite of unit tests that documents and verifies all the
basic aspects of the module design. See also: [modules](/topics/modules).

### Project Modules

Testing focus on client projects will not be quite so straightforward. Every project involves different personalities,
goals, and priorities, and most of the time, there is simply not enough time or resources to exhaustively predicate
every granular aspect of an application.

On application projects, the best option is to keep tests lean and agile. Most useful is a focus on experimentation and
prototyping, using the testing framework to explore solution spaces and bounce new code up into a state where we can be
happy that it works the way we want it to.

## Rules of Thumb

**Be aware of breaking existing behavior.** Run your full suite of tests every time you do a commit.

**Not everything is permanent.** If a test is no longer relevant, delete it from the repository.

## See Also

*  [Getting to Grips with SilverStripe
Testing](http://www.slideshare.net/maetl/getting-to-grips-with-silverstripe-testing)


