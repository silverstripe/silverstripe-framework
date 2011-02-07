# Troubleshooting

Part of the [SilverStripe Testing Guide](testing-guide).

## I can't run my new test class

If you've just added a test class, but you can't see it via the web interface, chances are, you haven't flushed your
manifest cache - append `?flush=1` to the end of your URL querystring.
