title: Environment Variables
summary: Site configuration variables such as database connection details, environment type and remote login information.

# Environment Variables

Environment specific variables like database connection details, API keys and other server configuration should be kept 
outside the application code in a separate `.env` file. This file is stored in the web root and 
kept out of version control for security reasons.

For more information see our docs on [Environment Management](../../getting_started/environment_management/).

Data which isn't sensitive that can be in version control but is mostly static such as constants is best suited to be 
included through the [Configuration API](configuration) based on the standard environment types (dev / test / live).
